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
}
