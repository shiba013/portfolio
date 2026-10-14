<?php

namespace App\Repository;

use PDO;

class SkillRepository
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * 公開中のスキル
   */
  public function publishedSkills()
  {
    $sql = "SELECT id, name, slug, category
            FROM skills
            WHERE is_public = 1
            ORDER BY
              sort_order ASC,
              id DESC";

    return $this->pdo->query($sql)->fetchAll();
  }

  /**
   * カテゴリーごとの公開中のスキル
   */
  public function publishedSkillsCategory()
  {
    $sql = "SELECT id, name, slug, category
            FROM skills
            WHERE is_public = 1
            ORDER BY
              category ASC,
              sort_order ASC,
              id DESC";

    $skills = $this->pdo->query($sql)->fetchAll();

    $group = [];
    foreach ($skills as $skill) {
      $group[$skill['category']][] = $skill;
    }
    return $group;
  }

  /**
   * 特定のスキルに紐づく実績(work)を取得
   */
  public function findByWorkSkill($slug)
  {
    $sql = "SELECT
            w.id,
            w.title,
            w.slug,
            w.category,
            w.summary,
            w.thumbnail
            FROM works w
            INNER JOIN work_skills ws
              ON ws.work_id = w.id
            INNER JOIN skills s
              ON s.id = ws.skill_id
            WHERE s.slug = :slug
              AND s.is_public = 1
              AND w.is_public = 1
            ORDER BY
              w.sort_order ASC
              w.id DESC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['slug', $slug]);
    return $stmt->fetchAll();
  }

  /**
   * 特定のスキルに紐づく事例(case)を取得
   */
  public function findByCaseSkill($slug)
  {
    $sql = "SELECT
              c.id,
              c.title,
              c.slug,
              c.summary
            FROM cases c
            INNER JOIN case_skills cs
              ON cs.case_id = c.id
            INNER JOIN skills s
              ON s.id = cs.skill_id
            WHERE s.slug = :slug
              AND c.is_public = 1
              AND s.is_public = 1
            ORDER BY
              c.sort_order ASC,
              c.id DESC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['slug', $slug]);
    return $stmt->fetchAll();
  }

  /**
   * スキル全件取得
   */
  public function allSkills()
  {
    $sql = "SELECT
              id,
              name,
              slug,
              category,
              sort_order,
              is_public,
              updated_at
            FROM skills
            ORDER BY
              id DESC";

    return $this->pdo->query($sql)->fetchAll();
  }

  /**
   * スキル数取得
   */
  public function skillCounts()
  {
    $sql = "SELECT
              COUNT(*) AS total,
              SUM(is_public = 1) AS publish,
              SUM(is_public = 0) AS unpublish
            FROM skills";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch();
  }

  /**
   * 登録済みのカテゴリー一覧を取得
   */
  public function categories()
  {
    $sql = "SELECT DISTINCT category
            FROM skills
            WHERE category != ''
            ORDER BY category ASC";

    return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
  }

  /**
   * URLスラッグの重複確認
   */
  public function slugExists(string $slug, ?int $exceptId = null)
  {
    $sql = "SELECT COUNT(*) FROM skills WHERE slug = :slug";
    $params = ['slug' => $slug];

    if ($exceptId !== null) {
      $sql .= " AND id != :except_id";
      $params['except_id'] = $exceptId;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
  }

  /**
   * 管理画面の編集用にスキルを取得
   */
  public function findAdminById(int $id)
  {
    $sql = "SELECT
              id,
              name,
              slug,
              category,
              sort_order,
              is_public
            FROM skills
            WHERE id = :id
            LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $skill = $stmt->fetch();

    return $skill ?: null;
  }

  /**
   * スキルを登録
   */
  public function create(array $data)
  {
    $sql = "INSERT INTO skills (
              name,
              slug,
              category,
              sort_order,
              is_public
            ) VALUES (
              :name,
              :slug,
              :category,
              :sort_order,
              :is_public
            )";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($this->skillParameters($data));

    return (int) $this->pdo->lastInsertId();
  }

  /**
   * スキルを更新
   */
  public function update(int $id, array $data)
  {
    $sql = "UPDATE skills SET
              name = :name,
              slug = :slug,
              category = :category,
              sort_order = :sort_order,
              is_public = :is_public
            WHERE id = :id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id] + $this->skillParameters($data));
  }

  /**
   * スキルと実績・事例との紐付けを削除
   */
  public function delete(int $id)
  {
    $this->pdo->beginTransaction();

    try {
      $workStmt = $this->pdo->prepare(
        "DELETE FROM work_skills WHERE skill_id = :skill_id"
      );
      $workStmt->execute(['skill_id' => $id]);

      $caseStmt = $this->pdo->prepare(
        "DELETE FROM case_skills WHERE skill_id = :skill_id"
      );
      $caseStmt->execute(['skill_id' => $id]);

      $skillStmt = $this->pdo->prepare("DELETE FROM skills WHERE id = :id");
      $skillStmt->execute(['id' => $id]);
      $deleted = $skillStmt->rowCount() > 0;

      $this->pdo->commit();
      return $deleted;
    } catch (\Throwable $error) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      throw $error;
    }
  }

  /**
   * フォーム入力値を返す
   */
  private function skillParameters(array $data)
  {
    return [
      'name' => $data['name'],
      'slug' => $data['slug'],
      'category' => $data['category'],
      'sort_order' => $data['sort_order'],
      'is_public' => $data['is_public'],
    ];
  }
}
