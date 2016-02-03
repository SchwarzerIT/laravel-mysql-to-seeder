<?php
/*
 * This is a Helper for Laravel to export Data from an existing Database to a Laravel Seeder
 *
 * @author André Schwarzer <andre@schwarzer.it>
 *
 * @license MIT
 *
 * @see https://stackoverflow.com/questions/1472250/pdo-working-with-table-prefixes?answertab=active#1472677
 *     The following functions are based on "Glass Robot's" answer: __construct , exec , prepare , query  & _tablePrefixSuffix
 * @see https://stackoverflow.com/questions/24316347/how-to-format-var-export-to-php5-4-array-syntax#24316675
 *     The following function is based on "mario's" answer: niceExport
 */

namespace Schwarzer\LaravelHelper\MySQLToSeeder;


/**
 * Class Export
 * @package Schwarzer\LaravelHelper\MySQLToSeeder
 */
class Export extends \PDO
{
    /**
     * @var null|string
     */
    protected $_table_prefix;

    /**
     * @var null|string
     */
    protected $_table_suffix;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $databasename;

    /**
     * @var string
     */
    protected static $indent = '        '; // 8 Spaces

    /**
     * Export constructor.
     *
     * @param $hostname
     * @param $databasename
     * @param null $username
     * @param null $password
     * @param array $driver_options the default is: array(parent::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
     * @param null $prefix
     * @param null $suffix
     */
    public function __construct(
        $hostname,
        $databasename,
        $username = null,
        $password = null,
        $driver_options = array(parent::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'),
        $prefix = null,
        $suffix = null
    ) {
        date_default_timezone_set('Europe/Berlin');

        $this->dsn = 'mysql:dbname=' . $databasename . ';host=' . $hostname;
        $this->databasename = $databasename;
        $this->_table_prefix = $prefix;
        $this->_table_suffix = $suffix;

        try {
            parent::__construct($this->dsn, $username, $password, $driver_options);
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    /**
     * Extends the PDO::exec function. A pre- and suffix is added.
     *
     * @param string $statement
     *
     * @return int
     */
    public function exec($statement)
    {
        $statement = $this->_tablePrefixSuffix($statement);
        return parent::exec($statement);
    }

    /**
     * Extends the PDO::prepare function. A pre- and suffix is added.
     *
     * @param string $statement
     * @param array $driver_options
     *
     * @return \PDOStatement
     */
    public function prepare($statement, $driver_options = array())
    {
        $statement = $this->_tablePrefixSuffix($statement);
        return parent::prepare($statement, $driver_options);
    }

    /**
     * Extends the PDO::query function. A pre- and suffix is added.
     *
     * @param string $statement
     *
     * @return mixed|\PDOStatement
     */
    public function query($statement)
    {
        $statement = $this->_tablePrefixSuffix($statement);
        $args = func_get_args();

        if (count($args) > 1) {
            return call_user_func_array(array($this, 'parent::query'), $args);
        } else {
            return parent::query($statement);
        }
    }

    /**
     * Adds a pre- and suffix to the statement.
     *
     * @param $statement
     *
     * @return string
     */
    protected function _tablePrefixSuffix($statement)
    {
        return sprintf($statement, $this->_table_prefix, $this->_table_suffix);
    }

    /**
     * Get all Tablenames from the given DB.
     *
     * @return array
     */
    public function getAllTableNames()
    {
        $sql = 'SHOW TABLES';
        $query = self::prepare($sql, array(parent::ATTR_CURSOR => parent::CURSOR_FWDONLY));
        $query->execute();
        $results = [];
        $queryResults = $query->fetchAll();
        foreach ($queryResults as $result) {
            $results[] = $result[0];
        }
        return $results;
    }

    /**
     * Get all Columnnames for a specific Table from the given DB.
     *
     * @param null $tablename
     *
     * @return array
     */
    public function getAllColumnNamesForTable($tablename = null)
    {
        if (is_null($tablename)) {
            return null;
        }

        $sql = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = "' . $this->databasename . '" AND TABLE_NAME = :tablename';

        $query = self::prepare($sql, array(parent::ATTR_CURSOR => parent::CURSOR_FWDONLY));

        $query->bindValue(':tablename', $tablename, parent::PARAM_STR);

        $query->execute();

        $results = [];
        $queryResults = $query->fetchAll();
        foreach ($queryResults as $result) {
            $results[] = $result[0];
        }
        return $results;
    }

    /**
     * Get all Tablenames from the given DB.
     *
     * @param null $tablename
     *
     * @return array
     */
    public function getAllColumnTypesForTable($tablename = null)
    {
        if (is_null($tablename)) {
            return null;
        }

        $sql = 'SELECT COLUMN_TYPE, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = "' . $this->databasename . '" AND TABLE_NAME = :tablename';

        $query = self::prepare($sql, array(parent::ATTR_CURSOR => parent::CURSOR_FWDONLY));

        $query->bindValue(':tablename', $tablename, parent::PARAM_STR);

        $query->execute();

        $results = [];
        $queryResults = $query->fetchAll();
        foreach ($queryResults as $result) {
            $results[$result['COLUMN_NAME']] = $result['COLUMN_TYPE'];
        }
        return $results;
    }

    /**
     * Get all Columnnames for all Tables from the given DB.
     *
     * @return array
     */
    public function getAllColumnNamesForAllTables()
    {
        $tables = $this->getAllTableNames();

        $results = [];
        foreach ($tables as $tablename) {
            foreach ($this->getAllColumnNamesForTable($tablename) as $result) {
                $results[$tablename][] = $result;
            }
        }
        return $results;
    }

    /**
     * Get all Entries for a specific or all Tables from the given DB.
     *
     * @param null $tablename
     * @param array $booleanValues
     * @param array $timestamps
     * @param array $dates
     *
     * @return array
     */
    public function getAllEntriesFromTable(
        $tablename = null,
        $booleanValues = [],
        $timestamps = ['created_at', 'updated_at'],
        $dates = ['birthday']
    ) {
        if (is_null($tablename) || !is_string($tablename)) {
            return null;
        }

        $sql = 'SELECT * FROM ' . $tablename;

        $query = self::prepare($sql, array(parent::ATTR_CURSOR => parent::CURSOR_FWDONLY));

        $query->execute();

        $queryResults = $query->fetchAll();
        $columns = $this->getAllColumnNamesForTable($tablename);

        // set $now to now
        $now = date('Y-m-d H:i:s');
        // fix the results
        $results = [];
        foreach ($queryResults as $rowKey => $row) {
            foreach ($row as $key => $value) {


                //check if the $key of the current $row's column is in the available $columns for the table(name)
                if (is_string($key) && in_array($key, $columns)) {

                    // if the $key is mentioned in the $booleanValues, force it into boolean
                    if (in_array($key, $booleanValues)) {
                        $results[$rowKey][$key] = (bool)$value;


                        // if the $key is mentioned in the $timestamps, replace null, '' and 0000-00-00 00:00:00 with $now
                    } elseif (in_array($key,
                            $timestamps) && ($value == null || $value == '' || $value == '0000-00-00 00:00:00')
                    ) {
                        $results[$rowKey][$key] = $now;

                        // if the $key is mentioned in the $dates, replace null, '' and 0000-00-00 00:00:00 with $now
                    } elseif (in_array($key,
                            $dates) && $value != null && ($value == '' || $value == '0000-00-00' || $value == '0000')
                    ) {
                        $results[$rowKey][$key] = null;

                        // other cases, escape ' with \'
                    } else {
                        $results[$rowKey][$key] = str_replace('\'', '\\\'', $value);
                    }
                } else {
                    // add missing created_at
                    if (!in_array('created_at', $results[$rowKey])) {
                        $results[$rowKey]['created_at'] = $now;
                    }
                    // add missing updated_at
                    if (!in_array('updated_at', $results[$rowKey])) {
                        $results[$rowKey]['updated_at'] = $now;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Generate the Seeder Files.
     *
     * @param null $tables
     * @param string $pathToSeeds default: './database/seeds'
     * @param array $booleanValues
     * @param array $timestamps
     */
    public function generateExport($tables = null, $pathToSeeds = './database/seeds', $booleanValues = [], $timestamps = ['created_at', 'updated_at'])
    {
        /*
         * For PHP 7
         *
         * foreach ($tables ?? $this->getAllTableNames() as $tablename) {
         *
         */
        foreach ( (is_null($tables) ? $this->getAllTableNames() : $tables) as $tablename) {

            // get the entries and transfere the result into a well formatted string
            $entries = self::$indent . preg_replace('/(>) */', '> ', $this->niceExport($this->getAllEntriesFromTable($tablename, $booleanValues, $timestamps), self::$indent));

            // uppercase the table's name
            $file = ucfirst($tablename) . 'TableSeeder';

            // compose the output
            $content =
                '<?php' . "\n\n" .

                'use Illuminate\Database\Seeder;' . "\n\n" .

                'class ' . $file . ' extends Seeder {' . "\n" .

                '    public function run() {' . "\n" .

                '        $entries = ' . "\n" . $entries . ';' . "\n\n\n" .

                '        foreach($entries as $entry){' . "\n" .
                '            DB::table(\'' . $tablename . '\')->insert($entry);' . "\n" .
                '        }' . "\n" .

                '    }' . "\n" .

                '}';

            file_put_contents( $pathToSeeds.'/' . $file . '.php', $content, LOCK_EX);
        }
    }

    /**
     * Clean the var_export output from array() to [].
     *
     * @param $var
     * @param string $indent
     *
     * @return mixed|string
     */
    public function niceExport($var, $indent = '')
    {
        switch (gettype($var)) {
            case 'string':
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case 'array':
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : $this->niceExport($key) . " => ")
                        . $this->niceExport($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case 'boolean':
                return $var ? 'true' : 'false';
            case 'NULL':
                return 'null';
            default:
                return var_export($var, true);
        }
    }
}