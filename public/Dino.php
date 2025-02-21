<?php

namespace DinoFrame;

require __DIR__ . '/../vendor/autoload.php';

use DinoEngine\Helpers\Helpers;
use DinoEngine\Core\Database;
use DinoEngine\Http\Response;
use DinoEngine\Http\Request;
use DinoEngine\Core\Router;
use DinoEngine\Core\Model;

use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\CallbackHandler;
use Whoops\Run;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;


class Dino{
    public const DEVELOPMENT_MODE = 1;
    public const PRODUCTION_MODE = 2;

    public static string $ROOT_DIR;
    public static string $APP_NAME;
    public static Dino $dino;

    public Database $database;
    public Response $response;
    public Request $request;
    public Router $router;
    public Model $model;

    private Logger $logger;
    private ?PHPMailer $mailer;
    private array $emailConfig;

    public function __construct(string $nameApp, string $rootDir, array $DBconfig = [], string $DBdriver = Database::PDO_DRIVER, int $mode = self::DEVELOPMENT_MODE, array $emailConfig = null){
        
        self::$ROOT_DIR = $rootDir;
        self::$APP_NAME = $nameApp;
        
        $this->response = new Response(self::$ROOT_DIR);
        $this->emailConfig = $emailConfig??null;
        $this->request = new Request;
        $this->router = new Router;
        $this->mailer = null;

        $this->setupLogger();
        $this->setupErrorHandling($mode);

        if(empty($DBconfig))
            throw new InvalidArgumentException("config array must be configured");

        $this->database = new Database($DBconfig, $DBdriver);
    }

    private function setupLogger(): void{

        $this->logger = new Logger(self::$APP_NAME);
        
        Helpers::pathExists(self::$ROOT_DIR .'/logs/');
        
        $logFile = self::$ROOT_DIR .'/logs/error.log';
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::ERROR));
    }

    private function setupErrorHandling(int $mode): void{
        $whoops = new Run;

        switch($mode){
            case self::PRODUCTION_MODE:
                $whoops->pushHandler(new CallbackHandler(function ($error) {
                    $this->logError($error);
                    $this->sendErrorEmail($error);
                    echo "Ha ocurrido un error. Por favor, inténtalo de nuevo más tarde.";
                }));
            break;

            case self::DEVELOPMENT_MODE:
                $whoops->pushHandler(new PrettyPageHandler);
            break;
        }
        $whoops->register();
    }

    private function logError($error): void{
        // Registra el error usando Monolog
        $this->logger->error($error->getMessage(), [
            'trace' => $error->getTraceAsString(),
        ]);
    }
    
    private function sendErrorEmail($error): void
    {
        if(is_null($this->emailConfig))
            return;
        
        if($this->mailer === null)
            $this->setupMailer($this->emailConfig);


        try{
            $this->mailer->setFrom($this->emailConfig['from'], self::$APP_NAME);
            $this->mailer->addAddress($this->emailConfig['to'], $this->emailConfig['name']);
            $this->mailer->Subject = 'Error en la aplicación '.self::$APP_NAME;
            $this->mailer->Body = sprintf(
                "Ha ocurrido un error en la aplicación:\n\nMensaje: %s\n\nTraza:\n%s",
                $error->getMessage(),
                $error->getTraceAsString()
            );

            $this->mailer->send();
        }catch(PHPMailerException $e)  {
            $this->logger->error('Error al enviar el correo: ' . $e->getMessage());
        }
    }

    private function setupMailer($emailConfig): void{
        $this->mailer = new PHPMailer(true);

        $this->mailer->isSMTP();
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Password = $emailConfig['password'];
        $this->mailer->Username = $emailConfig['user'];
        $this->mailer->Port = $emailConfig['port'];
        $this->mailer->Host = $emailConfig['host'];
        $this->mailer->CharSet = "UTF-8";
        $this->mailer->SMTPAuth = true;
    }
}