# uplink-ai

## 🧠 概要

uplink-ai は、ユーザーの実施内容をもとに今後のスケジュール（タスク）を自動生成するAIアプリケーションです。

本リポジトリでは、Next.js（フロントエンド） + Laravel（バックエンド） + OpenAI を用いた非同期タスク生成システムをDocker環境で構築しています。

## 🎯 目的

* 開発者向け環境提供
* βテスト用環境の共有

---

## 🏗 技術構成

| 項目         | 内容                |
| ---------- | ----------------- |
| Frontend   | Next.js (Node.js) |
| Backend    | Laravel (PHP 8.4) |
| Database   | PostgreSQL        |
| Queue      | database          |
| AI         | OpenAI API        |
| Web Server | Nginx             |
| Container  | Docker            |

---

## 📦 前提条件

以下がインストールされている必要があります：

* Docker
* Docker Compose

---

## 🚀 セットアップ手順

```bash
# 1. リポジトリをクローン
git clone <your-repository-url>
cd uplink-ai

# 2. Laravel側の.env作成
cp laravel/.env.example laravel/.env

# 3. APP_KEY生成
docker compose run --rm app php artisan key:generate

# 4. コンテナ起動
docker compose up -d --build

# 5. Composerインストール
docker compose exec app composer install

# 6. マイグレーション実行
docker compose exec app php artisan migrate

# 7. フロント依存関係（自動実行されるが念のため）
docker compose exec frontend npm install
```

---

## ▶️ 起動

```bash
docker compose up -d
```

---

## ⏹ 停止

```bash
docker compose down
```

---

## ⚙️ Queue Worker 起動（必須）

```bash
docker compose exec app php artisan queue:work --queue=ai-generation
```

※これを起動しないとAIタスク生成は完了しません

---

## 🌐 アクセス先

| 種別       | URL                   |
| -------- | --------------------- |
| Frontend | http://localhost:3000 |
| API      | http://localhost:8000 |

---

## 🔁 動作確認手順（E2E）

1. フロント（[http://localhost:3000）にアクセス](http://localhost:3000）にアクセス)
2. フォームに入力
3. タスク生成を実行
4. 以下のフローが動くことを確認：

```
フォーム入力
→ POST /api/task-generation-requests
→ request_id取得
→ polling開始
→ QueueでAI処理
→ DB保存
→ completed
→ タスク表示
```

5. 正常時：

* タスク一覧が表示される
* estimated_hours が適切に補正されている
* ログが表示される

---

## 🔐 環境変数

`.env` を作成し、以下を設定してください：

```env
OPENAI_API_KEY=your-api-key
```

※ `.env.example` をベースにコピーしてください

---

## ⚠️ 注意点

### 1. Queueが動いていないと完了しない

AI生成は非同期処理のため、必ず以下を実行：

```bash
php artisan queue:work --queue=ai-generation
```

---

### 2. OpenAI APIキーが必要

APIキー未設定の場合、タスク生成は失敗します

---

### 3. 初回起動は時間がかかる

* npm install
* composer install

---

### 4. ポート競合

以下のポートを使用します：

* 3000（Frontend）
* 8000（API）
* 5433（PostgreSQL）

---

## 🧩 補足（設計思想）

* AIは非同期前提
* pollingで状態管理
* AIの出力は信用しない（validation + normalize）
* ログファースト設計

---

## 🧠 現在の状態

* MVP完成
* エンドツーエンド動作確認済み
* 非同期AIタスク生成機能 実装済み

---

## 🚧 今後の改善

* プロンプト最適化
* タスク精度向上
* UI改善
* ログ活用強化

---
