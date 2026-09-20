<?php

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use App\Middleware\ContactFormMiddleware;

// composer読込み
require dirname(__DIR__) . '/vendor/autoload.php';

// session開始・cookie保護
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

// .envの設定
$env = Dotenv::createImmutable(
  dirname(__DIR__)
);
$env->load();

// php-DIの設定
$container = require dirname(__DIR__) . '/config/container.php';

// slimの設定
AppFactory::setContainer($container);
$app = AppFactory::create();

// エラーミドルウェア設定
$app->addRoutingMiddleware();
$app->add(TwigMiddleware::createFromContainer($app));
$app->add(new ContactFormMiddleware());

// $appを渡す
require dirname(__DIR__) . '/config/routes.php';

// エラー表示、ログ、詳細表の有効化設定
if ($_ENV['APP_ENV'] === 'develop') {
  $app->addErrorMiddleware(true, true, true);
} else {
  $app->addErrorMiddleware(false, false, false);
}

$app->run();
