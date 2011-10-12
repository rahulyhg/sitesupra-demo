<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Supra\Database\Doctrine;
use Doctrine\Common\Cache\ArrayCache;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\Events;
use Doctrine\Common\EventManager;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\Database\Doctrine\Listener\TableNameGenerator;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\Controller\Pages\Listener;
use Doctrine\DBAL\Types\Type;
use Supra\Database\Doctrine\Type\Sha1HashType;
use Supra\Database\Doctrine\Type\PathType;

Type::addType(Sha1HashType::NAME, 'Supra\Database\Doctrine\Type\Sha1HashType');
Type::addType(PathType::NAME, 'Supra\Database\Doctrine\Type\PathType');

// TODO: use configuration classes maybe?
$managerNames = array(
	'PublicSchema' => '',
	'Draft' => 'Supra\Cms',
	'Trash' => 'Supra\Cms\Abstraction\Trash',
	'History' => 'Supra\Cms\Abstraction\History',
);

foreach ($managerNames as $managerName => $namespace) {
	$config = new Configuration();

	// Doctrine cache (array cache for development)
	$cache = new ArrayCache();

	// Memcache cache configuration sample
	//$cache = new \Doctrine\Common\Cache\MemcacheCache();
	//$memcache = new \Memcache();
	//$memcache->addserver('127.0.0.1');
	//$cache->setMemcache($memcache);

	//NB! Must have different namespace for draft connection
	$cache->setNamespace($managerName);
	$config->setMetadataCacheImpl($cache);
	$config->setQueryCacheImpl($cache);

	// Metadata driver
	$entityPaths = array(
		SUPRA_LIBRARY_PATH . 'Supra/Controller/Pages/Entity/',
		SUPRA_LIBRARY_PATH . 'Supra/FileStorage/Entity/',
		SUPRA_LIBRARY_PATH . 'Supra/User/Entity/',
		SUPRA_LIBRARY_PATH . 'Supra/Console/Cron/Entity/',
	);
	$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
	//$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(SUPRA_LIBRARY_PATH . 'Supra/yaml/');
	$config->setMetadataDriverImpl($driverImpl);

	// Proxy configuration
	$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy/' . $managerName);
	$config->setProxyNamespace('Supra\\Proxy\\' . $managerName);
	$config->setAutoGenerateProxyClasses(true);

	// SQL logger
	$sqlLogger = new \Supra\Log\Logger\SqlLogger();
	$config->setSQLLogger($sqlLogger);

	$connectionOptions = $ini['database'];

	// TODO: Let's see if it is still required with MySQL PDO charset updates in PHP 5.3.6
	$connectionOptions['driverOptions'] = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
	);

	// TODO: move to some other configuration
	$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

	$eventManager = new EventManager();
	$eventManager->addEventListener(array(Events::loadClassMetadata), new TableNameGenerator());
	
	switch ($managerName) {
		case 'PublicSchema':
			$eventManager->addEventListener(array(Events::onFlush), new Listener\PagePathGenerator());
			$eventManager->addEventListener(array(Events::prePersist, Events::postLoad), new NestedSetListener());
			$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\VersionedTableLockIdRemover());
			break;
		
		case 'Draft':
			$eventManager->addEventListener(array(Events::onFlush), new Listener\PagePathGenerator());
			$eventManager->addEventListener(array(Events::prePersist, Events::postLoad), new NestedSetListener());
			$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\TableDraftPrefixAppender());
			$eventManager->addEventListener(array(Events::onFlush), new Listener\ImageSizeCreatorListener());
			break;
		
		case 'Trash':
			$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\VersionedTableLockIdRemover());
			$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\TableTrashPrefixAppender());
			$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\TrashTableIdChange());
			break;
		
		case 'History':
			$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\VersionedTableLockIdRemover());
			$eventManager->addEventListener(array(Events::loadClassMetadata, Events::prePersist, Events::postLoad), new Listener\HistorySchemaModifier());
			break;
	}

	$em = EntityManager::create($connectionOptions, $config, $eventManager);
	$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));
	$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(Sha1HashType::NAME));
	$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(PathType::NAME));
	$em->_mode = $managerName;

	ObjectRepository::setEntityManager($namespace, $em);
}
