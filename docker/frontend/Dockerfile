FROM node:20

WORKDIR /app

# Limpar cache do npm para evitar problemas de versão
RUN npm cache clean --force

# Instalar versões específicas e compatíveis com Angular 19
RUN npm install -g @angular/cli@19
RUN npm install -g @angular-devkit/build-angular@19.2.3

# Copiar package.json primeiro para aproveitar o cache do Docker
COPY frontend/package*.json ./

# Instalar dependências do projeto
RUN npm install

# Copiar o resto do código
COPY frontend/ .

# Script para iniciar o Angular
COPY docker/frontend/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 4200
CMD [ "/start.sh" ]
