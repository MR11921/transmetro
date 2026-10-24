# Despliegue en Render (plan gratuito)

**Versión publicada:** https://transmetro-8daj.onrender.com

Arquitectura: **GitHub** (`MR11921/transmetro`, rama `main`) → **Render** compila el `Dockerfile` → contenedor **Laravel + Apache** → **PostgreSQL** de Render. HTTPS lo da Render automáticamente.

Todo usa el plan **Free** de Render, así que no genera cobros.

---

## Archivos que intervienen

| Archivo | Para qué sirve |
|---|---|
| `../render.yaml` (raíz del repositorio) | *Blueprint*: define el servicio web `transmetro` (Docker) y la base `transmetro-db` (PostgreSQL), y sus variables de entorno |
| `Dockerfile` | Imagen PHP 8.4 + Apache con `pdo_pgsql` y `gd` (para el logo de los PDF). El cliente de Oracle solo se instala con `WITH_ORACLE=true` |
| `docker/entrypoint.sh` | Se ejecuta en cada arranque: ajusta el puerto (`$PORT`), la clave (`APP_KEY`) y la URL, aplica las migraciones y carga los datos de demostración **solo si la base está vacía** |

Variables definidas en `render.yaml`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` (la genera Render), `DB_CONNECTION=pgsql`, `DB_URL` (la toma de `transmetro-db`), `SESSION_DRIVER=database`, `CACHE_STORE=database`, `LOG_CHANNEL=stderr`, `TRUSTED_PROXIES=*` y `SEED_IF_EMPTY=true`.

---

## Primer despliegue (ya realizado)

1. Crear la cuenta en <https://dashboard.render.com/register> con **GitHub** y autorizar el acceso al repositorio.
2. **New → Blueprint** → elegir `MR11921/transmetro`.
3. **Blueprint Name:** `transmetro` · **Branch:** `main` · **Blueprint Path:** vacío (usa `render.yaml`).
4. Render muestra *Create database transmetro-db* y *Create web service transmetro* → **Deploy Blueprint**.
5. La primera compilación tarda de 5 a 10 minutos. Al terminar, el servicio dice **Live** y muestra su dirección `https://…onrender.com`.

En el primer arranque la base está vacía, así que se crean las tablas y se cargan los usuarios de demostración (contraseña `Transmetro2026`, ver README).

---

## Publicar cambios

Cada `git push` a `main` vuelve a compilar y publicar la app automáticamente:

```bash
git add -A && git commit -m "Describe el cambio" && git push
```

- Las **migraciones nuevas** se aplican solas al arrancar. Si una migración falla, el contenedor nuevo no arranca y Render mantiene la versión anterior.
- Los **datos se conservan** entre despliegues: la carga de demostración solo corre si no hay ningún usuario.
- Para confirmar la versión publicada: Render → servicio **transmetro** → *Events* → el último deploy debe decir **Live** con el commit esperado.

Antes de subir, conviene correr `php artisan test` y, si cambió el `Dockerfile` o una migración, probar la imagen localmente (ver abajo).

---

## Límites del plan gratuito

| Límite | Efecto |
|---|---|
| El servicio se duerme tras unos 15 minutos sin visitas | La primera visita después tarda de 30 a 60 segundos |
| La base PostgreSQL gratuita vence a los 30 días (creada el 22/10/2026) | Hacia el 21/11/2026 Render la elimina, salvo que se pase a un plan de pago |
| Sin respaldos automáticos en el plan gratuito | Para RNF-010, exportar los datos antes del vencimiento o usar un plan con respaldos |

---

## Versión local vs. publicada

| | Local (`php artisan serve` o el preview) | Render |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| Base de datos | SQLite (`database/database.sqlite`) | PostgreSQL `transmetro-db` |
| Aviso | Franja amarilla **"Versión local"** y pestañas con `[LOCAL]` | Sin aviso |

Los datos de cada copia son independientes: lo que se registra en local no aparece en Render.

---

## Probar la imagen localmente (opcional)

Con Docker Desktop abierto, desde `transmetro-app/`:

```bash
docker network create tm-net
docker run -d --name tm-pg --network tm-net -e POSTGRES_USER=transmetro -e POSTGRES_PASSWORD=prueba123 -e POSTGRES_DB=transmetro postgres:16
docker build -t transmetro-render .
docker run -d --name tm-app --network tm-net -p 8099:10000 -e PORT=10000 -e APP_ENV=production -e APP_KEY=cualquier-texto-largo -e DB_CONNECTION=pgsql -e DB_URL=postgresql://transmetro:prueba123@tm-pg:5432/transmetro -e SESSION_DRIVER=database -e CACHE_STORE=database -e SEED_IF_EMPTY=true transmetro-render
```

Abrir http://localhost:8099 y, al terminar, borrar los contenedores:

```bash
docker rm -f tm-app tm-pg && docker network rm tm-net
```

---

## Operación

| Tarea | Dónde |
|---|---|
| Ver registros (logs) | Render → servicio **transmetro** → *Logs* |
| Reiniciar | Render → servicio → *Manual Deploy → Restart service* |
| Volver a una versión anterior | Render → servicio → *Events* → *Rollback* en un deploy anterior |
| Consola de la base | Render → **transmetro-db** → *Connect* (cadena `psql`) |

---

## Alternativa descartada: Oracle Cloud

Primero se preparó un despliegue en **Oracle Cloud Always Free** (VM Ampere + Autonomous Database 23ai, con Caddy y `sslip.io`). Se descartó porque la región asignada no tenía capacidad para crear la máquina virtual. Después se intentó **Railway**, pero estaba caído para cuentas de prueba, y por eso se usó Render.

Los archivos de esa opción siguen en el repositorio por si se retoma: `docker-compose.yml`, `docker/Caddyfile`, `docker/deploy.sh` y `docker/env.production.example`. Para construir la imagen con Oracle se usa `WITH_ORACLE=true` (así lo hace `docker-compose.yml`).

Para probar contra Oracle en local:

```bash
docker run -d --name transmetro-oracle -p 1521:1521 -e ORACLE_PASSWORD=<clave> \
  -e APP_USER=transmetro -e APP_USER_PASSWORD=<clave> gvenzl/oracle-free:23-faststart
```

Al usuario se le debe dar `GRANT CTXAPP TO transmetro` (lo necesita `migrate:fresh`). Luego se configura un `.env.oracle` con `DB_CONNECTION=oracle` y se corre `php artisan migrate:fresh --seed --env=oracle`.
