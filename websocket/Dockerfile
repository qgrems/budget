FROM node:23.6.1
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
EXPOSE 3030
CMD ["node", "server.js"]