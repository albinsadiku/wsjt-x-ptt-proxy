<?php

declare(strict_types=1);

use App\Application\AutomationService;
use App\Config\AppConfig;
use App\Container\Container;
use App\Domain\Automation\AutomationController;
use App\Domain\PTT\PttController;
use App\Http\Controller\ApiController;
use App\Http\JsonResponse;
use App\Http\Router;
use App\Infrastructure\Storage\FileQsoLogRepository;
use App\Infrastructure\Udp\Listener;
use App\Infrastructure\Udp\Socket;
use App\Infrastructure\WsjtX\Client;
use App\Infrastructure\WsjtX\Messages\Builder;
use App\Infrastructure\WsjtX\Messages\Parser;

require dirname(__DIR__) . '/bootstrap.php';

$container = new Container();

$container->singleton(Socket::class, static function (): Socket {
    $host = $_ENV['WSJTX_HOST'] ?? '127.0.0.1';
    $port = (int) ($_ENV['WSJTX_PORT'] ?? 2237);
    return new Socket($host, $port);
});

$container->singleton(Listener::class, static function (): Listener {
    $host = $_ENV['WSJTX_LISTEN_HOST'] ?? '0.0.0.0';
    $port = (int) ($_ENV['WSJTX_LISTEN_PORT'] ?? 2237);
    return new Listener($host, $port);
});

$container->singleton(Builder::class, static fn (): Builder => new Builder());

$container->singleton(Parser::class, static fn (): Parser => new Parser());

$container->singleton(Client::class, static fn (Container $c): Client => new Client(
    $c->get(Socket::class),
    $c->get(Builder::class)
));

$container->singleton(FileQsoLogRepository::class, static fn (): FileQsoLogRepository => new FileQsoLogRepository());
$container->singleton(PttController::class, static fn (Container $c): PttController => new PttController($c->get(Client::class)));
$container->singleton(AutomationService::class, static fn (Container $c): AutomationService => new AutomationService(
    $c->get(Client::class),
    $c->get(PttController::class),
    $c->get(FileQsoLogRepository::class),
));

$container->singleton(AutomationController::class, static fn (Container $c): AutomationController => new AutomationController(
    $c->get(Client::class),
    $c->get(Parser::class),
    $c->get(Listener::class),
    $c->get(FileQsoLogRepository::class),
    $_ENV['MY_CALL'] ?? '',
    $_ENV['MY_GRID'] ?? ''
));

$container->singleton(ApiController::class, static fn (Container $c): ApiController => new ApiController(
    $c->get(Client::class),
    $c->get(PttController::class),
    $c->get(FileQsoLogRepository::class),
    $c->get(AutomationService::class),
    $c->get(AutomationController::class)
));

$router = new Router();

$router->add('GET', '/api/status', static fn (): mixed => $container->get(ApiController::class)->status());
$router->add('POST', '/api/ptt', static fn (): mixed => $container->get(ApiController::class)->togglePtt());
$router->add('POST', '/api/transmit', static fn (): mixed => $container->get(ApiController::class)->transmit());
$router->add('GET', '/api/logs', static fn (): mixed => $container->get(ApiController::class)->log());
$router->add('POST', '/api/automation/toggle', static fn (): mixed => $container->get(ApiController::class)->toggleAutomation());
$router->add('GET', '/api/automation/status', static fn (): mixed => $container->get(ApiController::class)->automationStatus());

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if (str_starts_with($path, '/api/')) {
        $router->dispatch($method, $path);
        exit;
    }

    $view = AppConfig::basePath('resources/views/home.php');
    if (is_file($view)) {
        require $view;
    } else {
        echo 'View missing.';
    }
} catch (Throwable $e) {
    JsonResponse::send([
        'error' => true,
        'message' => $e->getMessage(),
    ], 400);
}

