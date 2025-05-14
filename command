# 1) ローカルビルド & テスト
docker build --no-cache -t tabiguide:local .
docker run --rm -e PORT=8080 -p 8080:8080 tabiguide:local
# "Listening on http://0.0.0.0:8080" 確認

# 2) Buildx で amd64 イメージ作成 & プッシュ
TAG=$(date +%Y%m%d-%H%M%S)
docker buildx build --no-cache \
  --platform linux/amd64 \
  -t gcr.io/tabiguide/tabiguide:$TAG \
  -t gcr.io/tabiguide/tabiguide:latest \
  --push .

# 3) Cloud Run デプロイ
gcloud run deploy tabiguide-service \
  --image gcr.io/tabiguide/tabiguide:latest \
  --region asia-northeast1 --platform managed \
  --allow-unauthenticated \
  --add-cloudsql-instances=tabiguide:asia-northeast1:tabiguide-db \
  --timeout 300s \
  --command "" --args ""



  docker run --rm   --env-file ./.env.docker   -v "$(pwd)":/workspace   -p 8080:8080   tabiguide:local
  docker run --rm   --env-file .env.docker   -v "$PWD":/workspace   -p 8080:8080  tabiguide:local




google db

appuser
banax8777

プロジェクト:リージョン:インスタンス
tabiguide:asia-northeast1:tabiguide-db


CONNECTION_NAME=tabiguide:asia-northeast1:tabiguide-db


