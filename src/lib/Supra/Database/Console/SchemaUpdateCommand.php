<?php

namespace Supra\Database\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Events;
use Supra\Database\Upgrade\DatabaseUpgradeRunner;
use Supra\Database\Upgrade\SqlUpgradeFile;
use Supra\Database\Exception;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Common\Cache\MemcacheCache;
use Supra\Database\Doctrine\Cache\ProxyFactoryMetadataCache;
use \Memcache;

/**
 * Schema update command
 *
 */
class SchemaUpdateCommand extends SchemaAbstractCommand
{

	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:schema:update')
				->setDescription('Updates ORM schema.')
				->setHelp('Updates ORM schema.')
				->setDefinition(array(
					new InputOption(
							'force', null, InputOption::VALUE_NONE,
							'Causes the generated SQL statements to be physically executed against your database.'
					),
					new InputOption(
							'dump-sql', null, InputOption::VALUE_NONE,
							'Causes the generated SQL statements to be output.'
					),
					new InputOption(
							'check', null, InputOption::VALUE_NONE,
							'Causes exception if schema is not up to date.'
					),
					
					new InputOption(
							'fix-collation', null, InputOption::VALUE_NONE,
							'Causes attempt to fix collation of non utf8 tables'
					),
				));
	}

	/**
	 * Execute command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output 
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{		
		$force = (true === $input->getOption('force'));
		$dumpSql = (true === $input->getOption('dump-sql'));
		$check = (true === $input->getOption('check'));
		$forceFixCollation = $force || (true === $input->getOption('fix-collation'));
		$updateRequired = false;

		// checking database collation
		$wrongCollations = $this->getWrongCollations();

		if ( ! empty($wrongCollations['tables'])) {
			$updateRequired = true;
		}
		
		$this->outputWrongCollations($output, $wrongCollations, $forceFixCollation);
		
		$output->writeln('Updating database schemas...');

		$output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');
		

		// Doctrine schema update
		foreach ($this->entityManagers as $entityManagerName => $em) {

			$output->write($entityManagerName);

			/* @var $metadataFactory ClassMetadataFactory */
			$metadataFactory = $em->getMetadataFactory();

			/* @var $cache ProxyFactoryMetadataCache */
			$cache = $metadataFactory->getCacheDriver();
			$cache->setWriteOnlyMode(true);

			$metadatas = $metadataFactory->getAllMetadata();
			$schemaTool = new SchemaTool($em);
			$sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);

			if ( ! empty($sqls)) {
				$updateRequired = true;
				$output->writeln("\t - " . count($sqls) . ' queries');

				if ($force) {
					$schemaTool->updateSchema($metadatas, true);
				}

				if ($dumpSql) {
					$output->writeln('');
					foreach ($sqls as $sql) {
						$output->writeln("\t" . $sql);
					}
					$output->writeln('');
				}
			} else {
				$output->writeln("\t - up to date");
			}

			$cache->setWriteOnlyMode(false);
		}

		if ($force) {
			$output->writeln('Database schemas updated successfully!');
		}

		if ($updateRequired && $check) {
			throw new Exception\RuntimeException('Database schema(s) not up to date.');
		}

		if ($updateRequired && ! $force && ! $dumpSql) {
			$output->writeln('');
			$output->writeln('Schema is not up to date.');
			$output->writeln('Please run the operation by passing one of the following options:');
			$output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getName()));
			$output->writeln(sprintf('    <info>%s --dump-sql</info> to show the commands', $this->getName()));
		}

		$output->writeln('Done updating database schemas.');
	}

	/**
	 * Outputs information about database/tables collation to console
	 * @param OutputInterface $output
	 * @param type $wrongCollations 
	 */
	protected function outputWrongCollations(OutputInterface $output, $wrongCollations = array(), $forceFixCollation = false)
	{
		// This script doesn't fix this, no warning then
//		if ( ! empty($wrongCollations['database'])) {
//			$output->writeln("<comment>Database has {$wrongCollations['database']} collation set as default. Default collation utf8_unicode_ci is recommended.</comment>");
//		}

		if ( ! empty($wrongCollations['tables'])) {
			$tables = $collations = array();

			foreach ($wrongCollations['tables'] as $row) {
				$tables[] = $row['table_name'];
				$collations[$row['table_collation']] = $row['table_collation'];
			}

			$tables = join(', ', $tables);
			$collations = join(', ', $collations);

			$output->writeln("<comment>Database tables:</comment>\n{$tables}\n<comment>has one of following collations:</comment>\n{$collations}\n<comment>All tables must use utf8_unicode_ci collation.</comment>");

			if ( ! $forceFixCollation) {
				$forceFixCollation = $this->askApproval($output, '<question>Database is not up to date. Do you want to update now? [y/N]</question> ');
			}

			if ($forceFixCollation) {
				$this->fixWrongCollations($output, $wrongCollations);
			}
		}
	}

	/**
	 * Returns information about database and table wrong collations
	 * 
	 * @return array 
	 */
	protected function getWrongCollations()
	{
		$output = array();
		$connection = $this->getHelper('db')->getConnection();
		/* @var $connection Doctrine\DBAL\Connection */
		$params = $connection->getParams();

		$statement = $connection->prepare('select table_name, table_collation from information_schema.tables where table_schema = :schema and table_collation NOT LIKE :collation');
		/* @var $statement Doctrine\DBAL\Statement */
		$status = $statement->bindValue(':schema', $params['dbname']);
		$status = $statement->bindValue(':collation', 'utf8_unicode_ci');
		$statement->execute();

		$tables = $statement->fetchAll(\PDO::FETCH_ASSOC);

		if ( ! empty($tables)) {
			$output['tables'] = $tables;
		}

		$tables = $statement->fetchAll(\PDO::FETCH_ASSOC);
		$statement = $connection->prepare('SELECT default_collation_name FROM information_schema.schemata where schema_name = :schema and default_collation_name NOT LIKE :collation');
		$status = $statement->bindValue(':schema', $params['dbname']);
		$status = $statement->bindValue(':collation', 'utf8_unicode_ci');
		$statement->execute();

		$database = $statement->fetch(\PDO::FETCH_COLUMN);

		if ( ! empty($database)) {
			$output['database'] = $database;
		}

		return $output;
	}
	
	/**
	 * Attemps to change collation of tables and columns to utf8
	 * 
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @param array $wrongCollations
	 */
	protected function fixWrongCollations(OutputInterface $output, array $wrongCollations)
	{
		$output->writeln('Updating tables collation to utf8_unicode_ci...');
		
		$connection = $this->getHelper('db')->getConnection();
		/* @var $connection \Doctrine\DBAL\Connection */
		
		$convertCount = 0;
		
		$connection->query('SET foreign_key_checks = 0')
				->execute();
		
		foreach($wrongCollations['tables'] as $tableData) {
				
			$tableName = $tableData['table_name'];
			
			$stmt = $connection->prepare("ALTER TABLE {$tableName} CHARACTER SET = :charset, COLLATE = :collation");
					
			$stmt->bindValue(':charset', 'utf8');
			$stmt->bindValue(':collation', 'utf8_unicode_ci');
			$stmt->execute();

			$stmt = $connection->prepare("ALTER TABLE {$tableName} CONVERT TO CHARACTER SET :charset COLLATE :collation");
			
			$stmt->bindValue(':charset', 'utf8');
			$stmt->bindValue(':collation', 'utf8_unicode_ci');
			$stmt->execute();

			$convertCount++;
		}
		
		$connection->query('SET foreign_key_checks = 1')
				->execute();
		
		$output->writeln("<info>{$convertCount} tables were converted successfully</info>");
	}

}
