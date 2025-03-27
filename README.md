# DinoFramework

DinoFramework es un framework de PHP para desarrollo de aplicaciones web con arquitectura MVC diseñado por [Dinozign](https://dinozign.com)

## Instalación

1. Por el momento para poder usar el Framework debe ser descargado el repositorio en su maquina virtual

```sh
git clone https://github.com/joelAIbarraDino/DinoFramework
```

2. crear una carpeta de trabajo donde estara su proyecto

```sh
mkdir nuevoProyecto && cd nuevoProyecto
```

3. iniciar un nuevo proyecto con composer 

```sh
composer init
```

4. editar el archivo json y agregar el campo **repositories**

```json
"repositories": [
    {
        "type":"path",
        "url":{path del repositorio descargado en el paso 1},
        "options": {
            "symlink": false
        }
    }
]
```

5. en el campo *require* agregar el nombre del paquete **"joel/dino-framework":"*"**

```json
"require": {
    "joel/dino-framework":"*"
}
```

6. guardar el archivo json y actualizar composer para que instale el nucleo del framework

```sh
composer update
```

## nuevo proyecto

Para generar la estructura basica de un nuevo proyecto, una vez que el nucleo este instalado correctamente, ejecute el siguiente comando en la raiz del proyecto

```sh
vendor/bin/dino-install
```

Despues de ejecutar el comando tendra una estructura basica en su proyecto de la siguiente forma

```md
nuevo-proyecto/
├── app/
│   ├── Middlewares/
│   ├── Controllers/
│   │   └── PublicController.php
│   ├── Models/
│   │   └── User.php
│   └── Views/
│       ├── public/
│       │   └── index.php
│       └── master.php
├── logs/
│   └── error.log
├── public/
│   ├── index.php
│   └── .env
├── vendor/
└── composer.json
```

dentro de la carpeta app pude crear controladores, modelos, middlewares y vistas que requiera en su proyecto.

Despues de crear la estructura del proyecto, debe agregar el campo `autoload` y registrar el namespace App que apunta a la carpeta app, esto por que los archivos creados estan bajo este namespace y puede generar errores

```json
"autoload": {
        "psr-4": {
            "App\\":"./app"
        }
    }
```

## Archivos por defecto en un nuevo proyecto

A continuación se explicara los archivos que son generados despues de ejecutar el comando para la creación de un nuevo proyecto

### PublicController.php

por defecto el controlador que maneja las paginas disponibles para todo el publico se llama `PublicController.php`, las vistas que llama este controlador estan en la carpeta `app/Views/public`, todas las vistas que pueden ser vistas sin necesidad de ser autenticado pueden ser guardadas en esta carpeta

### Model user

por defecto tendra un modelo usuario para consultar en su base de datos con los siguientes atributos 

```php
public ?int $id;
public string $username;
public string $email;
public string $password;
public string $token;
```

pude personalizar el nombre de sus atributos dependiendo del diseño de su base de datos

### View/master.php

este archivo tiene la estrucutura basica que tendra la pagina web, si requiere incluir alguna fuente externa o scripts externos que requiera tenerlos presentes en todas sus paginas, pude modificar este archivo dependiendo de sus necesidades

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$nameApp." | ".$title??""?></title>
</head>
<body>
    <?= $content ?>

    <?= $scripts??''; ?>
</body>
</html>
```

la variable `$content` es donde se inyectara el contenido que tendra la vista, por lo que esta vista no requiere tener toda la estructura completa html. La variable `$scripts` se puede usar para incluir scripts especificos de javascript que requiera la vista que se esta llamando

las variables `$nameApp` y `$title` pueden variar cuando se llama a la vista desde el controlador.

```php
public static function index(): void{
    Response::render('public/index', [
        'nameApp'=>APP_NAME, 
        'title' => 'Inicio'
    ]);
}
```

### View/index.php

Este archivo tiene la estructura html para mostrar en la vista de inicio, como se menciono anteriormente, este archivo y todas las vistas en general no debe de incluir el html completo, ya que se inyecta dentro del contenido del archivo `master.php`, quedando solo esto:

```html
<h1>Todo parece funcionar correctamente</h1>
```


### logs/error.log

Este archivo registra los errores que pueden llegar a suceder al momento de desarrollar la aplicación o en el momento de ejecución, por lo que es un archivo util para consultar y corregir errores que pueden suceder en su aplicación

### public/index.php

Este es el archivo principal del proyecto, donde se configurar las variables de entorno de la base de datos, credenciales para enviar correos de errores de ejecución al momento de pasar la aplicación en modo de producción, la carga del archivo autoload, la configuración del modo de ejecución para el manejo de errores y las rutas del proyecto, por defecto esta registrada una ruta, la de inicio y usa el controlador que esta definido por defecto

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PublicController;
use DinoEngine\Core\Database;
use DinoFrame\Dino;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

date_default_timezone_set('America/Mexico_City');
define('APP_NAME', 'Mi app dinozign');

$dbConfig = [
    "host"=>$_ENV['DB_HOST'],
    "port"=>$_ENV['DB_PORT'],
    "user"=>$_ENV['DB_USER'],
    "password"=>$_ENV['DB_PASS'],
    "database"=>$_ENV['DB_DATABASE'],
    "driver"=>Database::PDO_DRIVER
];

$emailConfig = [
    "from"=>$_ENV['MAIL_DEBUG_FROM'],
    "to"=>$_ENV['MAIL_DEBUG_TO'],
    "name"=>$_ENV['MAIL_DEBUG_NAME'],
    "host"=>$_ENV['MAIL_DEBUG_HOST'],
    "user"=>$_ENV['MAIL_DEBUG_USER'],
    "password"=>$_ENV['MAIL_DEBUG_PASS'],
    "port"=>$_ENV['MAIL_DEBUG_PORT']
];

$dino = new Dino(dirname(__DIR__), Dino::DEVELOPMENT_MODE, $dbConfig, $emailConfig);

$dino->router->get('/', [PublicController::class, 'index']);

$dino->router->dispatch();
```

los argumento que se pasan al crear un nuevo objeto son los siguientes

1. nombre de la aplicación

2. ruta de la carpeta raiz del proyecto

3. modo que esta ejecutando el proyecto, por defecto esta en modo de desarrollador, que esto hace que el manejo de errores sea mas detallado, por lo que debe tener cuidado que el proyecto al momento de subir a producción no ejecutar en este modo

4. credenciales para el acceso a la base de datos

5. credenciales para enviar el correo de que ha sucedido un error y el trace de errores

