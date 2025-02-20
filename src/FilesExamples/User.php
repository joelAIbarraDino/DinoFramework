<?php

use DinoEngine\Core\Model;

class User extends Model {
    
    protected static $table = 'user';
    protected static $PK_name = 'id';
    protected static $columns = ['id', 'username', 'email', 'password', 'token'];
    protected static $fillable = ['username', 'email', 'password'];
    protected static $nullable = ['token'];

    public ?int $id;
    public string $username;
    public string $email;
    public string $password;
    public string $token;

    public function __construct($args = []){
        $this->id = $args["id"]??null;
        $this->username = $args["username"]??"";
        $this->email = $args["email"]??"";
        $this->password = $args["password"]??"";
        $this->token = $args["token"]??null;
    }
}