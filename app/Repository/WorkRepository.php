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
}
