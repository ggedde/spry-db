<?php

/**
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

namespace Spry\SpryProvider;

use PDO;
use Spry\Spry;
use Medoo\Medoo;
use PDOException;

/**
 *
 *  Database Class for Spry
 *
 */

class SpryDB extends Medoo
{
    protected $migration = [
        'options' => [],
        'schema' => [],
        'logs' => [],
    ];



    protected $dbMeta = [];


    /**
     * DB constructor
     *
     * @param mixed $options
     *
     * @access public
     *
     * @return void
     */
    public function __construct($options = null)
    {
        try {
            if (isset($options['schema'])) {
                $this->migration['schema'] = $options['schema'];
            }

            parent::__construct($options);
        } catch (PDOException $e) {
            Spry::stop(31, null, null, null, $e->getMessage());
        }
    }


     /**
     * Sets Meta settings and returns the DB Object
     *
     * @param array $meta
     *
     * @access public
     *
     * @return void
     */
    public function meta($meta = [])
    {
        $this->dbMeta = $meta;

        return $this;
    }



    /**
     * Query Call to Medoo Parent
     *
     * @param string $query
     * @param array  $map
     *
     * @access public
     *
     * @return mixed
     */
    public function query($query, $map = [])
    {
        $query = parent::query($query, $map);

        if ($this->hasError()) {
            Spry::stop(31, null, null, null, $this->errorMessage().' - SQLCode: ('.$this->errorCode().')');
        }

        return $query;
    }



    /**
     * Exec Call to Medoo Parent
     *
     * @param string $query
     * @param array  $map
     *
     * @access public
     *
     * @return mixed
     */
    public function exec($query, $map = [])
    {
        $exec = parent::exec($query, $map);

        if ($this->hasError()) {
            Spry::stop(31, null, null, null, $this->errorMessage().'  - SQLCode: ('.$this->errorCode().') - '.$this->last());
        }

        return $exec;
    }



    /**
     * Gets a single row from the Database
     * See Medoo for full instructions:
     * https://medoo.in/api/get
     *
     * @param string     $table
     * @param array|null $join
     * @param array|null $columns
     * @param array|null $where
     *
     * @access public
     *
     * @return mixed
     */
    public function get($table, $join = null, $columns = null, $where = null)
    {
        $getObj = $this->buildSelectObj('get', $table, $join, $columns, $where);
        $getObj = Spry::runFilter('dbGet', $getObj);

        return parent::get($getObj->table, $getObj->join, $getObj->columns, $getObj->where);
    }



     /**
     * Gets rows from the Database
     * See Medoo for full instructions:
     * https://medoo.in/api/select
     *
     * @param string     $table
     * @param array|null $join
     * @param array|null $columns
     * @param array|null $where
     *
     * @access public
     *
     * @return mixed
     */
    public function select($table, $join, $columns = null, $where = null)
    {
        $selectObj = $this->buildSelectObj('select', $table, $join, $columns, $where);
        $selectObj = Spry::runFilter('dbSelect', $selectObj);

        return parent::select($selectObj->table, $selectObj->join, $selectObj->columns, $selectObj->where);
    }


    /**
     * Insert into the Database
     * See Medoo for full instructions:
     * https://medoo.in/api/insert
     *
     * @param string $table
     * @param array  $data
     *
     * @access public
     *
     * @return boolean
     */
    public function insert($table, $data)
    {
        if (Spry::isTest()) {
            $datas['test_data'] = 1;
        }

        $data = Spry::runFilter('dbData', $data, (object) ['method' => 'insert', 'table' => $table, 'meta' => $this->dbMeta]);

        $insertObj = Spry::runFilter('dbInsert', (object) ['table' => $table, 'data' => $data, 'meta' => $this->dbMeta]);

        return parent::insert($insertObj->table, $insertObj->data)->rowCount() ? true : null;
    }



    /**
     * Update rows the Database
     * See Medoo for full instructions:
     * https://medoo.in/api/update
     *
     * @param string $table
     * @param array  $data
     * @param array  $where
     *
     * @access public
     *
     * @return boolean
     */
    public function update($table, $data, $where = null)
    {
        if (Spry::isTest()) {
            $where['test_data'] = 1;
        }

        $data = Spry::runFilter('dbData', $data, (object) ['method' => 'update', 'table' => $table, 'meta' => $this->dbMeta]);
        $where = Spry::runFilter('dbWhere', $where, (object) ['method' => 'update', 'table' => $table, 'meta' => $this->dbMeta]);

        $updateObj = Spry::runFilter('dbUpdate', (object) ['table' => $table, 'data' => $data, 'where' => $where, 'meta' => $this->dbMeta]);

        $update = parent::update($updateObj->table, $updateObj->data, $updateObj->where)->rowCount();

        return ($update || ($update === 0 && !$this->hasError())) ? true : null;
    }



    /**
     * Delete row from the Database
     * See Medoo for full instructions:
     * https://medoo.in/api/insert
     *
     * @param string $table
     * @param array  $where
     *
     * @access public
     *
     * @return boolean
     */
    public function delete($table, $where)
    {
        if (Spry::isTest()) {
            $where['test_data'] = 1;
        }

        $where = Spry::runFilter('dbWhere', $where, (object) ['method' => 'delete', 'table' => $table, 'meta' => $this->dbMeta]);

        $deleteObj = Spry::runFilter('dbDelete', (object) ['table' => $table, 'where' => $where, 'meta' => $this->dbMeta]);

        return parent::delete($deleteObj->table, $deleteObj->where)->rowCount() ? true : null;
    }



    /**
     * Replace row data in the Database
     * See Medoo for full instructions:
     * https://medoo.in/api/replace
     *
     * @param string $table
     * @param array  $columns
     * @param array  $where
     *
     * @access public
     *
     * @return boolean
     */
    public function replace($table, $columns, $where = null)
    {
        $columns = Spry::runFilter('dbColumns', $columns, (object) ['method' => 'replace', 'table' => $table, 'meta' => $this->dbMeta]);
        $where = Spry::runFilter('dbWhere', $where, (object) ['method' => 'replace', 'table' => $table, 'meta' => $this->dbMeta]);

        $replaceObj = Spry::runFilter('dbReplace', (object) ['table' => $table, 'columns' => $columns, 'where' => $where, 'meta' => $this->dbMeta]);

        $replace = parent::replace($replaceObj->table, $replaceObj->columns, $replaceObj->where)->rowCount();

        return ($replace || ($replace === 0 && !$this->hasError())) ? true : null;
    }



    /**
     * See if table has data
     * See Medoo for full instructions:
     * https://medoo.in/api/has
     *
     * @param string $table
     * @param array  $join
     * @param array  $where
     *
     * @access public
     *
     * @return boolean
     */
    public function has($table, $join, $where = null)
    {
        $hasObj = $this->buildHasObj($table, $join, $where);
        $hasObj = Spry::runFilter('dbHas', $hasObj);

        return parent::has($hasObj->table, $hasObj->join, $hasObj->where);
    }



    /**
     * Get Random Data
     * See Medoo for full instructions:
     * https://medoo.in/api/rand
     *
     * @param string $table
     * @param array  $join
     * @param array  $columns
     * @param array  $where
     *
     * @access public
     *
     * @return array
     */
    public function rand($table, $join = null, $columns = null, $where = null)
    {
        $randObj = $this->buildSelectObj($table, $join, $columns, $where);
        $randObj = Spry::runFilter('dbRand', $randObj);

        return parent::rand($randObj->table, $randObj->join, $randObj->columns, $randObj->where);
    }



    /**
     * Count Data from the Table
     * See Medoo for full instructions:
     * https://medoo.in/api/count
     *
     * @param string $table
     * @param array  $join
     * @param array  $column
     * @param array  $where
     *
     * @access public
     *
     * @return array
     */
    public function count($table, $join = null, $column = null, $where = null)
    {
        $countObj = $this->buildSelectObj($table, $join, $column, $where, true);
        $countObj = Spry::runFilter('dbCount', $countObj);

        return parent::count($countObj->table, $countObj->join, $countObj->column, $countObj->where);
    }



    /**
     * Get Avg from a column in the Table
     * See Medoo for full instructions:
     * https://medoo.in/api/avg
     *
     * @param string $table
     * @param array  $join
     * @param array  $column
     * @param array  $where
     *
     * @access public
     *
     * @return array
     */
    public function avg($table, $join, $column = null, $where = null)
    {
        $avgObj = $this->buildSelectObj($table, $join, $column, $where, true);
        $avgObj = Spry::runFilter('dbAvg', $avgObj);

        return parent::avg($avgObj->table, $avgObj->join, $avgObj->column, $avgObj->where);
    }



    /**
     * Ge the Max
     * See Medoo for full instructions:
     * https://medoo.in/api/max
     *
     * @param string $table
     * @param array  $join
     * @param array  $column
     * @param array  $where
     *
     * @access public
     *
     * @return array
     */
    public function max($table, $join, $column = null, $where = null)
    {
        $maxObj = $this->buildSelectObj($table, $join, $column, $where, true);
        $maxObj = Spry::runFilter('dbMax', $maxObj);

        return parent::max($maxObj->table, $maxObj->join, $maxObj->column, $maxObj->where);
    }



    /**
     * Ge the Min
     * See Medoo for full instructions:
     * https://medoo.in/api/min
     *
     * @param string $table
     * @param array  $join
     * @param array  $column
     * @param array  $where
     *
     * @access public
     *
     * @return array
     */
    public function min($table, $join, $column = null, $where = null)
    {
        $minObj = $this->buildSelectObj($table, $join, $column, $where, true);
        $minObj = Spry::runFilter('dbMin', $minObj);

        return parent::min($minObj->table, $minObj->join, $minObj->column, $minObj->where);
    }



    /**
     * Ge the Sum
     * See Medoo for full instructions:
     * https://medoo.in/api/sum
     *
     * @param string $table
     * @param array  $join
     * @param array  $column
     * @param array  $where
     *
     * @access public
     *
     * @return array
     */
    public function sum($table, $join, $column = null, $where = null)
    {
        $sumObj = $this->buildSelectObj($table, $join, $column, $where, true);
        $sumObj = Spry::runFilter('dbSum', $sumObj);

        return parent::sum($sumObj->table, $sumObj->join, $sumObj->column, $sumObj->where);
    }



    /**
     * Return Results
     * See Medoo for full instructions:
     * https://medoo.in/api/doc
     *
     * @access public
     *
     * @return mixed
     */
    public function results()
    {
        return $this->statement;
    }



    /**
     * Check if last DB Call had an Error
     *
     * @access public
     *
     * @return boolean
     */
    public function hasError()
    {
        $error = $this->error();

        if ((isset($error[0]) && $error[0] > 0) || (isset($error[1]) && $error[1] > 0)) {
            return true;
        }

        return false;
    }



    /**
     * Get Error Messages from last DB request
     *
     * @access public
     *
     * @return mixed
     */
    public function errorMessage()
    {
        $error = $this->error();

        if (!empty($error[2]) || (isset($error[0]) && $error[0] > 0) || (isset($error[1]) && $error[1] > 0)) {
            return !empty($error[2]) ? $error[2] : 'Unknown';
        }

        return '';
    }



    /**
     * Get the Error Code from last DB Call
     *
     * @access public
     *
     * @return int
     */
    public function errorCode()
    {
        $error = $this->error();

        if (!empty($error[1]) || !empty($error[0])) {
            return !empty($error[0]) ? intval($error[0]) : intval($error[1]);
        }

        return 0;
    }



    /**
     * Deletes all data in the Database with test_data = 1
     *
     * @access public
     *
     * @return boolean
     */
    public function deleteTestData()
    {
        foreach ($this->migrateTablesGet() as $tableName) {
            parent::delete(str_replace($this->prefix, '', $tableName), ['test_data' => 1]);
        }

        return true;
    }



    /**
     * Migrates the Database Schema from the Spry Config Schema
     *
     * @param array $args
     *
     * @access public
     *
     * @return mixed
     */
    public function migrate($args = array('force' => null, 'dryrun' => false, 'debug' => false))
    {
        // Defaults
        $options = array(
            'force' => false,
            'dryrun' => false,
            'debug' => false,
        );

        if (isset($args) && is_array($args)) {
            // Set Defaults
            $options = array_merge($options, $args);
        }

        $this->migration['options'] = $options;

        if (empty($this->migration['schema'])) {
            $this->migration['logs'][] = 'No Schema specified. Cannot Migrate.';
        } else {
            // Set Defaults
            if (!empty($this->migration['schema']['tables'])) {
                foreach ($this->migration['schema']['tables'] as $tableName => $table) {
                    // Set Default ID Column
                    if (!isset($table['columns']['id']) && (!isset($table['use_id']) || !empty($table['use_id']))) {
                        $columnId = [
                            'type' => 'int',
                            'primary' => 1,
                            'auto_increment' => 1,
                            'default' => '',
                        ];

                        $this->migration['schema']['tables'][$tableName]['columns'] = array_merge(['id' => $columnId], $this->migration['schema']['tables'][$tableName]['columns']);
                    }

                    // Set Default Test Data
                    if (!isset($table['columns']['test_data']) && (!isset($table['test_data']) || !empty($table['test_data']))) {
                        $this->migration['schema']['tables'][$tableName]['columns']['test_data'] = [
                            'type' => 'tinyint',
                            'default' => 0,
                        ];
                    }

                    // Set Default Created On
                    if (!isset($table['columns']['created_at']) && (!isset($table['timestamps']) || !empty($table['timestamps']))) {
                        $this->migration['schema']['tables'][$tableName]['columns']['created_at'] = [
                            'type' => 'datetime',
                            'default' => 'now',
                        ];
                    }

                    // Set Default Updated On
                    if (!isset($table['columns']['updated_at']) && (!isset($table['timestamps']) || !empty($table['timestamps']))) {
                        $this->migration['schema']['tables'][$tableName]['columns']['updated_at'] = [
                            'type' => 'datetime',
                            'default' => 'now',
                            'update' => 'now',
                        ];
                    }
                }
            }

            $this->migrateTablesDrop();
            $this->migrateTablesAdd();
            $this->migrateTablesUpdate();
        }

        if (empty($this->migration['logs'])) {
            $this->migration['logs'][] = 'Nothing to Migrate.';
        }

        return $this->migration['logs'];
    }



    /**
     * Updates the Migrate Errors
     *
     * @param string $command
     *
     * @access private
     *
     * @return void
     */
    private function migrateCheckErrors($command = '')
    {
        if ($error = $this->errorMessage()) {
            $this->migration['logs'][] = 'DB Error: ('.$command.') '.$error;
        }
    }



    /**
     * Returns Table list
     *
     * @access private
     *
     * @return array
     */
    private function migrateTablesGet()
    {
        $sql = 'SHOW TABLES';

        if ($results = $this->query($sql)) {
            $this->migrateCheckErrors($sql);

            return $results->fetchAll(PDO::FETCH_COLUMN);
        }

        $this->migrateCheckErrors($sql);

        return [];
    }



    /**
     * Filters the Select arguments
     * See Medoo for full instructions:
     * https://medoo.in/api/select
     *
     * @param string     $method
     * @param string     $table
     * @param array|null $join
     * @param array|null $columns
     * @param array|null $where
     * @param boolean    $isAggregate
     *
     * @return object
     */
    private function buildSelectObj($method, $table, $join = null, $columns = null, $where = null, $isAggregate = false)
    {
        $joinKey = is_array($join) ? array_keys($join) : null;

        if (is_null($join) || isset($joinKey[0]) && strpos(trim($joinKey[0]), '[') === 0) {
            $join = Spry::runFilter('dbJoin', $join, (object) ['method' => 'has', 'table' => $table, 'meta' => $this->dbMeta]);
        }

        $joinKey = is_array($join) ? array_keys($join) : null;
        $columnKey = is_array($columns) ? array_keys($columns) : null;
        $isJoin = false;
        if (isset($joinKey[0]) && strpos(trim($joinKey[0]), '[') === 0) {
            $isJoin = true;
        }

        $newArgs = [
            'method' => $method,
            'table' => $table,
            'meta' => $this->dbMeta,
        ];

        if (!$isJoin && isset($joinKey[0]) && is_string($joinKey[0])) { // Join is where
            $join = Spry::runFilter('dbWhere', $join, (object) ['method' => $method, 'table' => $table, 'meta' => $this->dbMeta]);
        } elseif (isset($columnKey[0]) && is_string($columnKey[0])) { // columns is where
            $columns = Spry::runFilter('dbWhere', $columns, $newArgs);
        }

        if (!$isJoin && (is_string($join) || (isset($joinKey[0]) && is_int($joinKey[0])))) { // Join is Columns
            $join = Spry::runFilter('dbColumns', $join, (object) ['method' => $method, 'table' => $table, 'meta' => $this->dbMeta]);

            if (is_array($columns) && empty($columns)) { // columns is where
                $columns = Spry::runFilter('dbWhere', $columns, (object) ['method' => $method, 'table' => $table, 'meta' => $this->dbMeta]);
            }
        }

        if (isset($columns['test_data'])) {
            unset($columns['test_data']);
        }

        if (isset($where['test_data'])) {
            unset($where['test_data']);
        }

        return (object) ['table' => $table, 'join' => $join, ($isAggregate ? 'column' : 'columns') => $columns, 'where' => $where, 'meta' => $this->dbMeta];
    }



    /**
     * Filters the Select arguments
     * See Medoo for full instructions:
     * https://medoo.in/api/select
     *
     * @param string     $table
     * @param array|null $join
     * @param array|null $where
     *
     * @return object
     */
    private function buildHasObj($table, $join = null, $where = null)
    {
        $joinKey = is_array($join) ? array_keys($join) : null;
        $isJoin = false;
        if (isset($joinKey[0]) && strpos(trim($joinKey[0]), '[') === 0) {
            $isJoin = true;
        }

        if (!is_null($join) && !$isJoin) {
            $join = Spry::runFilter('dbWhere', $join, (object) ['method' => 'has', 'table' => $table, 'meta' => $this->dbMeta]);
        } else {
            $where = Spry::runFilter('dbWhere', $where, (object) ['method' => 'has', 'table' => $table, 'meta' => $this->dbMeta]);
            $join = Spry::runFilter('dbJoin', $join, (object) ['method' => 'has', 'table' => $table, 'meta' => $this->dbMeta]);
        }

        if (isset($where['test_data'])) {
            unset($where['test_data']);
        }

        return (object) ['table' => $table, 'join' => $join, 'where' => $where, 'meta' => $this->dbMeta];
    }



    /**
     * Gets all columns from a Table
     *
     * @param string  $table
     * @param boolean $names
     *
     * @access private
     *
     * @return array
     */
    private function migrateColumnsGet($table = '', $names = false)
    {
        $sql = 'SHOW COLUMNS FROM '.$table;

        if ($results = $this->query($sql)) {
            $this->migrateCheckErrors($sql);

            return $results->fetchAll($names ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC);
        }

        $this->migrateCheckErrors($sql);

        return [];
    }



    /**
     * Drops all removed Tables from Migration
     *
     * @access private
     *
     * @return void
     */
    private function migrateTablesDrop()
    {
        $currentTables = $this->migrateTablesGet();

        foreach ($currentTables as $tableName) {
            if (empty($this->migration['schema']['tables'][str_replace($this->prefix, '', $tableName)])) {
                $logMessage = 'Dropped Table ['.$tableName.']';

                $sql = 'DROP TABLE '.$tableName;

                if ($this->migration['options']['debug']) {
                    $this->migration['logs'][] = '(DEBUG): '.$logMessage;
                    $this->migration['logs'][] = '(SQL): '.($this->type === 'mysql' ? str_replace('"', '`', $sql) : $sql);
                    continue;
                }

                if ($this->migration['options']['dryrun']) {
                    $this->migration['logs'][] = '(DRYRUN): '.$logMessage;
                    continue;
                }

                if (!$this->migration['options']['force']) {
                    $this->migration['logs'][] = '(IGNORED DESTRUCTIVE): '.$logMessage;
                    continue;
                }

                $result = $this->exec($sql);

                if ($result || 0 === $result || strval($result) === '0') {
                    $this->migration['logs'][] = $logMessage;
                } else {
                    $this->migration['logs'][] = 'ERROR: '.$logMessage.' Reported an Error.';
                }

                $this->migrateCheckErrors($sql);
            }
        }
    }



    /**
     * Adds table to the Database from Migration
     *
     * @access public
     *
     * @return boolean
     */
    private function migrateTablesAdd()
    {
        $currentTables = $this->migrateTablesGet();

        foreach ($this->migration['schema']['tables'] as $tableName => $table) {
            $tableName = $this->prefix.$tableName;

            if (in_array($tableName, $currentTables)) {
                // Table is already added so Skip
                continue;
            }

            $logMessage = 'Created Table ['.$tableName.']';

            // Add Table Sql
            $sql = 'CREATE TABLE IF NOT EXISTS '.$tableName;

            $fieldValues = [];

            foreach ($table['columns'] as $fieldName => $field) {
                $fieldValues[] = '"'.$fieldName.'" '.$this->migrateFieldValues($field);
            }

            $sql .= ' ('.implode(', ', $fieldValues).')';

            if ($this->migration['options']['debug']) {
                $this->migration['logs'][] = '(DEBUG): '.$logMessage;
                $this->migration['logs'][] = '(SQL): '.($this->type === 'mysql' ? str_replace('"', '`', $sql) : $sql);
                continue;
            }

            if (empty($table['columns'])) {
                $this->migration['logs'][] = 'Error: '.$logMessage.'. No Columns Specified.';
                continue;
            }

            if ($this->migration['options']['dryrun']) {
                $this->migration['logs'][] = '(DRYRUN): '.$logMessage;
                continue;
            }

            $this->exec($sql);

            if (in_array($tableName, $this->migrateTablesGet())) {
                $this->migration['logs'][] = $logMessage;
            } else {
                $this->migration['logs'][] = 'Error: '.$logMessage.'. Reported an Error.';
            }

            $this->migrateCheckErrors($sql);
        }
    }



    /**
     * Updates all tables from Migration
     *
     * @access private
     *
     * @return boolean
     */
    private function migrateTablesUpdate()
    {
        $currentTables = $this->migrateTablesGet();

        foreach ($this->migration['schema']['tables'] as $tableName => $table) {
            if (!in_array($this->prefix.$tableName, $currentTables)) {
                // Skip Tables that don't exist yet
                continue;
            }

            $fields = $this->migrateColumnsGet($this->prefix.$tableName);

            // Add Fields
            if (!empty($this->migration['schema']['tables'][$tableName]['columns'])) {
                foreach ($this->migration['schema']['tables'][$tableName]['columns'] as $fieldName => $field) {
                    if (!in_array($fieldName, $this->migrateColumnsGet($this->prefix.$tableName, true))) {
                        $logMessage = 'Created Column ['.$this->prefix.$tableName.'.'.$fieldName.']';
                        $sql = 'ALTER TABLE '.$this->prefix.$tableName.' ADD COLUMN "'.$fieldName.'" '.$this->migrateFieldValues($field);

                        if ($this->migration['options']['debug']) {
                            $this->migration['logs'][] = '(DEBUG): '.$logMessage;
                            $this->migration['logs'][] = '(SQL): '.($this->type === 'mysql' ? str_replace('"', '`', $sql) : $sql);
                            continue;
                        }

                        if ($this->migration['options']['dryrun']) {
                            $this->migration['logs'][] = '(DRYRUN): '.$logMessage;
                            continue;
                        }

                        $result = $this->exec($sql);

                        if ($result || 0 === $result || strval($result) === '0') {
                            $this->migration['logs'][] = $logMessage;
                        } else {
                            $this->migration['logs'][] = 'Error: '.$logMessage.'. Reported an Error.';
                        }

                        $this->migrateCheckErrors($sql);
                    }
                }
            }

            // Update Unique Indexes
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $schemaField = (!empty($this->migration['schema']['tables'][$tableName]['columns'][$field['Field']]) ? $this->migration['schema']['tables'][$tableName]['columns'][$field['Field']] : []);

                    //$schemaField = $this->migration['schema']['tables'][$tableName]['columns'][$field['Field']];
                    if (!isset($schemaField['unique'])) {
                        $schemaField['unique'] = [];
                    }

                    if (!empty($schemaField['unique']) && !is_array($schemaField['unique'])) {
                        $schemaField['unique'] = [$field['Field']];
                    }

                    if (!empty($schemaField['unique']) && is_array($schemaField['unique']) && !in_array($field['Field'], $schemaField['unique'])) {
                        array_unshift($schemaField['unique'], $field['Field']);
                    }

                    $uniqueFields = [];

                    $sql = 'SHOW INDEX FROM '.$this->prefix.$tableName." WHERE Key_name = '".$field['Field']."'";

                    if ($result = $this->exec($sql)) {
                        foreach ($result->fetchAll() as $row) {
                            $uniqueFields[] = $row['Column_name'];
                        }
                    }

                    if (implode(',', $uniqueFields) !== implode(',', $schemaField['unique'])) {
                        $logMessage = 'Update Unique Index ['.$this->prefix.$tableName.'.'.$field['Field'].']';

                        if (!empty($uniqueFields)) {
                            $sql = 'ALTER TABLE '.$this->prefix.$tableName.' DROP INDEX "'.$field['Field'].'"';
                            $dropResult = $this->exec($sql);
                        }

                        if (!empty($schemaField['unique'])) {
                            $sql = 'ALTER TABLE '.$this->prefix.$tableName.' ADD UNIQUE KEY "'.$field['Field'].'" ("'.implode('","', $schemaField['unique']).'")';
                            $addResult = $this->exec($sql);
                        }

                        if ($this->migration['options']['debug']) {
                            $this->migration['logs'][] = '(DEBUG): '.$logMessage;
                            $this->migration['logs'][] = '(SQL): '.($this->type === 'mysql' ? str_replace('"', '`', $sql) : $sql);
                            continue;
                        }

                        if ($this->migration['options']['dryrun']) {
                            $this->migration['logs'][] = '(DRYRUN): '.$logMessage;
                            continue;
                        }

                        if (!$this->migration['options']['force']) {
                            $this->migration['logs'][] = '(IGNORED DESTRUCTIVE): '.$logMessage;
                            continue;
                        }

                        if (!empty($dropResult) || !empty($addResult)) {
                            $this->migration['logs'][] = $logMessage;
                        }
                    }
                }
            }

            // Delete Fields
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    if (isset($field['Field']) && !isset($this->migration['schema']['tables'][$tableName]['columns'][$field['Field']])) {
                        $logMessage = 'Dropped Column ['.$this->prefix.$tableName.'.'.$field['Field'].']';
                        $sql = 'ALTER TABLE '.$this->prefix.$tableName.' DROP COLUMN "'.$field['Field'].'"';

                        if ($this->migration['options']['debug']) {
                            $this->migration['logs'][] = '(DEBUG): '.$logMessage;
                            $this->migration['logs'][] = '(SQL): '.($this->type === 'mysql' ? str_replace('"', '`', $sql) : $sql);
                            continue;
                        }

                        if ($this->migration['options']['dryrun']) {
                            $this->migration['logs'][] = '(DRYRUN): '.$logMessage;
                            continue;
                        }

                        if (!$this->migration['options']['force']) {
                            $this->migration['logs'][] = '(IGNORED DESTRUCTIVE): '.$logMessage;
                            continue;
                        }

                        $result = $this->exec($sql);

                        if ($result || 0 === $result || strval($result) === '0') {
                            $this->migration['logs'][] = $logMessage;
                        } else {
                            $this->migration['logs'][] = 'Error: '.$logMessage.'. Reported an Error.';
                        }

                        $this->migrateCheckErrors($sql);
                    }
                }
            }

            // Update Fields
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    if (isset($field['Type']) && isset($field['Null']) && isset($this->migration['schema']['tables'][$tableName]['columns'][$field['Field']])) {
                        $schemaField = $this->migration['schema']['tables'][$tableName]['columns'][$field['Field']];
                        $schemaType = $this->migrateFieldType($schemaField);
                        $schemaNull = (!empty($schemaField['null']) ? 'YES' : 'NO');

                        if (!isset($field['Default'])) {
                            if (strval($schemaNull) === 'YES') {
                                $field['Default'] = 'NULL';
                            } else {
                                $field['Default'] = '';
                            }
                        }

                        $schemaDefault = $this->migrateFieldDefault($schemaField);

                        $typeMatch = (strtolower(trim($field['Type'])) === strtolower(trim($schemaType)));
                        $nullMatch = (strtolower(trim($field['Null'])) === strtolower(trim($schemaNull)));
                        $defaultMatch = (strtolower(trim($field['Default'])) === strtolower(trim($schemaDefault)));

                        if (!$typeMatch || !$nullMatch || !$defaultMatch) {
                            $logMessage = 'Update Column ['.$this->prefix.$tableName.'.'.$field['Field'].'] '.$this->migrateFieldValues($schemaField);
                            $sql = 'ALTER TABLE '.$this->prefix.$tableName.' MODIFY "'.$field['Field'].'" '.$this->migrateFieldValues($schemaField);

                            if ($this->migration['options']['debug']) {
                                $this->migration['logs'][] = '(DEBUG): '.$logMessage;
                                $this->migration['logs'][] = '(SQL): '.($this->type === 'mysql' ? str_replace('"', '`', $sql) : $sql);
                                continue;
                            }

                            if ($this->migration['options']['dryrun']) {
                                $this->migration['logs'][] = '(DRYRUN): '.$logMessage;
                                continue;
                            }

                            if (!$this->migration['options']['force']) {
                                $this->migration['logs'][] = '(IGNORED DESTRUCTIVE): '.$logMessage;
                                continue;
                            }

                            $result = $this->exec($sql);

                            if ($result || 0 === $result || strval($result) === '0') {
                                $this->migration['logs'][] = $logMessage;
                            } else {
                                $this->migration['logs'][] = 'Error: '.$logMessage.'. Reported an Error.';
                            }

                            $this->migrateCheckErrors($sql);
                        }
                    }
                }
            }
        }
    }



    /**
     * Updates Field Type based on Migration
     *
     * @param array $field
     *
     * @access 'public'
     *
     * @return boolean
     */
    private function migrateFieldType($field = [])
    {
        $type = isset($field['type']) ? $field['type'] : 'string';

        if (!empty($field['options'])) {
            $type = 'options';
        }

        switch ($type) {
            case 'tinyint':
                return 'tinyint(3)';

            case 'int':
                return 'int(10)';

            case 'bigint':
                return 'bigint(21)';

            case 'bool':
            case 'boolean':
                return 'tinyint(1)';

            case 'number':
                return 'float';

            case 'decimal':
                return 'decimal(10,2)';

            case 'options':
            case 'enum':
                $options = !empty($field['options']) ? $field['options'] : [];

                return "enum('".implode("','", $options)."')";

            case 'bigtext':
                return 'longtext';

            case 'longstring':
            case 'bigstring':
                return 'varchar(250)';

            case 'string':
                return 'varchar(90)';

            case 'tinystring':
                return 'varchar(20)';
        }

        return $type;
    }



    /**
     * Sets Field Default based on Migration
     *
     * @param array $field
     *
     * @access private
     *
     * @return string
     */
    private function migrateFieldDefault($field = [])
    {
        if (isset($field['default'])) {
            if (in_array(trim(strtoupper($field['default'])), ['NOW()', 'NOW', 'CURRENT_TIMESTAMP'])) {
                return 'CURRENT_TIMESTAMP';
            }

            return $field['default'];
        }

        if (!empty($field['auto_increment']) && !empty($field['primary'])) {
            return '';
        }

        $type = isset($field['type']) ? $field['type'] : 'string';

        if (!empty($field['options'])) {
            $type = 'options';
        }

        if (!empty($field['null'])) {
            return 'NULL';
        }

        switch ($type) {
            case 'tinyint':
            case 'int':
            case 'bigint':
            case 'bool':
            case 'boolean':
            case 'number':
            case 'float':
            case 'timestamp':
                return '0';

            case 'decimal':
                return '0.00';

            case 'datetime':
                return '0000-00-00 00:00:00';

            case 'date':
                return '0000-00-00';

            case 'time':
                return '00:00:00';
        }

        return '';
    }



    /**
     * Update Field Values based on Migration
     *
     * @param array $field
     *
     * @access private
     *
     * @return string
     */
    private function migrateFieldValues($field = [])
    {
        $fieldValues = [];

        $default = [
            'type' => 'string',
        ];

        $field = array_merge($default, $field);

        $fieldValues[] = $this->migrateFieldType($field);

        // Primary Key
        if (!empty($field['primary'])) {
            $fieldValues[] = 'PRIMARY KEY';
        }

        // Auto Increment
        if (!empty($field['auto_increment'])) {
            $fieldValues[] = 'AUTO_INCREMENT';
        }

        // Unique
        if (!empty($field['unique'])) {
            $fieldValues[] = 'UNIQUE KEY';
        }

        // Null
        if (!empty($field['null'])) {
            $fieldValues[] = 'NULL';
        } else {
            $fieldValues[] = 'NOT NULL';
        }

        // Default
        $fieldDefault = $this->migrateFieldDefault($field);

        $isNull = (!empty($field['null']) && strtoupper($fieldDefault) === 'NULL');

        if (strval($fieldDefault) !== '' && !$isNull) {
            if (strval($fieldDefault) === 'CURRENT_TIMESTAMP') {
                $fieldValues[] = 'DEFAULT CURRENT_TIMESTAMP';
            } else {
                $fieldValues[] = "DEFAULT '".$fieldDefault."'";
            }
        }

        // Extra
        if (!empty($field['update']) && in_array(trim(strtoupper($field['update'])), ['NOW()', 'NOW', 'CURRENT_TIMESTAMP'])) {
            $fieldValues[] = 'ON UPDATE CURRENT_TIMESTAMP';
        }

        return implode(' ', $fieldValues);
    }
}
