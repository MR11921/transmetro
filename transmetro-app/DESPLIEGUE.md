# Despliegue en Oracle Cloud (Always Free)

Arquitectura: **VM Ampere A1 (Ubuntu)** con Docker → contenedor **Caddy** (HTTPS automático con un dominio `sslip.io`) → contenedor **Laravel + Apache + oci8** → **Oracle Autonomous Database 23ai** (TLS, sin wallet).

Todo es de la capa *Always Free*: no genera cobros mientras se use solo esa capa.

---

## Paso 1. Crear la cuenta (lo hace el dueño del proyecto)

1. Entrar a <https://www.oracle.com/cloud/free/> → **Start for free**.
2. Región de origen sugerida: **Mexico Central (Querétaro)**, la más cercana a Guatemala. No se puede cambiar después.
3. La tarjeta solo se usa para verificar identidad.

## Paso 2. Crear la máquina virtual

Menú ☰ → **Compute → Instances → Create instance**

| Campo | Valor |
|---|---|
| Name | `transmetro-vm` |
| Image | **Canonical Ubuntu 24.04** |
| Shape | **Ampere → VM.Standard.A1.Flex**, 1 OCPU, 6 GB (marcado *Always Free-eligible*) |
| Networking | Crear VCN nueva, subred **pública**, **Assign a public IPv4 address** |
| SSH keys | **Paste public keys** → pegar el contenido de `~/.ssh/transmetro_oci.pub` |

> Si aparece *Out of capacity* para Ampere, intentar otro *Availability Domain* o usar **VM.Standard.E2.1.Micro** (AMD, también gratis, más lenta).

Al terminar, anotar la **Public IP address**.

**Abrir puertos web:** en la instancia → *Subnet* → *Security Lists* → *Default Security List* → **Add Ingress Rules**:
- Source CIDR `0.0.0.0/0`, TCP, Destination port **80**
- Source CIDR `0.0.0.0/0`, TCP, Destination port **443**

## Paso 3. Crear la base de datos

Menú ☰ → **Oracle Database → Autonomous Database → Create Autonomous Database**

| Campo | Valor |
|---|---|
| Display name / Database name | `transmetro` |
| Workload type | **Transaction Processing** |
| Always Free | **Activado** |
| Database version | **23ai** |
| Password (ADMIN) | una contraseña segura (guardarla) |
| Network access | **Secure access from allowed IPs and VCNs only** → IP address = *IP pública de la VM* |
| Require mutual TLS (mTLS) | **Desmarcado** |

Cuando esté *Available*:

1. **Database actions → SQL** (entra como ADMIN) y ejecutar, cambiando la contraseña:

   ```sql
   CREATE USER TRANSMETRO IDENTIFIED BY "Cambie_Esta_Clave_2026";
   GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE TRIGGER,
         CREATE VIEW, CREATE PROCEDURE TO TRANSMETRO;
   GRANT CTXAPP TO TRANSMETRO;
   ALTER USER TRANSMETRO QUOTA UNLIMITED ON DATA;
   ```

   La contraseña debe tener 12–30 caracteres, mayúsculas, minúsculas y números, y no puede contener la palabra TRANSMETRO.

2. **Database connection** → *TLS authentication* = **TLS** → copiar la cadena de **`transmetro_low`** (empieza con `(description=`).

## Paso 4. Configurar y publicar

1. Copiar `docker/env.production.example` a `.env.production` y completar:
   - `APP_KEY`: `php artisan key:generate --show`
   - `APP_URL`: `https://<IP-con-guiones>.sslip.io` (ej. IP `129.146.1.2` → `https://129-146-1-2.sslip.io`)
   - `DB_TNS`: la cadena del paso 3.2
   - `DB_PASSWORD`: la contraseña de TRANSMETRO
2. Publicar (la primera vez con datos de demostración):

   ```bash
   docker/deploy.sh <IP_PUBLICA_VM> --seed
   ```

3. Abrir `https://<IP-con-guiones>.sslip.io`. El certificado HTTPS tarda unos segundos la primera vez.

Para publicar cambios después: `docker/deploy.sh <IP_PUBLICA_VM>` (sin `--seed`). Las migraciones nuevas se aplican solas al arrancar el contenedor.

---

## Operación

| Tarea | Comando (en la VM, carpeta `~/transmetro`) |
|---|---|
| Ver registros | `sudo docker compose logs -f app` |
| Reiniciar | `sudo docker compose restart` |
| Consola Laravel | `sudo docker compose exec app php artisan tinker` |

**Respaldos (RNF-010):** Autonomous Database hace respaldos automáticos diarios con 60 días de retención; se restauran desde la consola (*Backups → Restore*).

## Probar localmente contra Oracle

```bash
docker run -d --name transmetro-oracle -p 1521:1521 -e ORACLE_PASSWORD=<clave> \
  -e APP_USER=transmetro -e APP_USER_PASSWORD=<clave> gvenzl/oracle-free:23-faststart
```

Al usuario se le debe dar `GRANT CTXAPP TO transmetro` (lo necesita `migrate:fresh`). Luego se configura un `.env.oracle` con `DB_CONNECTION=oracle` y se corre `php artisan migrate:fresh --seed --env=oracle`.
