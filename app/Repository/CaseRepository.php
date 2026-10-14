<?php

namespace App\Repository;

use PDO;

class CaseRepository
{
  private PDO $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * 公開中の事例一覧
   */
  public function publishedCases()
  {
    $sql = "SELECT id, title, slug, summary
            FROM cases
            WHERE is_public = 1
            ORDER BY
              sort_order ASC,
              id DESC";

    $cases = $this->pdo->query($sql)->fetchAll();
    return $this->withSkills($cases);
  }


  /**
   * トップに表示する注目事例
   */
  public function featuredCases()
  {
    $sql = "SELECT id, title, slug, summary
            FROM cases
            WHERE is_public = 1
            AND is_featured = 1
            ORDER BY
              sort_order ASC,
              id DESC
            LIMIT 4";

    $cases = $this->pdo->query($sql)->fetchAll();
    return $this->withSkills($cases);
  }

  /**
   * 詳細画面へ遷移するためにslugを基準に事例を取得
   */
  public function findPublishedSlug($slug)
  {
    $sql = "SELECT
              c.id,
              c.title,
              c.slug,
              c.summary,
              c.problem,
              c.investigation,
              c.solution,
              c.result,
              c.visual_type,
              c.visual_body
            FROM cases c
            WHERE c.slug = :slug
            AND c.is_public = 1
            LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['slug' => $slug]);
    $case = $stmt->fetch();

    if (!$case) {
      return null;
    }

    // スキルを一緒に表示
    $cases = $this->withSkills([$case]);
    return $cases[0];
  }


  /**
   * 各事例に紐づくスキル取得
   */
  private function withSkills($cases)
  {
    // casesが空ならスキルを返さない
    if (empty($cases)) {
      return [];
    }

    // 取得した事例一覧に'skills'という空配列追加
    foreach ($cases as &$case) {
      $case['skills'] = [];
    }
    // 配列追加をやめる
    unset($case);

    // idだけ取得
    $caseIds = array_column($cases, 'id');

    // 取得したidをプレースホルダーとしてSQLに入れる準備
    $placeholders = implode(',', array_fill(0, count($caseIds), '?'));

    // スキル取得
    $sql = "SELECT
              cs.case_id,
              s.id,
              s.name
            FROM case_skills cs
            INNER JOIN skills s
              ON s.id = cs.skill_id
            WHERE cs.case_id IN ({$placeholders})
              AND s.is_public = 1
            ORDER BY
              s.sort_order ASC,
              s.id DESC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($caseIds);
    $skills = $stmt->fetchAll();

    // 配列がずれてるから入れ直し
    // $caseIndex[case_id] = $casesの配列番号 にしたい
    // $caseIndex[$case['id']]と'case_id'を同じにする
    $caseIndex = [];
    foreach ($cases as $index => $case) {
      $caseIndex[$case['id']] = $index;
    }

    // 取得したスキルがどの事例に紐づくかを取り出す
    foreach ($skills as $skill) {
      $caseId = $skill['case_id'];

      // スキルに対応する事例が存在しなかったらスキル処理をスキップ
      // これいらんかも
      if (!isset($caseIndex[$caseId])) {
        continue;
      }

      // 取得したスキルを追加
      $cases[$caseIndex[$caseId]]['skills'][] = [
        'id' => $skill['id'],
        'name' => $skill['name']
      ];
    }
    return $cases;
  }


  /**
   * 事例全件取得
   */
  public function allCases()
  {
    $sql = "SELECT id, title, slug, is_public, sort_order, updated_at
            FROM cases
            ORDER BY
              id DESC";

    return $this->pdo->query($sql)->fetchAll();
  }


  /**
   * 事例数取得
   */
  public function caseCounts()
  {
    $sql = "SELECT
              COUNT(*) AS total,
              SUM(is_public = 1) AS publish,
              SUM(is_public = 0) AS unpublish
            FROM cases";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch();
  }

  /**
   * URLスラッグの重複確認
   */
  public function slugExists(string $slug, ?int $exceptId = null)
  {
    $sql = "SELECT COUNT(*) FROM cases WHERE slug = :slug";
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
   * 管理画面の編集用に事例と選択済みスキルIDを取得
   */
  public function findAdminById(int $id)
  {
    $sql = "SELECT
              id,
              title,
              slug,
              summary,
              problem,
              investigation,
              solution,
              result,
              visual_type,
              visual_body,
              is_public,
              is_featured,
              sort_order
            FROM cases
            WHERE id = :id
            LIMIT 1";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $case = $stmt->fetch();

    if (!$case) {
      return null;
    }

    $skillStmt = $this->pdo->prepare(
      "SELECT skill_id
        FROM case_skills
        WHERE case_id = :case_id"
    );
    $skillStmt->execute(['case_id' => $id]);
    $case['skill_ids'] = array_map('intval', $skillStmt->fetchAll(PDO::FETCH_COLUMN));

    return $case;
  }

  /**
   * 事例と使用スキルを登録
   */
  public function create(array $data, array $skillIds = [])
  {
    $this->pdo->beginTransaction();

    try {
      $sql = "INSERT INTO cases (
                title,
                slug,
                summary,
                problem,
                investigation,
                solution,
                result,
                visual_type,
                visual_body,
                is_public,
                is_featured,
                sort_order
              ) VALUES (
                :title,
                :slug,
                :summary,
                :problem,
                :investigation,
                :solution,
                :result,
                :visual_type,
                :visual_body,
                :is_public,
                :is_featured,
                :sort_order
              )";

      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($this->caseParameters($data));
      $caseId = (int) $this->pdo->lastInsertId();

      $this->insertSkills($caseId, $skillIds);
      $this->pdo->commit();

      return $caseId;
    } catch (\Throwable $error) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      throw $error;
    }
  }

  /**
   * 事例と使用スキルを更新
   */
  public function update(int $id, array $data, array $skillIds = [])
  {
    $this->pdo->beginTransaction();

    try {
      $sql = "UPDATE cases SET
                title = :title,
                slug = :slug,
                summary = :summary,
                problem = :problem,
                investigation = :investigation,
                solution = :solution,
                result = :result,
                visual_type = :visual_type,
                visual_body = :visual_body,
                is_public = :is_public,
                is_featured = :is_featured,
                sort_order = :sort_order
              WHERE id = :id";

      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['id' => $id] + $this->caseParameters($data));

      $deleteStmt = $this->pdo->prepare(
        "DELETE FROM case_skills WHERE case_id = :case_id"
      );
      $deleteStmt->execute(['case_id' => $id]);
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
   * 事例と紐づくスキル情報を削除
   */
  public function delete(int $id)
  {
    $this->pdo->beginTransaction();

    try {
      $skillStmt = $this->pdo->prepare(
        "DELETE FROM case_skills WHERE case_id = :case_id"
      );
      $skillStmt->execute(['case_id' => $id]);

      $caseStmt = $this->pdo->prepare("DELETE FROM cases WHERE id = :id");
      $caseStmt->execute(['id' => $id]);
      $deleted = $caseStmt->rowCount() > 0;

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
  private function caseParameters(array $data)
  {
    return [
      'title' => $data['title'],
      'slug' => $data['slug'],
      'summary' => $data['summary'],
      'problem' => $data['problem'],
      'investigation' => $data['investigation'],
      'solution' => $data['solution'],
      'result' => $data['result'],
      'visual_type' => $data['visual_type'],
      'visual_body' => $data['visual_body'],
      'is_public' => $data['is_public'],
      'is_featured' => $data['is_featured'],
      'sort_order' => $data['sort_order'],
    ];
  }


  /**
   * 事例に紐づくスキルを登録
   */
  private function insertSkills(int $caseId, array $skillIds)
  {
    if ($skillIds === []) {
      return;
    }

    $stmt = $this->pdo->prepare(
      "INSERT INTO case_skills (case_id, skill_id)
        VALUES (:case_id, :skill_id)"
    );

    foreach ($skillIds as $skillId) {
      $stmt->execute([
        'case_id' => $caseId,
        'skill_id' => $skillId,
      ]);
    }
  }
}
