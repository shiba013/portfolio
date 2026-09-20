# Portfolio

PHP / Slim 4 を中心に構成したポートフォリオサイトです。

## 技術構成

```text
PHP 8.4
│
├── Slim 4
│   ├── Routing
│   ├── Middleware
│   └── Controller
│
├── PHP-DI
│   └── Dependency Injection
│
├── Twig 3
│   └── HTML Template
│
├── PDO
│   └── MySQL
│
└── Vite
    ├── JavaScript
    ├── TypeScript
    └── React
```

## 必要環境
以下の環境を事前に用意してください。
- PHP: 8.4
- Composer: 2.x
- MySQL: 8.4
- Node.js: 20.x 以上
- npm: 10.x 以上

バージョン確認は以下のコマンドで行えます。
```sh
php -v
composer --version
mysql --version
node -v
npm -v
```

## セットアップ

### PHPライブラリのインストール
```sh
composer install
composer require \
  slim/slim \
  slim/psr7 \
  slim/twig-view \
  php-di/php-di \
  vlucas/phpdotenv
```

### フロントエンド依存関係のインストール
```sh
npm install
npm install -D vite
npm install react react-dom
npm install -D typescript @types/react @types/react-dom
npm install -D @vitejs/plugin-react
```

### 環境変数の設定
```sh
cp .env.example .env
```
`.env` にデータベース接続情報などを設定してください。

### データベースの起動
MySQL を起動してください。
```sh
mysql -u ユーザー名 -p データベース名
```

### 開発サーバーの起動
```sh
npm run dev
```

### フロントエンドのビルド
```sh
npm run build
```
