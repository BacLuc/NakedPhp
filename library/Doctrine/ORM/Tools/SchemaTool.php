<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Internal\CommitOrderCalculator;

/**
 * The SchemaTool is a tool to create/drop/update database schemas based on
 * <tt>ClassMetadata</tt> class descriptors.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class SchemaTool
{
    /**
     * @var string
     */
    const DROP_METADATA = "metadata";
    /**
     * @var string
     */
    const DROP_DATABASE = "database";

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $_platform;

    /**
     * Initializes a new SchemaTool instance that uses the connection of the
     * provided EntityManager.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_platform = $em->getConnection()->getDatabasePlatform();
    }

    /**
     * Creates the database schema for the given array of ClassMetadata instances.
     *
     * @param array $classes
     */
    public function createSchema(array $classes)
    {
        $createSchemaSql = $this->getCreateSchemaSql($classes);
        $conn = $this->_em->getConnection();
        
        foreach ($createSchemaSql as $sql) {
            $conn->execute($sql);
        }
    }

    /**
     * Gets the list of DDL statements that are required to create the database schema for
     * the given list of ClassMetadata instances.
     *
     * @param array $classes
     * @return array $sql The SQL statements needed to create the schema for the classes.
     */
    public function getCreateSchemaSql(array $classes)
    {
        $sql = array(); // All SQL statements
        $processedClasses = array(); // Reminder for processed classes, used for hierarchies
        $foreignKeyConstraints = array(); // FK SQL statements. Appended to $sql at the end.
        $sequences = array(); // Sequence SQL statements. Appended to $sql at the end.

        foreach ($classes as $class) {
            if (isset($processedClasses[$class->name]) || $class->isMappedSuperclass) {
                continue;
            }

            $options = array(); // table options
            $columns = array(); // table columns
            
            if ($class->isInheritanceTypeSingleTable()) {
                $columns = $this->_gatherColumns($class, $options);
                $this->_gatherRelationsSql($class, $sql, $columns, $foreignKeyConstraints);
                
                // Add the discriminator column
                $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class);
                $columns[$discrColumnDef['name']] = $discrColumnDef;

                // Aggregate all the information from all classes in the hierarchy
                foreach ($class->parentClasses as $parentClassName) {
                    // Parent class information is already contained in this class
                    $processedClasses[$parentClassName] = true;
                }
                
                foreach ($class->subClasses as $subClassName) {
                    $subClass = $this->_em->getClassMetadata($subClassName);
                    $columns = array_merge($columns, $this->_gatherColumns($subClass, $options));
                    $this->_gatherRelationsSql($subClass, $sql, $columns, $foreignKeyConstraints);
                    $processedClasses[$subClassName] = true;
                }
            } else if ($class->isInheritanceTypeJoined()) {
                // Add all non-inherited fields as columns
                foreach ($class->fieldMappings as $fieldName => $mapping) {
                    if ( ! isset($mapping['inherited'])) {
                        $columnName = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
                        $columns[$columnName] = $this->_gatherColumn($class, $mapping, $options);
                    }
                }

                $this->_gatherRelationsSql($class, $sql, $columns, $foreignKeyConstraints);

                // Add the discriminator column only to the root table
                if ($class->name == $class->rootEntityName) {
                    $discrColumnDef = $this->_getDiscriminatorColumnDefinition($class);
                    $columns[$discrColumnDef['name']] = $discrColumnDef;
                } else {
                    // Add an ID FK column to child tables
                    $idMapping = $class->fieldMappings[$class->identifier[0]];
                    $idColumn = $this->_gatherColumn($class, $idMapping, $options);
                    
                    unset($idColumn['autoincrement']);
                    
                    $columns[$idColumn['name']] = $idColumn;
                    
                    // Add a FK constraint on the ID column
                    $constraint = array();
                    $constraint['tableName'] = $class->getQuotedTableName($this->_platform);
                    $constraint['foreignTable'] = $this->_em->getClassMetadata($class->rootEntityName)->getQuotedTableName($this->_platform);
                    $constraint['local'] = array($idColumn['name']);
                    $constraint['foreign'] = array($idColumn['name']);
                    $constraint['onDelete'] = 'CASCADE';
                    $foreignKeyConstraints[] = $constraint;
                }
            } else if ($class->isInheritanceTypeTablePerClass()) {
                throw DoctrineException::notSupported();
            } else {
                $columns = $this->_gatherColumns($class, $options);
                $this->_gatherRelationsSql($class, $sql, $columns, $foreignKeyConstraints);
            }
            
            if (isset($class->primaryTable['indexes'])) {
                $options['indexes'] = $class->primaryTable['indexes'];
            }
            
            if (isset($class->primaryTable['uniqueConstraints'])) {
                $options['uniqueConstraints'] = $class->primaryTable['uniqueConstraints'];
            }

            $sql = array_merge($sql, $this->_platform->getCreateTableSql(
                $class->getQuotedTableName($this->_platform), $columns, $options)
            );

            $processedClasses[$class->name] = true;

            // TODO if we're reusing the sequence previously defined (in another model),
            // it should not attempt to create a new sequence.
            if ($class->isIdGeneratorSequence() && $class->name == $class->rootEntityName) {
                $seqDef = $class->getSequenceGeneratorDefinition();
                $sequences[] = $this->_platform->getCreateSequenceSql(
                    $seqDef['sequenceName'],
                    $seqDef['initialValue'],
                    $seqDef['allocationSize']
                );
            }
        }

        // Append the foreign key constraints SQL
        if ($this->_platform->supportsForeignKeyConstraints()) {
            foreach ($foreignKeyConstraints as $fkConstraint) {
                $sql = array_merge($sql, (array) $this->_platform->getCreateForeignKeySql($fkConstraint['tableName'], $fkConstraint));
            }
        }

        // Append the sequence SQL
        $sql = array_merge($sql, $sequences);

        return $sql;
    }

    /**
     * Gets a portable column definition as required by the DBAL for the discriminator
     * column of a class.
     * 
     * @param ClassMetadata $class
     * @return array The portable column definition of the discriminator column as required by
     *              the DBAL.
     */
    private function _getDiscriminatorColumnDefinition($class)
    {
        $discrColumn = $class->discriminatorColumn;
        
        return array(
            'name' => $class->getQuotedDiscriminatorColumnName($this->_platform),
            'type' => Type::getType($discrColumn['type']),
            'length' => $discrColumn['length'],
            'notnull' => true
        );
    }

    /**
     * Gathers the column definitions as required by the DBAL of all field mappings
     * found in the given class.
     *
     * @param ClassMetadata $class
     * @param array $options The table options/constraints where any additional options/constraints
     *              that are required by columns should be appended.
     * @return array The list of portable column definitions as required by the DBAL.
     */
    private function _gatherColumns($class, array &$options)
    {
        $columns = array();
        
        foreach ($class->fieldMappings as $fieldName => $mapping) {
            $column = $this->_gatherColumn($class, $mapping, $options);
            $columns[$column['name']] = $column;
        }
        
        return $columns;
    }
    
    /**
     * Creates a column definition as required by the DBAL from an ORM field mapping definition.
     * 
     * @param ClassMetadata $class The class that owns the field mapping.
     * @param array $mapping The field mapping.
     * @param array $options The table options/constraints where any additional options/constraints
     *          required by the column should be appended.
     * @return array The portable column definition as required by the DBAL.
     */
    private function _gatherColumn($class, array $mapping, array &$options)
    {
        $column = array();
        $column['name'] = $class->getQuotedColumnName($mapping['fieldName'], $this->_platform);
        $column['type'] = Type::getType($mapping['type']);
        $column['length'] = isset($mapping['length']) ? $mapping['length'] : null;
        $column['notnull'] = isset($mapping['nullable']) ? ! $mapping['nullable'] : true;
        $column['unique'] = isset($mapping['unique']) ? $mapping['unique'] : false;
        $column['version'] = $class->isVersioned && $class->versionField == $mapping['fieldName'] ? true : false;

        if(strtolower($column['type']) == 'string' && $column['length'] === null) {
            $column['length'] = 255;
        }

        if (isset($mapping['precision'])) {
            $column['precision'] = $mapping['precision'];
        }
        
        if (isset($mapping['scale'])) {
            $column['scale'] = $mapping['scale'];
        }
        
        if (isset($mapping['default'])) {
            $column['default'] = $mapping['default'];
        }
        
        if ($class->isIdentifier($mapping['fieldName'])) {
            $column['primary'] = true;
            $options['primary'][] = $mapping['columnName'];

            if ($class->isIdGeneratorIdentity()) {
                $column['autoincrement'] = true;
            }
        }

        return $column;
    }

    /**
     * Gathers the SQL for properly setting up the relations of the given class.
     * This includes the SQL for foreign key constraints and join tables.
     * 
     * @param ClassMetadata $class
     * @param array $sql The sequence of SQL statements where any new statements should be appended.
     * @param array $columns The list of columns in the class's primary table where any additional
     *          columns required by relations should be appended.
     * @param array $constraints The constraints of the table where any additional constraints
     *          required by relations should be appended.
     * @return void
     */
    private function _gatherRelationsSql($class, array &$sql, array &$columns, array &$constraints)
    {
        foreach ($class->associationMappings as $fieldName => $mapping) {
            if (isset($class->inheritedAssociationFields[$fieldName])) {
                continue;
            }

            $foreignClass = $this->_em->getClassMetadata($mapping->targetEntityName);
            
            if ($mapping->isOneToOne() && $mapping->isOwningSide) {
                $constraint = array();
                $constraint['tableName'] = $class->getQuotedTableName($this->_platform);
                $constraint['foreignTable'] = $foreignClass->getQuotedTableName($this->_platform);
                $constraint['local'] = array();
                $constraint['foreign'] = array();
                
                foreach ($mapping->getJoinColumns() as $joinColumn) {
                    $column = array();
                    $column['name'] = $mapping->getQuotedJoinColumnName($joinColumn['name'], $this->_platform);
                    $referencedColumnName = $joinColumn['referencedColumnName'];
                    $type = $foreignClass->getTypeOfColumn($referencedColumnName);
                    if ($type === null) {
                        throw new \Doctrine\Common\DoctrineException("Column name `$referencedColumnName` referenced for relation from $mapping->sourceEntityName towards $mapping->targetEntityName does not exist.");
                    }
                    $column['type'] = Type::getType($type);

                    $columns[$column['name']] = $column;
                    $constraint['local'][] = $column['name'];
                    $constraint['foreign'][] = $joinColumn['referencedColumnName'];
                    
                    if (isset($joinColumn['onUpdate'])) {
                        $constraint['onUpdate'] = $joinColumn['onUpdate'];
                    }
                    
                    if (isset($joinColumn['onDelete'])) {
                        $constraint['onDelete'] = $joinColumn['onDelete'];
                    }
                }
                
                $constraints[] = $constraint;
            } else if ($mapping->isOneToMany() && $mapping->isOwningSide) {
                //... create join table, one-many through join table supported later
                throw DoctrineException::notSupported();
            } else if ($mapping->isManyToMany() && $mapping->isOwningSide) {
                // create join table
                $joinTableColumns = array();
                $joinTableOptions = array('primary' => array(), 'uniqueConstraints' => array());
                $joinTable = $mapping->getJoinTable();
                
                // Build first FK constraint (relation table => source table)
                $constraint1 = array(
                    'tableName' => $mapping->getQuotedJoinTableName($this->_platform),
                    'foreignTable' => $class->getQuotedTableName($this->_platform),
                    'local' => array(),
                    'foreign' => array()
                );
                
                foreach ($joinTable['joinColumns'] as $joinColumn) {
                    $column = array();
                    $column['primary'] = true;
                    $joinTableOptions['primary'][] = $joinColumn['name'];
                    $column['name'] = $mapping->getQuotedJoinColumnName($joinColumn['name'], $this->_platform);
                    $column['type'] = Type::getType($class->getTypeOfColumn($joinColumn['referencedColumnName']));
                    $joinTableColumns[$column['name']] = $column;
                    $constraint1['local'][] = $column['name'];
                    $constraint1['foreign'][] = $joinColumn['referencedColumnName'];

                    if(isset($joinColumn['unique']) && $joinColumn['unique'] == true) {
                        $joinTableOptions['uniqueConstraints'][] = array($joinColumn['name']);
                    }
                    
                    if (isset($joinColumn['onUpdate'])) {
                        $constraint1['onUpdate'] = $joinColumn['onUpdate'];
                    }
                    
                    if (isset($joinColumn['onDelete'])) {
                        $constraint1['onDelete'] = $joinColumn['onDelete'];
                    }
                }
                
                $constraints[] = $constraint1;
                
                // Build second FK constraint (relation table => target table)
                $constraint2 = array();
                $constraint2['tableName'] = $mapping->getQuotedJoinTableName($this->_platform);
                $constraint2['foreignTable'] = $foreignClass->getQuotedTableName($this->_platform);
                $constraint2['local'] = array();
                $constraint2['foreign'] = array();
                
                foreach ($joinTable['inverseJoinColumns'] as $inverseJoinColumn) {
                    $column = array();
                    $column['primary'] = true;
                    $joinTableOptions['primary'][] = $inverseJoinColumn['name'];
                    $column['name'] = $inverseJoinColumn['name'];
                    $column['type'] = Type::getType($this->_em->getClassMetadata($mapping->getTargetEntityName())
                            ->getTypeOfColumn($inverseJoinColumn['referencedColumnName']));
                    $joinTableColumns[$inverseJoinColumn['name']] = $column;
                    $constraint2['local'][] = $inverseJoinColumn['name'];
                    $constraint2['foreign'][] = $inverseJoinColumn['referencedColumnName'];

                    if(isset($inverseJoinColumn['unique']) && $inverseJoinColumn['unique'] == true) {
                        $joinTableOptions['uniqueConstraints'][] = array($inverseJoinColumn['name']);
                    }
                    
                    if (isset($inverseJoinColumn['onUpdate'])) {
                        $constraint2['onUpdate'] = $inverseJoinColumn['onUpdate'];
                    }
                    
                    if (isset($joinColumn['onDelete'])) {
                        $constraint2['onDelete'] = $inverseJoinColumn['onDelete'];
                    }
                }
                
                $constraints[] = $constraint2;
                
                // Get the SQL for creating the join table and merge it with the others
                $sql = array_merge($sql, $this->_platform->getCreateTableSql(
                    $mapping->getQuotedJoinTableName($this->_platform), $joinTableColumns, $joinTableOptions)
                );
            }
        }
    }
    
    /**
     * Drops the database schema for the given classes.
     *
     * In any way when an exception is thrown it is supressed since drop was
     * issued for all classes of the schema and some probably just don't exist.
     *
     * @param array $classes
     * @param string $mode
     * @return void
     */
    public function dropSchema(array $classes, $mode=self::DROP_METADATA)
    {
        $dropSchemaSql = $this->getDropSchemaSql($classes, $mode);
        $conn = $this->_em->getConnection();
        
        foreach ($dropSchemaSql as $sql) {
            $conn->execute($sql);
        }
    }
    
    /**
     * Gets the SQL needed to drop the database schema for the given classes.
     * 
     * @param array $classes
     * @param string $mode
     * @return array
     */
    public function getDropSchemaSql(array $classes, $mode=self::DROP_METADATA)
    {
        if($mode == self::DROP_METADATA) {
            $tables = $this->_getDropSchemaTablesMetadataMode($classes);
        } else if($mode == self::DROP_DATABASE) {
            $tables = $this->_getDropSchemaTablesDatabaseMode($classes);
        } else {
            throw new \Doctrine\ORM\ORMException("Given Drop Schema Mode is not supported.");
        }

        $sm = $this->_em->getConnection()->getSchemaManager();
        /* @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */
        $allTables = $sm->listTables();
        
        $sql = array();
        foreach($tables AS $tableName) {
            if(in_array($tableName, $allTables)) {
                $sql[] = $this->_platform->getDropTableSql($tableName);
            }
        }

        return $sql;
    }

    /**
     * Drop all tables of the database connection.
     * 
     * @return array
     */
    private function _getDropSchemaTablesDatabaseMode($classes)
    {
        $conn = $this->_em->getConnection();
        
        $sm = $conn->getSchemaManager();
        /* @var $sm \Doctrine\DBAL\Schema\AbstractSchemaManager */

        $allTables = $sm->listTables();

        $orderedTables = $this->_getDropSchemaTablesMetadataMode($classes);
        foreach($allTables AS $tableName) {
            if(!in_array($tableName, $orderedTables)) {
                $orderedTables[] = $tableName;
            }
        }

        return $orderedTables;
    }

    private function _getDropSchemaTablesMetadataMode(array $classes)
    {
        $orderedTables = array();
        
        $commitOrder = $this->_getCommitOrder($classes);
        $associationTables = $this->_getAssociationTables($commitOrder);

        // Drop association tables first
        foreach ($associationTables as $associationTable) {
            $orderedTables[] = $associationTable;
        }

        // Drop tables in reverse commit order
        for ($i = count($commitOrder) - 1; $i >= 0; --$i) {
            $class = $commitOrder[$i];

            if (($class->isInheritanceTypeSingleTable() && $class->name != $class->rootEntityName)
                || $class->isMappedSuperclass) {
                continue;
            }

            $orderedTables[] = $class->getTableName();
        }

        //TODO: Drop other schema elements, like sequences etc.

        return $orderedTables;
    }
    
    /**
     * Updates the database schema of the given classes by comparing the ClassMetadata
     * instances to the current database schema that is inspected.
     * 
     * @param array $classes
     * @return void
     */
    public function updateSchema(array $classes)
    {
        $updateSchemaSql = $this->getUpdateSchemaSql($classes);
        $conn = $this->_em->getConnection();
        
        foreach ($updateSchemaSql as $sql) {
            $conn->execute($sql);
        }
    }
    
    /**
     * Gets the sequence of SQL statements that need to be performed in order
     * to bring the given class mappings in-synch with the relational schema.
     * 
     * @param array $classes The classes to consider.
     * @return array The sequence of SQL statements.
     */
    public function getUpdateSchemaSql(array $classes)
    {
        $sql = array();
        $conn = $this->_em->getConnection();
        $sm = $conn->getSchemaManager();
        
        $tables = $sm->listTables();
        $newClasses = array();
        
        foreach ($classes as $class) {
            $tableName = $class->getTableName();
            $tableExists = false;
            
            foreach ($tables as $index => $table) {
                if ($tableName == $table) {
                    $tableExists = true;
                    
                    unset($tables[$index]);
                    break;
                }
            }
            
            if ( ! $tableExists) {
                $newClasses[] = $class;
            } else {
                $newFields = array();
                $updateFields = array();
                $dropIndexes = array();
                $newJoinColumns = array();
                $currentColumns = $sm->listTableColumns($tableName);
                                
                foreach ($class->fieldMappings as $fieldName => $fieldMapping) {
                    $exists = false;
                    
                    foreach ($currentColumns as $index => $column) {
                        if ($column['name'] == $fieldMapping['columnName']) {
                            // Column exists, check for changes
                            $columnInfo = $column;
                            $columnChanged = false;
                                                        
                            // 1. check for nullability change
                            $columnInfo['notnull'] = ( ! isset($columnInfo['notnull'])) 
                                ? false : $columnInfo['notnull'];
                            $notnull = ! $class->isNullable($fieldName);
                            
                            if ($columnInfo['notnull'] != $notnull) {
                                $columnInfo['notnull'] = $notnull;
                                $columnChanged = true;
                            }
                            
                            unset($notnull);
                            
                            // 2. check for uniqueness change
                            $columnInfo['unique'] = ( ! isset($columnInfo['unique'])) 
                                ? false : $columnInfo['unique'];
                            $unique = $class->isUniqueField($fieldName);
                            
                            if ($columnInfo['unique'] != $unique) {
                                // We need to call a special DROP INDEX if it was defined
                                if ($columnInfo['unique']) {
                                    $dropIndexes[] = $column['name'];
                                }
                                
                                $columnInfo['unique'] = $unique;
                                $columnChanged = true;
                            }
                            
                            unset($unique);
                            
                            // 3. check for type change
                            $type = Type::getType($fieldMapping['type']);
                            
                            if ($columnInfo['type'] != $type) {
                                $columnInfo['type'] = $type;
                                $columnChanged = true;
                            }
                            
                            unset($type);
                            
                            // 4. check for scale and precision change
                            if (strtolower($columnInfo['type']) == 'decimal') {
                                /*// Doesn't work yet, see DDC-89
                                if($columnInfo['length'] != $fieldMapping['precision'] ||
                                   $columnInfo['scale'] != $fieldMapping['scale']) {

                                    $columnInfo['length'] = $fieldMapping['precision'];
                                    $columnInfo['scale'] = $fieldMapping['scale'];
                                    $columnChanged = true;
                                }*/
                            }
                            // 5. check for length change of strings
                            elseif(strtolower($fieldMapping['type']) == 'string') {
                                if(!isset($fieldMapping['length']) || $fieldMapping['length'] === null) {
                                    $fieldMapping['length'] = 255;
                                }

                                if($columnInfo['length'] != $fieldMapping['length']) {
                                    $columnInfo['length'] = $fieldMapping['length'];
                                    $columnChanged = true;
                                }
                            }
                            
                            // 6. check for flexible and fixed length
                            $fieldMapping['fixed'] = ( ! isset($fieldMapping['fixed'])) 
                                ? false : $fieldMapping['fixed'];
                                
                            if ($columnInfo['fixed'] != $fieldMapping['fixed']) {
                                $columnInfo['fixed'] = $fieldMapping['fixed'];
                                $columnChanged = true;
                            }
                            
                            // Only add to column changed list if it was actually changed
                            if ($columnChanged) {
                                $updateFields[] = $columnInfo;
                            }
                            
                            unset($currentColumns[$index]);
                            $exists = true;
                            break;
                        }
                    }
                    
                    if ( ! $exists) {
                        $newFields[] = $fieldMapping;
                    }
                }
                
                foreach ($class->associationMappings as $assoc) {
                    if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                        foreach ($assoc->targetToSourceKeyColumns as $targetColumn => $sourceColumn) {
                            $exists = false;
                            
                            foreach ($currentColumns as $index => $column) {
                                if ($column['name'] == $sourceColumn) {
                                    // Column exists, check for changes
                                    
                                    // 1. check for nullability change
                                    
                                    unset($currentColumns[$index]);
                                    $exists = true;
                                    break;
                                }
                            }
                            
                            if ( ! $exists) {
                                $newJoinColumns[$sourceColumn] = array(
                                    'name' => $sourceColumn,
                                    'type' => 'integer' //FIXME!!!
                                );
                            }
                        }
                    }
                }
                
                // Drop indexes
                if ($dropIndexes) {
                    foreach ($dropIndexes as $dropIndex) {
                        $sql[] = $this->_platform->getDropIndexSql($tableName, $dropIndex);
                    }
                }
                
                // Create new columns
                if ($newFields || $newJoinColumns) {
                    $changes = array();
                    
                    foreach ($newFields as $newField) {
                        $options = array();
                        $changes['add'][$newField['columnName']] = $this->_gatherColumn($class, $newField, $options);
                    }
                    
                    foreach ($newJoinColumns as $name => $joinColumn) {
                        $joinColumn['type'] = Type::getType($joinColumn['type']);
                        $changes['add'][$name] = $joinColumn;
                    }
                    $sql = array_merge($sql, $this->_platform->getAlterTableSql($tableName, $changes));
                }
                
                // Update existent columns
                if ($updateFields) {
                    $changes = array();
                    
                    foreach ($updateFields as $updateField) {
                        // Now we pick the Type instance
                        $changes['change'][$updateField['name']] = array(
                            'definition' => $updateField
                        );
                    }
                    
                    $sql = array_merge($sql, $this->_platform->getAlterTableSql($tableName, $changes));
                }
                
                // Drop any remaining columns
                if ($currentColumns) {
                    $changes = array();
                    
                    foreach ($currentColumns as $column) {
                        $options = array();
                        $changes['remove'][$column['name']] = $column;
                    }
                    
                    $sql = array_merge($sql, $this->_platform->getAlterTableSql($tableName, $changes));
                }
            }
        }
        
        if ($newClasses) {
            $sql = array_merge($this->getCreateSchemaSql($newClasses), $sql);
        }
        
        // Drop any remaining tables (Probably not a good idea, because the given class list
        // may not be complete!)
        /*if ($tables) {
            foreach ($tables as $table) {
                $sql[] = $this->_platform->getDropTableSql($table);
            }
        }*/
        
        return $sql;
    }
    
    private function _getCommitOrder(array $classes)
    {
        $calc = new CommitOrderCalculator;
        
        // Calculate dependencies
        foreach ($classes as $class) {
            $calc->addClass($class);
            
            foreach ($class->associationMappings as $assoc) {
                if ($assoc->isOwningSide) {
                    $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
                    
                    if ( ! $calc->hasClass($targetClass->name)) {
                        $calc->addClass($targetClass);
                    }
                    
                    // add dependency ($targetClass before $class)
                    $calc->addDependency($targetClass, $class);
                }
            }
        }

        return $calc->getCommitOrder();
    }
    
    private function _getAssociationTables(array $classes)
    {
        $associationTables = array();
        
        foreach ($classes as $class) {
            foreach ($class->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isManyToMany()) {
                    $associationTables[] = $assoc->joinTable['name'];
                }
            }
        }
        
        return $associationTables;
    }
}