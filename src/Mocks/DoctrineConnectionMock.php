<?php

declare(strict_types=1);

namespace Testbench\Mocks;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\Migrations\Exception\MigrationException;
use Nette\UnexpectedValueException;
use Symfony\Component\Console\Input\ArrayInput;

class DoctrineConnectionMock extends \Kdyby\Doctrine\Connection implements \Testbench\Providers\IDatabaseProvider
{
	private $__testbench_databaseName;

	public $onConnect = [];

	public function onConnect(self $self)
	{
		if (is_array($this->onConnect) || $this->onConnect instanceof \Traversable) {
			foreach ($this->onConnect as $handler) {
				$handler($self);
			}
		} elseif ($this->onConnect !== null) {
			throw new UnexpectedValueException('Property ' . static::class . '::$onConnect must be array or null, ' . gettype($this->onConnect) . ' given.');
		}
	}

	public function connect()
	{
		if (parent::connect()) {
			$this->onConnect($this);
		}
	}

	public function __construct(
		array $params,
		DBAL\Driver $driver,
		DBAL\Configuration $config = NULL,
		Common\EventManager $eventManager = NULL
	) {
		$container = \Testbench\ContainerFactory::create(FALSE);
		$this->onConnect[] = function (DoctrineConnectionMock $connection) use ($container) {
			if ($this->__testbench_databaseName !== NULL) { //already initialized (needed for pgsql)
				return;
			}
			try {
				$config = $container->parameters['testbench'];
				if ($config['shareDatabase'] === TRUE) {
					$registry = new \Testbench\DatabasesRegistry;
					$dbName = $container->parameters['testbench']['dbprefix'] . getenv(\Tester\Environment::THREAD);
					if ($registry->registerDatabase($dbName)) {
						$this->__testbench_database_setup($connection, $container, TRUE);
					} else {
						$this->__testbench_databaseName = $dbName;
						$this->__testbench_database_change($connection, $container);
					}
				} else { // always create new test database
					$this->__testbench_database_setup($connection, $container);
				}
			} catch (MigrationException $e) {
				//  do not throw an exception if there are no migrations
				if ($e->getCode() !== 4) {
					\Tester\Assert::fail($e->getMessage());
				}
			} catch (\Exception $e) {
				\Tester\Assert::fail($e->getMessage());
			}
		};
		parent::__construct($params, $driver, $config, $eventManager);
	}

	public function __testbench_database_setup($connection, \Nette\DI\Container $container, $persistent = FALSE)
	{
		$config = $container->parameters['testbench'];
		$this->__testbench_databaseName = $config['dbprefix'] . getenv(\Tester\Environment::THREAD);

		$this->__testbench_database_drop($connection, $container);
		$this->__testbench_database_create($connection, $container);

		foreach ($config['sqls'] as $file) {
			\Kdyby\Doctrine\Dbal\BatchImport\Helpers::loadFromFile($connection, $file);
		}

		if ($config['migrations'] === TRUE) {
			/** @var \Doctrine\Migrations\DependencyFactory $factory */
			$factory = $container->getByType(\Doctrine\Migrations\DependencyFactory::class, FALSE);
			if ($factory) {
				$factory->getMetadataStorage()->ensureInitialized();
				$aliasResolver = $factory->getVersionAliasResolver();
				$migrator = $factory->getMigrator();
				$calculator = $factory->getMigrationPlanCalculator();
				$plan = $calculator->getPlanUntilVersion($aliasResolver->resolveVersionAlias('latest'));
				$configurationFactory = $factory->getConsoleInputMigratorConfigurationFactory();
				$input = new ArrayInput([]);
				$migrator->migrate($plan, $configurationFactory->getMigratorConfiguration($input));
			}
		}

		if ($persistent === FALSE) {
			register_shutdown_function(function () use ($connection, $container) {
				$this->__testbench_database_drop($connection, $container);
			});
		}
	}

	public function compareMigrationNames($a, $b)
	{
		return strcmp($a->name, $b->name);
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_create($connection, \Nette\DI\Container $container)
	{
		$connection->exec("CREATE DATABASE {$this->__testbench_databaseName}");
		$this->__testbench_database_change($connection, $container);
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_change($connection, \Nette\DI\Container $container)
	{
		if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$connection->exec("USE {$this->__testbench_databaseName}");
		} else {
			$this->__testbench_database_connect($connection, $container, $this->__testbench_databaseName);
		}
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_drop($connection, \Nette\DI\Container $container)
	{
		if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$this->__testbench_database_connect($connection, $container);
		}
		$connection->exec("DROP DATABASE IF EXISTS {$this->__testbench_databaseName}");
	}

	/**
	 * @internal
	 *
	 * @param $connection \Kdyby\Doctrine\Connection
	 */
	public function __testbench_database_connect($connection, \Nette\DI\Container $container, $databaseName = NULL)
	{
		//connect to an existing database other than $this->_databaseName
		if ($databaseName === NULL) {
			$dbname = $container->parameters['testbench']['dbname'];
			if ($dbname) {
				$databaseName = $dbname;
			} elseif ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
				$databaseName = 'postgres';
			} else {
				throw new \LogicException('You should setup existing database name using testbench:dbname option.');
			}
		}

		$connection->close();
		$connection->__construct(
			['dbname' => $databaseName] + $connection->getParams(),
			$connection->getDriver(),
			$connection->getConfiguration(),
			$connection->getEventManager()
		);
		$connection->connect();
	}

}
