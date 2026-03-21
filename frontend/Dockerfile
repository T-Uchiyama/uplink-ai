FROM node:22-alpine

WORKDIR /app

CMD ["sh", "-c", "if [ -f package.json ]; then npm install && npm run dev; else tail -f /dev/null; fi"]