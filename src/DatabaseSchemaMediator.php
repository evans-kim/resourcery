<?php


namespace EvansKim\Resourcery;


use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use EvansKim\Resourcery\Exception\NotSupportDatabaseDriver;
use Illuminate\Support\Facades\Config;

class DatabaseSchemaMediator
{
    private $conn;
    private $table;
    /**
     * @var Table
     */
    private $table_details;

    /**
     * DatabaseSchemaMediator constructor.
     * @throws DBALException
     * @throws NotSupportDatabaseDriver
     */
    public function __construct()
    {
        $config = new Configuration();
        $database = Config::get('database');
        $param = $database['connections'][ $database['default'] ];
        if( !in_array($param['driver'], ['sqlite','mysql']) ){
            throw new NotSupportDatabaseDriver;
        }
        if($param['driver'] === 'sqlite'){
            $connectionParams = [
                'user' => null,
                'password' => null,
                'path' => $param['database']
            ];
        }
        if($param['driver'] === 'mysql'){
            $connectionParams = array(
                'dbname' => $param['database'],
                'user' => $param['username'],
                'password' => $param['password'],
                'host' => $param['host'],
                'driver' => 'pdo_mysql',
            );
        }

        $this->conn = DriverManager::getConnection($connectionParams, $config);
    }
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    public function getColumns()
    {
        $this->table_details = $this->conn->getSchemaManager()->listTableDetails($this->table);

        return $this->table_details->getColumns();
    }

    public static function getTableColumns($table)
    {
        $db = (new static());
        return $db->getConnection()->getSchemaManager()->listTableColumns($table);
    }

    public function toMysqlColumns()
    {
        return array_map( function( Column $column ){
            return [
                'Field' => $column->getName(),
                'Type' => $column->getType()->getName(),
                'Null' => $column->getNotnull(),

            ];
        }, $this->getColumns());
    }

}
