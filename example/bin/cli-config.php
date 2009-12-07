<?php
#
# This configuration file is loaded by the Doctrine CLI whenever you execute
# a task. A CLI configuration file usually initializes two local variables:
#
# $em - An EntityManager instance that the CLI tasks should use.
# $globalArguments - An array of default command line arguments that are passed to all
#                    CLI tasks automatically when an argument is not specifically set on
#                    the command line.
#
# You can create several CLI configuration files with different names, for different databases.
# Every CLI task recognizes the --config=<path> option where you can specify the configuration
# file to use for a particular task. If this option is not given, the CLI looks for a file
# named "cli-config.php" (this one) in the same directory and uses that by default.
#

$classLoader = new \Doctrine\Common\IsolatedClassLoader('Proxies');
$classLoader->setBasePath(__DIR__ . '/../application/');
$classLoader->register();

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path' => '/var/www/nakedphp/sqlite/database.sqlite'
);

// These are required named variables (names can't change!)
$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

$globalArguments = array(
    'class-dir' => __DIR__ . '/../application/models'
);