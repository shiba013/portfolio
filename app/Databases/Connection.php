<?php

namespace App\Databases;

use PDO;

class Connection
{
  public static function create()
  {
    $dsn = sprintf(
      'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
      $_ENV['DB_HOST'],
      $_ENV['DB_PORT'],
      $_ENV['DB_DATABASE']
    );

    return new PDO(
      $dsn,
      $_ENV['DB_USERNAME'],
      $_ENV['DB_PASSWORD'],
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]
    );
  }
}
