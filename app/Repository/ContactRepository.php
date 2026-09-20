<?php

namespace App\Repository;

use PDO;

class ContactRepository
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * 問い合わせを保存
   */
  public function create($form)
  {
    $sql = "INSERT INTO contacts
              (name, email, subject, message)
            VALUES
              (:name, :email, :subject, :message)";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      'name' => $form['name'],
      'email' => $form['email'],
      'subject' => $form['subject'],
      'message' => $form['message']
    ]);
  }
}
