#!/bin/zsh
# Sube Transmetro a la VM de Oracle Cloud y lo (re)inicia con Docker Compose.
# Uso:  docker/deploy.sh <IP_PUBLICA_VM> [--seed]
#   --seed  carga los datos de demostración (solo la primera vez, con la BD vacía)
set -e
IP=${1:?Indique la IP pública de la VM}
SEED=$2
KEY=~/.ssh/transmetro_oci
SSH="ssh -i $KEY -o StrictHostKeyChecking=accept-new ubuntu@$IP"
cd "$(dirname "$0")/.."

[ -f .env.production ] || { echo "Falta .env.production (copie docker/env.production.example)"; exit 1; }

echo "==> Preparando la VM (Docker, firewall)…"
$SSH 'bash -s' <<'EOS'
set -e
if ! command -v docker >/dev/null; then
  curl -fsSL https://get.docker.com | sudo sh
  sudo usermod -aG docker ubuntu
fi
# Las imágenes Ubuntu de OCI bloquean 80/443 con iptables aunque la Security List los permita
for p in 80 443; do
  sudo iptables -C INPUT -p tcp --dport $p -j ACCEPT 2>/dev/null || sudo iptables -I INPUT 6 -p tcp --dport $p -j ACCEPT
done
sudo sh -c 'command -v netfilter-persistent >/dev/null && netfilter-persistent save' || true
mkdir -p ~/transmetro
EOS

echo "==> Copiando el proyecto…"
rsync -az --delete -e "ssh -i $KEY" \
  --exclude vendor --exclude node_modules --exclude .env --exclude '.env.oracle' \
  --exclude 'storage/logs/*' --exclude 'storage/framework/*/*' --exclude database/database.sqlite \
  --exclude graphify-out --exclude .phpunit.result.cache \
  ./ ubuntu@$IP:~/transmetro/

echo "==> Construyendo e iniciando contenedores…"
DOMINIO="${IP//./-}.sslip.io"
$SSH "cd ~/transmetro && echo DOMINIO=$DOMINIO > .env && sudo docker compose up -d --build"

if [ "$SEED" = "--seed" ]; then
  echo "==> Cargando datos de demostración…"
  $SSH "cd ~/transmetro && sudo docker compose exec -T app php artisan db:seed --force"
fi

echo "==> Listo: https://$DOMINIO"
