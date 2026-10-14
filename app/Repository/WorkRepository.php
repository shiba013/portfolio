<?php

namespace App\Repository;

use PDO;

class WorkRepository
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * 公開中の実績
   */
  public function publishedWorks()
  {
    $sql = "SELECT id, title, slug, category, summary, thumbnail
            FROM works
            WHERE is_public = 1
            ORDER BY
              sort_order ASC,
              id DESC";

    $works = $this->pdo->query($sql)->fetchAll();
    return $this->withSkills($works);
  }


  /**
   * トップに出す注目実績を取得、それぞれに紐づくスキルも取得
   */
  public function featuredWorks()
  {
    $sql = "SELECT id, title, slug, category, summary, thumbnail
            FROM works
            WHERE is_public = 1
            AND is_featured = 1
            ORDER BY
              sort_order ASC,
              id DESC
            LIMIT 4";

    $works = $this->pdo->query($sql)->fetchAll();
    return $this->withSkills($works);
  }


  /**
   * 詳細画面へ遷移するためにslugを基準に実績を取得
   */
  public function findPublishedSlug($slug)
  {
    $sql = "SELECT id, title, slug, category, summary,
              description, thumbnail, period, github_url, site_url
            FROM works
            WHERE slug = :slug
            AND is_public = 1
            LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['slug' => $slug]);
    $work = $stmt->fetch();

    if (!$work) {
      return null;
    }

    // スキルを一緒に表示
    $works = $this->withSkills([$work]);
    return $works[0];
  }


  /**
   * 各実績に紐づくスキル取得
   */
  private function withSkills($works)
  {
    // worksが空ならスキルを返さない
    if (empty($works)) {
      return [];
    }

    // 取得した実績一覧に'skills'という空配列追加
    foreach ($works as &$work) {
      $work['skills'] = [];
    }
    // 配列追加をやめる
    unset($work);

    // idだけ取得
    $workIds = array_column($works, 'id');

    // 取得したidをプレースホルダーとしてSQLに入れる準備
    $placeholders = implode(',', array_fill(0, count($workIds), '?'));

    // スキル取得
    $sql = "SELECT
              ws.work_id,
              s.id,
              s.name
            FROM work_skills ws
            INNER JOIN skills s
              ON s.id = ws.skill_id
            WHERE ws.work_id IN ({$placeholders})
              AND s.is_public = 1
            ORDER BY
              s.sort_order ASC,
              s.id DESC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($workIds);
    $skills = $stmt->fetchAll();

    // 配列がずれてるから入れ直し
    // $workIndex[work_id] = $worksの配列番号 にしたい
    // $workIndex[$work['id']]と'work_id'を同じにする
    $workIndex = [];
    foreach ($works as $index => $work) {
      $workIndex[$work['id']] = $index;
    }

    // 取得したスキルがどの実績に紐づくかを取り出す
    foreach ($skills as $skill) {
      $workId = $skill['work_id'];

      // スキルに対応する実績が存在しなかったらスキル処理をスキップ
      // これいらんかも
      if (!isset($workIndex[$workId])) {
        continue;
      }

      // 取得したスキルを追加
      $works[$workIndex[$workId]]['skills'][] = [
        'id' => $skill['id'],
        'name' => $skill['name'],
      ];
    }
    return $works;
  }


  /**
   * 実績全件取得
   */
  public function allWorks()
  {
    $sql = "SELECT
              id,
              title,
              category,
              slug,
              is_public,
              sort_order,
              updated_at
            FROM works
            ORDER BY
              id DESC";

    return $this->pdo->query($sql)->fetchAll();
  }


  /**
   * 実績件数取得
   */
  public function workCounts()
  {
    $sql = "SELECT
              COUNT(*) AS total,
              SUM(is_public = 1) AS publish,
              SUM(is_public = 0) AS unpublish
            FROM works";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch();
  }

  /**
   * URLスラッグの重複確認
   */
  public function slugExists(string $slug, ?int $exceptId = null)
  {
    $sql = "SELECT COUNT(*) FROM works WHERE slug = :slug";

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
   * 管理画面の編集用に実績と選択済みスキルIDを取得
   */
  public function findAdminById(int $id)
  {
    $sql = "SELECT
              id,
              title,
              slug,
              category,
              summary,
              description,
              thumbnail,
              period,
              github_url,
              site_url,
              is_public,
              is_featured,
              sort_order
            FROM works
            WHERE id = :id
            LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $work = $stmt->fetch();

    if (!$work) {
      return null;
    }

    $skillStmt = $this->pdo->prepare(
      "SELECT skill_id
        FROM work_skills
        WHERE work_id = :work_id"
    );
    $skillStmt->execute(['work_id' => $id]);
    $work['skill_ids'] = array_map('intval', $skillStmt->fetchAll(PDO::FETCH_COLUMN));

    return $work;
  }

  /**
   * 実績と使用スキルを登録
   */
  public function create(array $data, array $skillIds = [])
  {
    $this->pdo->beginTransaction();

    try {
      $sql = "INSERT INTO works (
                title,
                slug,
                category,
                summary,
                description,
                thumbnail,
                period,
                github_url,
                site_url,
                is_public,
                is_featured,
                sort_order
              ) VALUES (
                :title,
                :slug,
                :category,
                :summary,
                :description,
                :thumbnail,
                :period,
                :github_url,
                :site_url,
                :is_public,
                :is_featured,
                :sort_order
              )";

      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($this->workParameters($data));
      $workId = (int) $this->pdo->lastInsertId();

      $this->insertSkills($workId, $skillIds);
      $this->pdo->commit();

      return $workId;
    } catch (\Throwable $error) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      throw $error;
    }
  }

  /**
   * 実績と使用スキルを更新
   */
  public function update(int $id, array $data, array $skillIds = [])
  {
    $this->pdo->beginTransaction();

    try {
      $sql = "UPDATE works SET
                title = :title,
                slug = :slug,
                category = :category,
                summary = :summary,
                description = :description,
                thumbnail = :thumbnail,
                period = :period,
                github_url = :github_url,
                site_url = :site_url,
                is_public = :is_public,
                is_featured = :is_featured,
                sort_order = :sort_order
              WHERE id = :id";

      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['id' => $id] + $this->workParameters($data));

      $deleteStmt = $this->pdo->prepare(
        "DELETE FROM work_skills WHERE work_id = :work_id"
      );
      $deleteStmt->execute(['work_id' => $id]);
      $this->insertSkills($id, $skillIds);

      $this->pdo->commit();
    } catch (\Throwable $error) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      throw $error;
    }
  }

  /**
   * 実績と紐づくスキル情報を削除
   */
  public function delete(int $id)
  {
    $this->pdo->beginTransaction();

    try {
      $skillStmt = $this->pdo->prepare(
        "DELETE FROM work_skills WHERE work_id = :work_id"
      );
      $skillStmt->execute(['work_id' => $id]);

      $workStmt = $this->pdo->prepare("DELETE FROM works WHERE id = :id");
      $workStmt->execute(['id' => $id]);
      $deleted = $workStmt->rowCount() > 0;

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
  private function workParameters(array $data)
  {
    return [
      'title' => $data['title'],
      'slug' => $data['slug'],
      'category' => $data['category'],
      'summary' => $data['summary'],
      'description' => $data['description'],
      'thumbnail' => $data['thumbnail'],
      'period' => $data['period'],
      'github_url' => $data['github_url'],
      'site_url' => $data['site_url'],
      'is_public' => $data['is_public'],
      'is_featured' => $data['is_featured'],
      'sort_order' => $data['sort_order'],
    ];
  }

  /**
   * 実績に紐づくスキルを登録
   */
  private function insertSkills(int $workId, array $skillIds)
  {
    if ($skillIds === []) {
      return;
    }

    $stmt = $this->pdo->prepare(
      "INSERT INTO work_skills (work_id, skill_id)
        VALUES (:work_id, :skill_id)"
    );

    foreach ($skillIds as $skillId) {
      $stmt->execute([
        'work_id' => $workId,
        'skill_id' => $skillId,
      ]);
    }
  }
}
