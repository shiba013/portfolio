-- Portfolio database schema
-- MySQL / MariaDB

SET NAMES utf8mb4;
-- 削除用
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS case_skills;
DROP TABLE IF EXISTS work_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS cases;
DROP TABLE IF EXISTS works;
DROP TABLE IF EXISTS contacts;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE works (
  -- 制作実績id
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- 制作実績のタイトル
  title VARCHAR(255) NOT NULL,

  -- URLで使用する識別子 例: laravel-business-system
  slug VARCHAR(255) NOT NULL UNIQUE,

  -- 制作物の分類 例: Backend, Frontend, Web App
  category VARCHAR(100) NULL,

  -- 一覧やトップページで表示する短い説明文
  summary TEXT NULL,

  -- 詳細ページで表示する長い説明文
  description LONGTEXT NULL,

  -- サムネイル画像の保存パス 例: works/laravel-system.webp
  thumbnail VARCHAR(255) NULL,

  -- 制作期間
  period VARCHAR(50) NULL,

  -- GitHubリポジトリのURL
  github_url VARCHAR(500) NULL,

  -- 公開サイトやデモサイトのURL
  site_url VARCHAR(500) NULL,

  -- 公開状態を管理するフラグ 1: 公開, 0: 非公開
  is_public TINYINT(1) NOT NULL DEFAULT 1,

  -- トップページなどで注目表示するか 1: 表示, 0: 通常
  is_featured TINYINT(1) NOT NULL DEFAULT 0,

  -- 表示順を管理する数値 小さいほど先に表示
  sort_order INT NOT NULL DEFAULT 0,

  -- レコード作成日時
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- レコード更新日時
  updated_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  -- 公開中の制作実績を表示順で取得しやすくするためのインデックス
  INDEX idx_works_public_sort_id (
    is_public,
    sort_order,
    id
  ),

  -- 注目表示の制作実績を取得しやすくするためのインデックス
  INDEX idx_works_public_featured_sort_id (
    is_public,
    is_featured,
    sort_order,
    id
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cases (
  -- ケース記事id
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- ケース記事のタイトル
  title VARCHAR(255) NOT NULL,

  -- URLで使用する識別子 例: php83-migration
  slug VARCHAR(255) NOT NULL UNIQUE,

  -- 一覧やトップページで表示する短い説明文
  summary TEXT NULL,

  -- 発生した問題やトラブルの内容
  problem LONGTEXT NULL,

  -- 原因調査の内容
  investigation LONGTEXT NULL,

  -- 実施した解決策
  solution LONGTEXT NULL,

  -- 解決後の結果や改善内容
  result LONGTEXT NULL,

  -- ケース詳細で表示するビジュアル表現の種別 例: terminal, architecture, sql, diff
  visual_type VARCHAR(50) NOT NULL,

  -- ケース詳細で表示するログや構成図などの本文
  visual_body LONGTEXT NOT NULL,

  -- 公開状態を管理するフラグ 1: 公開, 0: 非公開
  is_public TINYINT(1) NOT NULL DEFAULT 1,

  -- トップページなどで注目表示するか 1: 表示, 0: 通常
  is_featured TINYINT(1) NOT NULL DEFAULT 0,

  -- 表示順を管理する数値 小さいほど先に表示
  sort_order INT NOT NULL DEFAULT 0,

  -- レコード作成日時
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- レコード更新日時
  updated_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  -- 公開中のケース記事を表示順で取得しやすくするためのインデックス
  INDEX idx_cases_public_sort_id (
    is_public,
    sort_order,
    id
  ),

  -- 注目表示のケース記事を取得しやすくするためのインデックス
  INDEX idx_cases_publish_featured_sort_id (
    is_public,
    is_featured,
    sort_order,
    id
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE skills (
  -- スキルid
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- スキル名 例: PHP, Slim, MySQL
  name VARCHAR(100) NOT NULL,

  -- URLや内部処理で使用する識別子 例: php, slim, mysql
  slug VARCHAR(100) NOT NULL UNIQUE,

  -- スキル分類 例: Backend, Frontend, Database
  category VARCHAR(50) NOT NULL,

  -- 表示順を管理する数値 小さいほど先に表示
  sort_order INT NOT NULL DEFAULT 0,

  -- 公開状態を管理するフラグ 1: 公開, 0: 非公開
  is_public TINYINT(1) NOT NULL DEFAULT 1,

  -- レコード作成日時
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- レコード更新日時
  updated_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  -- 公開中のスキルを表示順で取得しやすくするためのインデックス
  INDEX idx_skills_public_sort_id (
    is_public,
    sort_order,
    id
  ),

  -- カテゴリごとにスキルを表示順で取得しやすくするためのインデックス
  INDEX idx_skills_public_category_sort_id (
    is_public,
    category,
    sort_order,
    id
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_skills (
  -- 紐づけ対象の制作実績ID
  work_id BIGINT UNSIGNED NOT NULL,

  -- 紐づけ対象のスキルID
  skill_id BIGINT UNSIGNED NOT NULL,

  -- 同じ制作実績とスキルの組み合わせを重複登録しないための主キー
  PRIMARY KEY (
    work_id,
    skill_id
  ),

  -- スキルに基づく制作実績を取得しやすくするためのインデックス
  INDEX idx_work_skills_skill_work (
    skill_id,
    work_id
  ),

  -- 制作実績が削除されたら紐づく関連データも削除する
  FOREIGN KEY (work_id)
    REFERENCES works(id)
    ON DELETE CASCADE,

  -- スキルが削除されたら紐づく関連データも削除する
  FOREIGN KEY (skill_id)
    REFERENCES skills(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_skills (
  -- 紐づけ対象のケース記事ID
  case_id BIGINT UNSIGNED NOT NULL,

  -- 紐づけ対象のスキルID
  skill_id BIGINT UNSIGNED NOT NULL,

  -- 同じケース記事とスキルの組み合わせを重複登録しないための主キー
  PRIMARY KEY (
    case_id,
    skill_id
  ),

  -- スキルに基づく事例を取得しやすくするためのインデックス
  INDEX idx_case_skills_skill_case (
    skill_id,
    case_id
  ),

  -- ケース記事が削除されたら紐づく関連データも削除する
  FOREIGN KEY (case_id)
    REFERENCES cases(id)
    ON DELETE CASCADE,

  -- スキルが削除されたら紐づく関連データも削除する
  FOREIGN KEY (skill_id)
    REFERENCES skills(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contacts(
  -- id
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- 問い合わせしてきた人の名前
  name VARCHAR(20) NOT NULL,

  -- 問い合わせしてきた人のメールアドレス
  email VARCHAR(255) NOT NULL,

  -- 問い合わせの件名
  subject VARCHAR(50) NOT NULL,

  -- 問い合わせの内容
  message TEXT NOT NULL,

  -- 対応状態 0: 未対応, 1: 対応中, 2: 対応済み
  status TINYINT UNSIGNED NOT NULL DEFAULT 0,

  -- 自分用のメモ
  memo TEXT NULL,

  -- 問い合わせがあった日時
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- 問い合わせに対してアクションをした日時
  updated_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  -- 管理画面で日付で昇順・降順で並べ替えしやすくする
  INDEX idx_contacts_created_at(
    created_at
  ),

  -- 管理画面で対応状態と日付で並べ替えしやすくする
  INDEX idx_contacts_status_created_at(
    status,
    created_at
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
