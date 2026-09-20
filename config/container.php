<?php

use App\Databases\Connection;
use DI\ContainerBuilder;
use function DI\get;
use Twig\TwigFunction;
use Slim\Views\Twig;

// php-DI立上げ
$builder = new ContainerBuilder();

// env読込み
$env = $_ENV['APP_ENV'] ?? 'develop';

// 本番環境と開発環境でキャッシュの有無を分岐
if ($env === 'production') {
  $caches = dirname(__DIR__) . '/storage/caches';

  if (!is_dir($caches)) {
    mkdir($caches, 0775, true);
  }
  $builder->enableCompilation($caches);
}

// php-DI起動
$builder->addDefinitions([
  PDO::class => function () {
    return Connection::create();
  },
  Twig::class => function () {
    $twig = Twig::create(
      dirname(__DIR__) . '/templates',
      [
        'cache' => false,
        'debug' => true,
        'autoescape' => 'html'
      ]
    );

    // ヘッダーにis-activeクラスを付与
    $twig->getEnvironment()->addFunction(
      new TwigFunction('is_active_path', function ($path) {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($path === '/') {
          return $currentPath === '/';
        }

        return $currentPath === $path || str_starts_with($currentPath, $path . '/');
      })
    );

    return $twig;
  },
  'view' => get(Twig::class)
]);

return $builder->build();
