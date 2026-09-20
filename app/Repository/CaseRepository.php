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
}
