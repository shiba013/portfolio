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

  /**
   * 未対応の問い合わせ件数を出力
   */
  public function findStatusUnhandled()
  {
    $sql = "SELECT COUNT(*)
            FROM contacts
            WHERE status = 0";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  /**
   * 管理画面に表示する問い合わせを新着順で取得
   */
  public function allContacts()
  {
    $sql = "SELECT
              id,
              name,
              email,
              subject,
              message,
              status,
              memo,
              created_at,
              updated_at
            FROM contacts
            ORDER BY
              created_at DESC,
              id DESC";

    return $this->pdo->query($sql)->fetchAll();
  }

  /**
   * 管理画面の詳細表示用に問い合わせを1件取得
   */
  public function findAdminById(int $id)
  {
    $sql = "SELECT
              id,
              name,
              email,
              subject,
              message,
              status,
              memo,
              created_at,
              updated_at
            FROM contacts
            WHERE id = :id
            LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $contact = $stmt->fetch();

    return $contact ?: null;
  }

  /**
   * 問い合わせの対応状況と管理メモを更新
   */
  public function updateManagement(int $id, int $status, ?string $memo)
  {
    $sql = "UPDATE contacts
            SET
              status = :status,
              memo = :memo
            WHERE id = :id";

    $stmt = $this->pdo->prepare($sql);

    return $stmt->execute([
      'id' => $id,
      'status' => $status,
      'memo' => $memo,
    ]);
  }

  /**
   * まだ返信に着手していない問い合わせをすべて取得
   */
  public function needsReply()
  {
    $sql = "SELECT
              id,
              name,
              email,
              subject,
              created_at
            FROM contacts
            WHERE status = 0
            ORDER BY
              created_at ASC,
              id ASC";

    return $this->pdo->query($sql)->fetchAll();
  }

  /**
   * 問い合わせ管理画面の集計値
   *
   * 本日期限は当日受信した未対応・対応中の問い合わせ、
   * 今週の対応は今週更新された対応済みの問い合わせとして集計する。
   */
  public function contactCounts()
  {
    $sql = "SELECT
              COUNT(*) AS total,
              SUM(status = 2) AS handled,
              SUM(status != 2) AS unhandled,
              SUM(status != 2 AND DATE(created_at) = CURRENT_DATE) AS due_today,
              SUM(
                status = 2
                AND YEARWEEK(updated_at, 1) = YEARWEEK(CURRENT_DATE, 1)
              ) AS handled_this_week
            FROM contacts";

    $counts = $this->pdo->query($sql)->fetch();

    return array_map(
      static fn($value): int => (int) ($value ?? 0),
      $counts ?: []
    );
  }
}
