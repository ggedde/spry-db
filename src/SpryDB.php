<?php

namespace Spry\SpryProvider;

use Spry\Spry;
use PDOException;
use Medoo\Medoo;
use Exception;
use PDO;

class SpryDB extends Medoo
{
	protected $migration = [
		'options' => [],
		'schema' => [],
		'logs' => []
	];

	public function __construct($options = null)
	{
		try {

			if(isset($options['schema']))
			{
				$this->migration['schema'] = $options['schema'];
			}

			parent::__construct($options);
		}
		catch (PDOException $e)
		{
			Spry::stop(5031, null, $e->getMessage());
		}
	}

	public function query($query, $map = [])
	{
		$query = parent::query($query, $map);

		if($this->hasError())
		{
			Spry::stop(5031, null, $this->errorMessage());
		}

		return $query;
	}

	public function exec($query, $map = [])
	{
		$exec = parent::exec($query, $map);

		if($this->hasError())
		{
			Spry::stop(5031, null, $this->errorMessage());
		}

		return $exec;
	}

	public function hasError()
	{
		$error = $this->error();
		
		if((isset($error[0]) && $error[0] > 0) || (isset($error[1]) && $error[1] > 0))
		{
			return true;
		}
		return false;
	}

	public function errorMessage()
	{
		$error = $this->error();

		if(!empty($error[2]))
		{
			return $error[2];
		}
		else if((isset($error[0]) && $error[0] > 0) || (isset($error[1]) && $error[1] > 0))
		{
			return 'Unknown';
		}

		return '';
	}

	public function migrate($args = array('destructive' => null, 'dryrun' => false))
	{
		// Defaults
		$options = array(
			'destructive' => false,
			'dryrun' => false
		);

		if(isset($args) && is_array($args))
		{
			// Set Defaults
			$options = array_merge($options, $args);
		}

		$this->migration['options'] = $options;

		if(empty($this->migration['schema']))
		{
			$this->migration['logs'][] = 'No Schema specified. Cannot Migrate.';
		}
		else
		{
			// Set Defaults
			if(!empty($this->migration['schema']['tables']))
			{
				foreach ($this->migration['schema']['tables'] as $table_name => $table)
				{
					// Set Default ID Column
					if(!isset($table['columns']['id']) && (!isset($table['use_id']) || !empty($table['use_id'])))
					{
						$column_id = [
							'type' => 'int',
							'primary' => 1,
							'auto_increment' => 1,
							'default' => 0
						];

						$this->migration['schema']['tables'][$table_name]['columns'] = array_merge(['id' => $column_id], $this->migration['schema']['tables'][$table_name]['columns']);
					}

					// Set Default Created On
					if(!isset($table['columns']['created_at']) && (!isset($table['timestamps']) || !empty($table['timestamps'])))
					{
						$this->migration['schema']['tables'][$table_name]['columns']['created_at'] = [
							'type' => 'datetime',
							'default_datetime' => 1,
						];
					}

					// Set Default Updated On
					if(!isset($table['columns']['updated_at']) && (!isset($table['timestamps']) || !empty($table['timestamps'])))
					{
						$this->migration['schema']['tables'][$table_name]['columns']['updated_at'] = [
							'type' => 'datetime',
							'default_datetime' => 1,
							'update_datetime' => 1,
						];
					}
				}
			}

			$this->migrateTablesDrop();
			$this->migrateTablesAdd();
			$this->migrateTablesUpdate();
		}

		if(empty($this->migration['logs']))
		{
			$this->migration['logs'][] = 'Nothing to Migrate.';
		}

		return $this->migration['logs'];
	}

	private function migrateCheckErrors($command='')
	{
		if($error = $this->errorMessage())
		{
			$this->migration['logs'][] = 'DB Error: ('.$command.') '.$error;
		}
	}

	private function migrateTablesGet()
	{
		$sql = 'SHOW TABLES';

		if($results = $this->query($sql))
		{
			$this->migrateCheckErrors($sql);
			return $results->fetchAll(PDO::FETCH_COLUMN);
		}

		$this->migrateCheckErrors($sql);

		return [];
	}

	private function migrateColumnsGet($table='', $names=false)
	{
		$sql = 'SHOW COLUMNS FROM '.$table;

		if($results = $this->query($sql))
		{
			$this->migrateCheckErrors($sql);
			return $results->fetchAll($names ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC);
		}

		$this->migrateCheckErrors($sql);

		return [];
	}

	private function migrateTablesDrop()
	{
		$current_tables = $this->migrateTablesGet();

		foreach ($current_tables as $table_name)
		{
			if(empty($this->migration['schema']['tables'][str_replace($this->prefix, '', $table_name)]))
			{
				$log_message = 'Dropped Table ['.$table_name.']';

				if($this->migration['options']['dryrun'])
				{
					$this->migration['logs'][] = '(DRYRUN): '.$log_message;
					continue;
				}

				if( ! $this->migration['options']['destructive'])
				{
					$this->migration['logs'][] = '(IGNORED DESTRUCTIVE): '.$log_message;
					continue;
				}

				$sql = 'DROP TABLE '.$table_name;
				$result = $this->exec($sql);

				if($result || $result === 0 || $result === '0')
				{
					$this->migration['logs'][] = $log_message;
				}
				else
				{
					$this->migration['logs'][] = 'ERROR: '.$log_message.' Reported an Error.';
				}

				$this->migrateCheckErrors($sql);

			}
		}
	}

	private function migrateTablesAdd()
	{
		$current_tables = $this->migrateTablesGet();

		foreach ($this->migration['schema']['tables'] as $table_name => $table)
		{
			$table_name = $this->prefix.$table_name;

			if(in_array($table_name, $current_tables))
			{
				// Table is already added so Skip
				continue;
			}

			$log_message = 'Created Table ['.$table_name.']';

			if(empty($table['columns']))
			{
				$this->migration['logs'][] = 'Error: '.$log_message.'. No Columns Specified.';
				continue;
			}

			if($this->migration['options']['dryrun'])
			{
				$this->migration['logs'][] = '(DRYRUN): '.$log_message;
				continue;
			}

			// Add Table
			$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name;

			$field_values = [];

			foreach ($table['columns'] as $field_name => $field)
			{
				$field_values[] = $field_name.' '.$this->migrateFieldValues($field);
			}

			$sql.= '('.implode(', ', $field_values).')';

			$this->exec($sql);

			if(in_array($table_name, $this->migrateTablesGet()))
			{
				$this->migration['logs'][] = $log_message;
			}
			else
			{
				$this->migration['logs'][] = 'Error: '.$log_message.'. Reported an Error.';
			}

			$this->migrateCheckErrors($sql);
		}
	}

	private function migrateTablesUpdate()
	{
		$current_tables = $this->migrateTablesGet();

		foreach ($this->migration['schema']['tables'] as $table_name => $table)
		{
			if(!in_array($this->prefix.$table_name, $current_tables))
			{
				// Skip Tables that don't exist yet
				continue;
			}

			$fields = $this->migrateColumnsGet($this->prefix.$table_name);

			// Add Fields
			if( ! empty($this->migration['schema']['tables'][$table_name]['columns']))
			{
				foreach ($this->migration['schema']['tables'][$table_name]['columns'] as $field_name => $field)
				{
					if( ! in_array($field_name, $this->migrateColumnsGet($this->prefix.$table_name, true)))
					{
						$log_message = 'Created Column ['.$this->prefix.$table_name.'.'.$field_name.']';

						if($this->migration['options']['dryrun'])
						{
							$this->migration['logs'][] = '(DRYRUN): '.$log_message;
							continue;
						}

						$sql = 'ALTER TABLE '.$this->prefix.$table_name.' ADD COLUMN '.$field_name.' '.$this->migrateFieldValues($field);

						$result = $this->exec($sql);

						if($result || $result === 0 || $result === '0')
						{
							$this->migration['logs'][] = $log_message;
						}
						else
						{
							$this->migration['logs'][] = 'Error: '.$log_message.'. Reported an Error.';
						}

						$this->migrateCheckErrors($sql);
					}
				}
			}

			// Delete Fields
			if(!empty($fields))
			{
				foreach ($fields as $field)
				{
					if(isset($field['Field']) && !isset($this->migration['schema']['tables'][$table_name]['columns'][$field['Field']]))
					{
						$log_message = 'Dropped Column ['.$this->prefix.$table_name.'.'.$field['Field'].']';

						if($this->migration['options']['dryrun'])
						{
							$this->migration['logs'][] = '(DRYRUN): '.$log_message;
							continue;
						}

						if( ! $this->migration['options']['destructive'])
						{
							$this->migration['logs'][] = '(IGNORED DESTRUCTIVE): '.$log_message;
							continue;
						}

						$sql = 'ALTER TABLE '.$this->prefix.$table_name.' DROP COLUMN '.$field['Field'];

						$result = $this->exec($sql);

						if($result || $result === 0 || $result === '0')
						{
							$this->migration['logs'][] = $log_message;
						}
						else
						{
							$this->migration['logs'][] = 'Error: '.$log_message.'. Reported an Error.';
						}

						$this->migrateCheckErrors($sql);
					}
				}
			}

			// Update Fields
			#TODO
		}
	}

	private function migrateFieldType($field=[])
	{
		$type = isset($field['type']) ? $field['type'] : 'string';

		if(!empty($field['options']))
		{
			$type = 'options';
		}

		switch ($type)
		{
			case 'int':

				return 'int(10)';

			break;

			case 'bool':
			case 'boolean':

				return 'tinyint(1)';

			break;

			case 'number':

				return 'float';

			break;

			case 'options':
			case 'enum':

				$options = !empty($field['options']) ? $field['options'] : [];

				return "enum('".implode("','", $options)."')";

			break;

			case 'datetime':

				return 'datetime';

			break;

			case 'timestamp':

				return 'timestamp';

			break;

			case 'text':

				return 'text';

			break;

			case 'longtext':

				return 'longtext';

			break;

			case 'longstring':

				return 'varchar(255)';

			break;

			case 'string':

				return 'varchar(64)';

			break;
		}

		return $type;
	}

	private function migrateFieldValues($field=[])
	{
		$field_values = [];

		$default = [
			'type' => 'string',
			'default' => ''
		];

		$field = array_merge($default, $field);

		$field_values[] = $this->migrateFieldType($field);

		// Primary Key
		if(!empty($field['primary']))
		{
			$field_values[] = 'PRIMARY KEY';
		}

		// Auto Increment
		if(!empty($field['auto_increment']))
		{
			$field_values[] = 'AUTO_INCREMENT';
		}

		// Unique
		if(!empty($field['unique']))
		{
			$field_values[] = 'UNIQUE KEY';
		}

		// Null
		if(!empty($field['null']))
		{
			$field_values[] = 'NULL';
		}
		else
		{
			$field_values[] = 'NOT NULL';
		}

		// Default
		if(!empty($field['default_datetime']))
		{
			$field_values[] = 'DEFAULT CURRENT_TIMESTAMP';
		}
		elseif(!empty($field['default']))
		{
			$field_values[] = "DEFAULT '".$field['default']."'";
		}

		// Extra
		if(!empty($field['update_datetime']))
		{
			$field_values[] = 'ON UPDATE CURRENT_TIMESTAMP';
		}

		return implode(' ', $field_values);

	}

}
