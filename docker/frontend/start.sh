#!/bin/bash
set -e

# Instalar dependências se node_modules não existir
if [ ! -d "node_modules" ]; then
  echo "🔄 Instalando dependências do Node..."
  npm install
fi

# Garantir que o @angular-devkit/build-angular esteja instalado
if [ ! -d "node_modules/@angular-devkit/build-angular" ]; then
  echo "🔄 Instalando @angular-devkit/build-angular..."
  npm install --save-dev @angular-devkit/build-angular
fi

# Iniciar o servidor de desenvolvimento Angular
echo "🚀 Iniciando o servidor Angular..."
ng serve --host 0.0.0.0 --configuration production
