<?php

declare(strict_types=1);

namespace DinoEngine\Core;

use DinoEngine\Exceptions\DatabaseConnectionException;
use DinoEngine\Exceptions\MassAssignmentException;
use DinoEngine\Exceptions\QueryException;
use DinoEngine\Core\Database;
use DinoEngine\Helpers\Helpers;
use InvalidArgumentException;
use RuntimeException;
use PDOException;

use mysqli_result;
use PDOStatement;

use PDO;

class Model{

    //database attributes 
    protected static $db;
    protected static string $driver;

    //table database attributes
    protected static string $table;
    protected static string $PK_name = "id";
    protected static array $columns = [];
    
    //alerts attributes
    protected static array $alerts = [];

    //Model attributes
    protected static array $fillable = [];
    private static array $hidden = [];
    protected static array $selectColumns = [];
    protected static array $nulleable = [];
    
    //alerts methods

    /**
     * set the database instance to query in the model
     * @param mixed $db database instance
     */
    public static function setDB($db):void{
        self::$db = $db->getConnection();
        self::$driver = $db->getDriver();
    }

    /**
     * set a new alert message 
     * @param string $type a message type **error**, **warning** or **success**
     * @param string $message a message to alert the user
     */
    public static function setAlerts(string $type, string $message):void{
        self::$alerts[$type][] = $message;
    }

    /**
     * return an array of alerts
     * @return array array of alerts
     */
    public static function getAlerts():array{
        return self::$alerts;
    }

    //query methods
    
    //ok
    public static function find(int $id):static|null{

        if($id < 0)
            throw new InvalidArgumentException("id must be positive");

        $columns = array_diff(static::$columns, static::$selectColumns);

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= " WHERE ". static::$PK_name ." = :id";

        $stmt = self::executeSQL($query, [':id'=>$id]);
        $results = self::DatabaseResultToObjects($stmt);
        
        return empty($results)?null:array_shift($results);
    }
    
    //ok
    public static function all(int $limit = 0):array{

        if($limit < 0)
            throw new InvalidArgumentException("limit must be positive");

        $columns = array_diff(static::$columns, static::$selectColumns);

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= $limit > 0 ?" LIMIT ".$limit :""; 

        $stmt = self::executeSQL($query);
        $results = self::DatabaseResultToObjects($stmt);

        return $results;
    }

    //ok
    public static function belongsTo(string $column, string $value):array|null{
        $columns = array_diff(static::$columns, static::$selectColumns);

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= " WHERE ". $column ." = :value";

        $stmt = self::executeSQL($query, [':value'=>$value]);
        $results = self::DatabaseResultToObjects($stmt);
        
        return empty($results)?null:$results;
    }

    //ok
    public static function where(string $column, string $operator, ?string $value):static|null{
        
        self::validateOperator($operator, ['=', '!=', '>', '<', '>=', '<=', 'LIKE']);
        self::validateColumn($column);
        self::validateValue($column, $value);

        $columns = array_diff(static::$columns, static::$selectColumns);

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= " WHERE ".$column.$operator.":value";

        $stmt = self::executeSQL($query, [':value'=>$value]);
        $results = self::DatabaseResultToObjects($stmt);
        
        return empty($results)?null:array_shift($results);
    }

    //ok
    public static function multiWhere(array $data, array $operators = ["AND"]):static|null{
        
        if (count($operators) != count($data) - 1)
            throw new InvalidArgumentException("The number of operators must be one less than the number of conditions.");

        self::validateValues($data);        
        self::validateOperators($operators, ['AND', 'OR']);
        self::validateColumns($data);

        $columns = array_diff(static::$columns, static::$selectColumns);
        
        $conditions = array_map(fn($col) => "$col = :$col", array_keys($data));
        $whereClause = "WHERE " . implode(" ", array_map(fn($cond, $op) => "$cond $op", $conditions, $operators));

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= " ".$whereClause;

        $params = self::createParams($data);
    
        $stmt = self::executeSQL($query, $params);
        $results = self::DatabaseResultToObjects($stmt);
        
        return empty($results)?null:array_shift($results);
    }

    //ok
    public static function nullValue(string $column, bool $isNull = true):array|null{

        self::validateColumn($column);

        $columns = array_diff(static::$columns, static::$selectColumns);
        
        $conditionNull = $isNull?'IS NULL':'IS NOT NULL';
        $whereClause = "WHERE $column $conditionNull";

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= " ".$whereClause;
    
        $stmt = self::executeSQL($query);
        $results = self::DatabaseResultToObjects($stmt);
        
        return empty($results)?null:$results;
    }

    //ok
    public static function nullValues(array $nullColumns, array $operators = ['AND'], bool $isNull = true):array|null{

        if (count($operators) != count($nullColumns) - 1)
            throw new InvalidArgumentException("The number of operators must be one less than the number of conditions.");
        
        self::validateOperators($operators, ['AND', 'OR']);
        self::validateColumns($nullColumns);

        $columns = array_diff(static::$columns, static::$selectColumns);
        
        $conditionNull = $isNull ? 'IS NULL' : 'IS NOT NULL';
        $conditions = array_map(fn($nullColumn) => "$nullColumn $conditionNull", $nullColumns);
        $whereClause = "WHERE " . implode(" ", array_map(fn($cond, $op) => "$cond $op", $conditions, $operators));

        $query = "SELECT ";
        $query .= implode(', ', $columns);
        $query .= " FROM ". static::$table;
        $query .= " ".$whereClause;
    
        $stmt = self::executeSQL($query);
        $results = self::DatabaseResultToObjects($stmt);
        
        return empty($results)?null:$results;
    }

    //ok
    public function save():?int{
        
        $columnName = static::$PK_name;

        if(is_null($this->$columnName))
            return $this->create();
        else
            return $this->update();
    }
    
    //ok
    private function create():?int{
        $attributes = $this->attributesTableMatch();

        self::validateColumns(array_keys($attributes));

        $query  = "INSERT INTO ". static::$table ."(";
        $query .= implode(", ", array_keys($attributes));
        $query .= ") VALUES (";
        $query .= implode(", ", array_map(fn($col)=>":$col", array_keys($attributes)));
        $query .= ")";

        $params = self::createParams($attributes);
        self::executeSQL($query, $params);

        $id = self::$driver === Database::MYSQLI_DRIVER?self::$db->insert_id:self::$db->lastInsertId();

        return $id?(int)$id:null;
    }

    //ok
    private function update():int{
        $attributes = $this->attributesTableMatch();
        $idName = static::$PK_name;

        self::validateColumns(array_keys($attributes));

        $query  = "UPDATE ". static::$table." SET ";
        $query .= implode(", ", array_map(fn($col) => "$col = :$col", array_keys($attributes)));
        $query .= " WHERE $idName = :$idName";
        $query .= " LIMIT 1";

        $params = self::createParams($attributes);
        $params[":$idName"] =  $this->$idName;
        
        $stmt = self::executeSQL($query, $params);

        return self::$driver === Database::MYSQLI_DRIVER?$stmt->affected_rows:$stmt->rowCount();
    }

    //ok
    public function delete():int{

        $idName = static::$PK_name;

        $query = "DELETE FROM ".static::$table;
        $query .= " WHERE $idName = :$idName";
        $query .= " LIMIT 1";

        $stmt = self::executeSQL($query, [":$idName"=> $this->$idName]);
        
        return self::$driver === Database::MYSQLI_DRIVER?$stmt->affected_rows:$stmt->rowCount();
    }

    //ORM methods

    /**
     * match the columns with values of the object
     * @return array array with values of the object
     */
    private function attributesTableMatch():array{
        $attributes = [];

        foreach(static::$columns as $column){
            if($column != static::$PK_name){
                if(!property_exists($this, $column))
                    throw new RuntimeException("Property $column does not exists");
                
                if(is_null($this->$column) && !in_array($column, static::$nulleable))
                    throw new RuntimeException("Column $column does not allow null values");
                $attributes[$column] = $this->$column;

            }
        }
        return $attributes;   
    }

    private static function DatabaseResultToObjects($DatabaseResult):array{
        switch(self::$driver){
            case Database::MYSQLI_DRIVER:
                $array = Database::mysqliResultToArray($DatabaseResult);
                return self::arrayToArrayObject($array);
            break;
            
            case Database::PDO_DRIVER:
                $array = $DatabaseResult->fetchAll(PDO::FETCH_ASSOC);
                return self::arrayToArrayObject($array);
             break;
            default:
                throw new DatabaseConnectionException("Driver ".self::$driver." no supported", -1);
        }
    }

    private static function arrayToArrayObject(array $arrays):array{
        $arraysObject = [];
        
        foreach($arrays as $array)
            $arraysObject[] = static::newObject($array); 
        
        return $arraysObject;
    }

    /**
     * create a new static object setted with the values of database row
     * @param array $row a database registrer row
     * @return static returns a new inherited object or null
     */
    protected static function newObject(array $row):static{
    
        $object = new static;

        foreach($row as $key=>$value)
            if(property_exists($object, $key))
                $object->$key = $value;
            
        return $object;
    }

    /**
     * update the attributes of a created instence
     * @param array $args news values of the attributes
     */
    public function sincronize(array $args = []):void{

        if(count(static::$fillable) == 0)
            throw new MassAssignmentException("Must add fillable attribute to allow mass asigment", -1);

        foreach($args as $key=>$value){
            if(!property_exists($this, $key))
                throw new MassAssignmentException("Property $key does not exists", -2);

            if(!in_array($key, static::$fillable))
                throw new MassAssignmentException("Property $key is not fillable", -3);

            if(is_null($value) && !in_array($key, static::$nulleable))
                throw new MassAssignmentException("Property $key does not allow null values", -4);

            $this->$key = $value;
        }
    }

    //SQL execution methods

    /**
     * Execute a sentence SQL with the correct driver
     * @param string $sql sentece SQL
     * @param array $params query params
     * @return mixed query result
     */
    private static function executeSQL(string $sql, array $params = []):mixed{
        switch(self::$driver){
            case Database::MYSQLI_DRIVER:
                return self::executeMysqliQuery(self::prepareSentenceMySQL($sql), array_values($params));
            break;
            
            case Database::PDO_DRIVER:
                return self::executePDOQuery($sql, $params);
             break;
            default:
                throw new DatabaseConnectionException("Driver ".self::$driver." no supported", -1);
        }
    }

    /**
     * Execute a sentence SQL with Mysqli driver
     * @param string $sql sentece SQL
     * @param array $params query params
     * @return mysqli_result mysqli result
     */
    private static function executeMysqliQuery(string $sql, array $params): mixed {
        $stmt = self::$db->prepare($sql);
        
        if (!$stmt)
            throw new QueryException("Prepare failed: " . self::$db->error, -2);
        
        if (!empty($params)) {
            $types = Database::determinateTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute())
            throw new QueryException("Execute failed: " . $stmt->error, -4);
        
        if(stripos($sql, 'SELECT') === 0){
            $result = $stmt->get_result();

            if (!$result) 
                throw new QueryException("Get result failed: " . $stmt->error, -5);
            
            return $result;
        }else{
            return $stmt;
        }
    }

    /**
     * Execute a sentece SQL with PDO driver
     * @param string $sql sentece SQL
     * @param array $params query params
     * @return PDOStatement executed statement
     */
    private static function executePDOQuery(string $sql, array $params): PDOStatement {
        try {
            $stmt = self::$db->prepare($sql);
    
            if (!$stmt) {
                throw new QueryException("Prepare failed: " . implode(", ", self::$db->errorInfo()), -2);
            }
    
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new QueryException("Query failed: " . $e->getMessage(), -3);
        }
    }
    
    private static function prepareSentenceMySQL(string $sql):string{
            return preg_replace('/:\w+/', '?', $sql);
    }

    private static function createParams($array):array{
        $params = [];

        foreach ($array as $column => $value)
            if(is_null($value) && !in_array($column, static::$nulleable))
                throw new InvalidArgumentException("Column '$column' does not allow null values");
            else
                $params[":$column"] = $value;
        
        return $params;
    }

    private static function validateOperator($operator, $operatorDic):void{
        if (!in_array($operator, $operatorDic)) 
                throw new InvalidArgumentException("invalid Operator: $operator");
    }

    private static function validateOperators($operators = [], $operatorDic = []):void{
        if(empty($operators))
            throw new InvalidArgumentException("Operators array is empty");

        if(empty($operatorDic))
            throw new InvalidArgumentException("Operators dictionary is emtpy");

        foreach($operators as $operator)
            self::validateOperator($operator, $operatorDic);

    }

    private static function validateColumn($column):void{
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column))
                throw new InvalidArgumentException("invalid column name: $column");
    }

    private static function validateColumns($columns):void{

        if(array_is_list($columns)){
            foreach($columns as $column)
                self::validateColumn($column);
        }else{
            foreach($columns as $column=>$value)
                self::validateColumn($column);

        }
    }

    private static function validateValue(string $column, mixed $value):void{
        if(is_null($value))
            throw new InvalidArgumentException("column '$column' does not allow evalue null values, use nullValues() method");
    }

    private static function validateValues(array $arrayData):void{
        foreach($arrayData as $column=>$data)
            self::validateValue($column, $data);
    }

}