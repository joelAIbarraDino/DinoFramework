<?php

declare(strict_types=1);

namespace DinoEngine\Core;

use DinoEngine\Exceptions\DatabaseConnectionException;
use mysqli_sql_exception;
use PDOException;

use mysqli;
use mysqli_result;
use PDO;

class Database{

    public const MYSQLI_DRIVER = "mysqli";
    public const PDO_DRIVER = "PDO";

    private string $host;
    private string $user;
    private string $password;
    private string $database;
    private int $port;
    private string $driver;
    private $connection = null;

    function __construct($config =[], $driver = self::PDO_DRIVER)
    {
        $this->host = $config['host']??'localhost';
        $this->port = $config['port']??3306;
        $this->user = $config['user']??'root';
        $this->password = $config['password']??'';
        $this->database = $config['database']??'test';
        $this->driver = $driver;
        
        $this->connect();
    }

    /**
     * create a new connection betewen PHP and database server using mysqli or PDO
     */
    private function connect():void{
        //check the driver selected
        switch($this->driver){

            //driver Mysqli
            case self::MYSQLI_DRIVER:
                try{
                    $this->connection = new mysqli($this->host, $this->user, $this->password, $this->database, $this->port);

                    //verify if connect with database
                    if($this->connection->connect_error)
                        throw new mysqli_sql_exception("Connection failed: can't open a new connection to MySQL server");
                
                    //set charset to UTF8mb4 to support spanish
                    if (!$this->connection->set_charset("utf8mb4"))
                        throw new mysqli_sql_exception("Error setting charset: " . $this->connection->error);

                }catch(mysqli_sql_exception $e){
                    throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
                }
            break;
            
            //driver PDO
            case self::PDO_DRIVER:
                //string connection to datbase to set the host, port, database name and charset
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";

                try{
                    $this->connection = new PDO($dsn, $this->user, $this->password);
                    $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    $this->connection->setAttribute(PDO::ATTR_PERSISTENT, true);
                }catch(PDOException $e){
                    throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e->getPrevious());
                }

            break;  
            
            //no support other driver
            default:
                throw new DatabaseConnectionException("Driver {$this->driver} no supported", -1);
        }
    }

    /**
     * return the object connection of database
     * @return mixed an instance type PDO or mysqli
     */
    public function getConnection():mixed{
        return $this->connection;
    }


    /**
     * return the driver used to connnecto to the database
     * @return string name of the driver used
     */
    public function getDriver():string{
        return $this->driver;
    }

    /**
     * determine the variable type of value param
     * @param array $param query params
     * @return string a string with the database type of values params
     */
    public static function determinateTypes(array $params):string{
        $types = "";
        foreach($params as $param){
            
            $types .= match (gettype($param)) {
                "integer" => "i",
                "double"  => "d",
                "boolean" => "i", // Trata booleanos como enteros (0 o 1)
                "NULL"    => "s", // Trata NULL como string
                default   => "s", // Por defecto, trata todo como string
            };

        }
        return $types;
    } 

    /**
     * Transform a new Mysqli result to associative array
     * 
     * @param mysqli_result $result mysqli result.
     * @return array results on associative array
     */
    public static function mysqliResultToArray(mysqli_result $result): array {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * close the connection to the database
     */
    public function closeConnection(): void
    {
        if ($this->connection instanceof mysqli)
            $this->connection->close();
        elseif ($this->connection instanceof PDO)
            $this->connection = null;
        
    }

}