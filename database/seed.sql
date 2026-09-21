INSERT INTO skills
(name, slug, category, sort_order)
VALUES
('PHP', 'php', 'Backend', 1),
('Laravel', 'laravel', 'Backend', 2),
('Slim', 'slim', 'Backend', 3),

('HTML', 'html', 'Frontend', 1),
('CSS', 'css', 'Frontend', 2),
('SCSS', 'scss', 'Frontend', 3),
('JavaScript', 'javascript', 'Frontend', 4),
('TypeScript', 'typescript', 'Frontend', 5),
('React', 'react', 'Frontend', 6),

('MySQL', 'mysql', 'Database', 1),

('Linux', 'linux', 'Infrastructure & DevOps', 1),
('nginx', 'nginx', 'Infrastructure & DevOps', 2),
('Docker', 'docker', 'Infrastructure & DevOps', 3),

('Cloudflare', 'cloudflare', 'Cloud', 1),

('virtualbox', 'virtualbox', 'Virtualization', 1),
('UTM', 'UTM', 'Virtualization', 2);

INSERT INTO works
(
  title,
  slug,
  category,
  summary,
  period,
  is_public,
  is_featured,
  sort_order
)
VALUES
(
  '業務管理システムの新規構築',
  'laravel-business-system',
  'Full Stack',
  'Laravelを使用して業務管理システムを構築。権限管理・検索・集計機能などを実装。',
  '約1ヶ月',
  1,
  1,
  1
),
(
  'コーポレートサイトリニューアル',
  'corporate-site-react',
  'Frontend',
  'React・TypeScriptを使用したコーポレートサイトのリニューアル。',
  '約2週間',
  1,
  1,
  2
),
(
  'Webサイト構築・カスタマイズ',
  'wordpress-customize',
  'Web Development',
  'CMSを利用したWebサイト構築と独自機能の開発。',
  '約2週間',
  1,
  1,
  3
),
(
  'REST API設計・開発',
  'rest-api-development',
  'Backend',
  'PHPを使用したREST APIの設計・開発。',
  '約1週間',
  1,
  1,
  4
);


INSERT INTO cases
(
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
)
VALUES
(
  'PHP 7.4 → PHP 8.3 移行',
  'legacy-system-migration',
  'レガシーCMSのPHPバージョンアップ対応。互換性問題を解決して安定稼働を実装。',
  'PHP 7.4で稼働していたレガシーCMSをPHP 8環境へ移行。Smarty / Zend Frameworkなどで多数の互換性問題が発生。',
  'Error Log, PHP compatibility, Smarty Resource, Database Layer',
  'Zend_Dbへの移行、Smarty Resourceの改修、PHP8非互換コードの修正',
  '✓ PHP 8.3対応, ✓ 既存機能を維持, ✓ サーバー移行完了',
  'terminal',
  '$ php -v
PHP 8.3.2

$ composer check-platform-reqs
php           8.3.2   success
ext-json      8.3.2   success
ext-pdo       8.3.2   success

$ tail -f storage/logs/error.log
Fatal error: Declaration of LegacyResource::fetch()
must be compatible with Smarty_Resource_Custom::fetch()

# fix
- Smarty Resource class のメソッドシグネチャをPHP8対応
- Zend_Db adapter 周りの型エラーを修正
- deprecated / warning を段階的に解消

$ ./vendor/bin/phpunit
Tests: 84, Assertions: 212, Failures: 0',
  1,
  1,
  1
),
(
  'MySQL パフォーマンス改善',
  'mysql-performance',
  'スロークエリの分析・INDEX最適化によりDB負荷を大幅に削減。',
  'LoadAverage50以上まで上昇し、Webサイト閲覧時パフォーマンスに大幅に影響。',
  'slow query log, SHOW PROCESSLIST, EXPLAIN',
  '複合INDEXの追加とSQLの見直しにより、検索条件に合った実行計画へ改善',
  'INDEX追加・SQL改善を試みたことにより複合INDEX不足が解消された',
  'sql',
  '$ mysqldumpslow -s t /var/log/mysql/slow.log
Count: 124  Time=8.42s  SELECT * FROM access_logs WHERE site_id = ? AND created_at BETWEEN ? AND ?

mysql> EXPLAIN SELECT * FROM access_logs WHERE site_id = 1 AND created_at >= ''2026-01-01'';
+------+-------------+-------------+------+---------------+------+---------+------+--------+-------------+
| type | possible_keys | key       | rows | Extra         |
+------+-------------+-------------+------+---------------+------+---------+------+--------+-------------+
| ALL  | NULL          | NULL      | 982134 | Using where  |
+------+-------------+-------------+------+---------------+------+---------+------+--------+-------------+

mysql> ALTER TABLE access_logs ADD INDEX idx_site_created_at (site_id, created_at);

mysql> EXPLAIN SELECT * FROM access_logs WHERE site_id = 1 AND created_at >= ''2026-01-01'';
+------+---------------------+---------------------+-------+-------------+
| type | key                 | rows                | Extra |
+------+---------------------+---------------------+-------+-------------+
| range| idx_site_created_at | 18420               | Using index condition |
+------+---------------------+---------------------+-------+-------------+',
  1,
  1,
  2
),
(
  'サーバー移転・環境構築',
  'server-migration',
  '共有サーバーからVPSへ移行。nginx / SSL / Docker構成を最適化。',
  '自社管理している各サイトの冗長化および死活監視の導入を実施',
  'Docker swarm導入・構築、死活監視としてZabbixを導入・設定',
  'nginx / SSL / Docker環境を再構成し、Zabbixによる状態監視とパフォーマンス監視を設定',
  'コンテナ運用により冗長化を確立、状態監視だけでなくパフォーマンス監視も実装',
  'architecture',
  '$ docker node ls
HOSTNAME       STATUS   AVAILABILITY   MANAGER STATUS
vps-manager    Ready    Active         Leader
vps-worker-1   Ready    Active
vps-worker-2   Ready    Active

$ docker service ls
NAME              MODE        REPLICAS   IMAGE
web_nginx         replicated  2/2        nginx:stable
app_php           replicated  2/2        php:8.3-fpm
monitor_zabbix    replicated  1/1        zabbix/zabbix-server

[ users ]
    |
[ nginx / SSL ]
    |
[ Docker Swarm ]
    |-- app_php replica 1
    |-- app_php replica 2
    |-- mysql
    |-- zabbix monitoring',
  1,
  1,
  3
),
(
  'サイト高速化・改善',
  'site-speed',
  'キャッシュ・画像最適化・不要リクエスト削減で表示速度を改善',
  '画像の読込み速度を高速化することで、さらなるパフォーマンス改善を目指す',
  'キャッシュの保存期間の調整、画像サイズ変換時の処理を修正',
  'キャッシュ設定の見直し、画像変換処理の修正、不要なリクエストの削減を実施',
  'スムーズなブラウザ表示を実現',
  'checklist',
  '$ curl -I https://example.com/assets/main.webp
HTTP/2 200
cache-control: public, max-age=31536000, immutable
content-type: image/webp

# optimization checklist
[x] 画像変換後のサイズを再調整
[x] 静的アセットのキャッシュ期間を延長
[x] 不要な外部リクエストを削減
[x] 表示確認とレスポンスヘッダー確認',
  1,
  1,
  4
);

INSERT INTO case_skills
(case_id, skill_id)
VALUES
(1, 1),
(2, 10),
(3, 10),
(3, 11),
(3, 12),
(3, 13),
(3, 14),
(4, 1),
(4, 10);

INSERT INTO work_skills
(work_id, skill_id)
VALUES
(1, 1),
(1, 2),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 10),
(2, 1),
(2, 6),
(2, 8),
(2, 9),
(3, 1),
(3, 4),
(3, 5),
(4, 1),
(4, 2);
