<?php

namespace App\Repository;

use PDO;

class CaseSkillRepository
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }
}
