<?php

use App\Command\Help;
use App\Command\Log;
use App\Command\Me;
use App\Command\Mood;
use App\Command\Pidor;
use App\Handler\Confirmation;
use App\Handler\Command;
use App\Handler\DevModeGuard;
use App\Handler\MessageCounter;
use App\Logger\AbstractLogger;
use App\Logger\Stderr;
use JetBrains\PhpStorm\NoReturn;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Pimple\Container;
use App\Handler\AbstractHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\VarDumper\VarDumper;
use VK\Client\VKApiClient;

/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 1:05
 * Made with <3 by West from Bubuni Team
 */

class App
{
    public static string $dir;
    protected static App $app;
    protected Container $container;

    public function __construct()
    {
        $this->container = $container = new Container();

        $container['logger.default'] = fn(): AbstractLogger => new Stderr();

        $container['request'] = array_merge(
            $_REQUEST, json_decode(file_get_contents('php://input'), true) ?? []
        );

        $container['handlerMap'] = [
            'confirmation' => [Confirmation::class],
            'message_new' => [
                DevModeGuard::class,
                Command::class,
                MessageCounter::class
            ]
        ];

        $container['commandMap'] = [
            'today' => Mood::class,
            'pidor' => Pidor::class,
            'log' => Log::class,
            'me' => Me::class,
            'help' => Help::class
        ];

        $container['vkApi'] = fn(): VKApiClient => new VKApiClient();
        $container['db'] = fn(): PDO => new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $container['rabbitmq'] = fn(): AMQPStreamConnection => new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'],
            $_ENV['RABBITMQ_PORT'],
            $_ENV['RABBITMQ_USER'],
            $_ENV['RABBITMQ_PASSWORD']
        );

        $container['logger'] = function (Container $c)
        {
            $loggerClass = $_ENV['LOGGER_CLASS'];
            if (!class_exists($loggerClass))
            {
                return $c['logger.default'];
            }

            /** @var AbstractLogger $logger */
            $logger = new $loggerClass;
            return $logger->setup() ? $logger : $c['logger.default'];
        };

        $container['phrase.map'] = fn (Container $c): array => require self::$dir . '/src/phrases.php';
        $container['phrase.set'] = function ()
        {
            $monthNumber = idate('m');
            if (in_array($monthNumber, [9, 10]))
            {
                return 'army';
            }

            return 'default';
        };
    }

    public static function setup(string $dir): App
    {
        require_once $dir . '/vendor/autoload.php';

        self::$dir = $dir;
        $app = new App();

        ignore_user_abort(true);
        @ini_set('output_buffering', '0');

        return self::$app = $app;
    }

    public function run(): void
    {
        $this->handleRequest();
    }

    public function handleRequest(): void
    {
        if ($this->getFromRequest('secret') != $_ENV['VK_SECRET'])
        {
            $this->sendError(403, '');
        }

        $type = $this->getFromRequest('type');
        $handlerClassList = $this->container()['handlerMap'][$type] ?? null;

        foreach ($handlerClassList as $handlerClass)
        {
            if (!class_exists($handlerClass))
            {
                $this->sendError(404, 'handler class doesn\'t exist');
            }

            $handler = new $handlerClass($this);
            if (!($handler instanceof AbstractHandler))
            {
                $this->sendError(404, 'handler should inherit AbstractHandler class');
            }

            $handlerResult = $handler->handle();
            if ($handlerResult->shouldBreak())
            {
                $this->sendResponse(200, $handlerResult->getResponse() ?: 'ok');
                break;
            }
        }
        $this->sendResponse(200, 'ok');
    }

    public function sendResponse(int $httpCode, string $body, string $contentType = 'application/json'): void
    {
        header('Content-Type: ' . $contentType . '; charset=utf8', false, $httpCode);
        header('Content-Length: ' . strlen($body));

        echo $body;
        fastcgi_finish_request();
    }

    public function formatException(\Throwable $throwable): string
    {
        $class = get_class($throwable);

        return <<<MSG
        {$class} {$throwable->getMessage()} @ {$throwable->getFile()}:{$throwable->getLine()}
        {$throwable->getTraceAsString()}
        MSG;
    }
    
    /**
     * @psalm-suppress UndefinedAttributeClass
     */
    #[NoReturn]
    public function sendError(int $httpCode, string $errorText): void
    {
        $this->sendResponse($httpCode, $errorText);
        exit();
    }

    public function getFromRequest(string $key): mixed
    {
        return $this->container['request'][$key] ?? null;
    }

    public static function phrase(string $name): string
    {
        $c = self::app()->container();
        $phraseVariantMap = $c['phrase.map'][$name];

        $phrase = $phraseVariantMap[$c['phrase.set']] ?? $phraseVariantMap['default'];
        if (is_array($phrase))
        {
            $phrase = $phrase[array_rand($phrase)];
        }

        return $phrase;
    }

    public static function pluralize(int $count): string
    {
        if ($count == 1)
        {
            return 'one';
        }
        else if ($count > 1 && $count < 5)
        {
            return 'few';
        }
        else
        {
            return 'many';
        }
    }

    public static function app(): self
    {
        return self::$app;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function vkApi(): VKApiClient
    {
        return $this->container()['vkApi'];
    }

    public function logger(): LoggerInterface
    {
        return $this->container()['logger'];
    }

    public static function dump(mixed $var): void
    {
        VarDumper::dump($var);
    }
}