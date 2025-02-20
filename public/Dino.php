<?php

namespace DinoFrame;

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

use function DinoEngine\Helpers\pathExists;

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

    public function __construct(string $nameApp, string $rootDir, array $DBconfig = [], string $DBdriver = Database::PDO_DRIVER, int $mode = self::DEVELOPMENT_MODE, array $emailConfig = []){
        $this->setupLogger();
        $this->setupErrorHandling($mode, $emailConfig);

        if(empty($config))
            throw new InvalidArgumentException("config array must be configured");

        self::$ROOT_DIR = $rootDir;
        self::$APP_NAME = $nameApp;

        $this->database = new Database($DBconfig, $DBdriver);
        $this->response = new Response(self::$ROOT_DIR);
        $this->request = new Request;
        $this->router = new Router;
    }

    private function setupLogger(): void{

        $this->logger = new Logger(self::$APP_NAME);
        pathExists(self::$ROOT_DIR .'/logs/');
        
        $logFile = self::$ROOT_DIR .'/logs/error.log';
        $this->logger->pushHandler(new StreamHandler($logFile, Logger::ERROR));
    }

    private function setupErrorHandling(int $mode, array $emailConfig): void{
        $whoops = new Run;

        switch($mode){
            case self::PRODUCTION_MODE:
                $whoops->pushHandler(new CallbackHandler(function ($error, $emailConfig) {
                    $this->logError($error);
                    $this->sendErrorEmail($error, $emailConfig);
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
    
    private function sendErrorEmail($error, $emailConfig): void
    {
        if(is_null($emailConfig))
            return;
        
        if($this->mailer === null)
            $this->setupMailer($emailConfig);


        try{
            $this->mailer->setFrom($emailConfig['from'], self::$APP_NAME);
            $this->mailer->addAddress($emailConfig['to'], $emailConfig['name']);
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
        $this->mailer->Host = $emailConfig['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $emailConfig['user'];
        $this->mailer->Password = $emailConfig['password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $emailConfig['port'];
    }
}