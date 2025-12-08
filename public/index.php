<?php

declare(strict_types=1);

use App\Application\AutomationService;
use App\Config\AppConfig;
use App\Container\Container;
use App\Domain\PTT\PttController;
use App\Http\Controller\ApiController;
use App\Http\JsonResponse;
use App\Http\Router;
use App\Infrastructure\Storage\FileQsoLogRepository;
use App\Infrastructure\WsjtX\WsjtXClient;

require dirname(__DIR__) . '/bootstrap.php';

$container = new Container();

$container->singleton(WsjtXClient::class, static function (): WsjtXClient {
    $host = $_ENV['WSJTX_HOST'] ?? '127.0.0.1';
    $port = (int) ($_ENV['WSJTX_PORT'] ?? 2237);
    return new WsjtXClient($host, $port);
});

$container->singleton(FileQsoLogRepository::class, static fn (): FileQsoLogRepository => new FileQsoLogRepository());
$container->singleton(PttController::class, static fn (Container $c): PttController => new PttController($c->get(WsjtXClient::class)));
$container->singleton(AutomationService::class, static fn (Container $c): AutomationService => new AutomationService(
    $c->get(WsjtXClient::class),
    $c->get(PttController::class),
    $c->get(FileQsoLogRepository::class),
));
$container->singleton(ApiController::class, static fn (Container $c): ApiController => new ApiController(
    $c->get(WsjtXClient::class),
    $c->get(PttController::class),
    $c->get(FileQsoLogRepository::class),
    $c->get(AutomationService::class)
));

$router = new Router();

$router->add('GET', '/api/status', static fn (): mixed => $container->get(ApiController::class)->status());
$router->add('POST', '/api/ptt', static fn (): mixed => $container->get(ApiController::class)->togglePtt());
$router->add('POST', '/api/transmit', static fn (): mixed => $container->get(ApiController::class)->transmit());
$router->add('GET', '/api/logs', static fn (): mixed => $container->get(ApiController::class)->log());

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

