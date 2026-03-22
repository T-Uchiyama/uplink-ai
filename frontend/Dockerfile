FROM node:22-alpine

WORKDIR /app

EXPOSE 3000

CMD ["sh", "-c", "if [ -f package.json ]; then npm install && npm run dev -- --hostname 0.0.0.0; else tail -f /dev/null; fi"]