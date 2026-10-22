# Transmetro — Sistema de Gestión y Control

Proyecto final de **Aseguramiento de la Calidad** (UMG, octubre 2026) — Alan Mauricio Rodas Escobar.
Implementa la especificación de `../PROYECTO FINAL TRANSMETRO.pdf` (RF-001 a RF-025, RNF, RN-001 a RN-015, modelo ER y script de BD).

**Stack (sección 12 del PDF):** PHP 8 · Laravel 13 (MVC, Blade, HTML/CSS/JS) · Oracle Database · VS Code · metodología en espiral.
En desarrollo se usa SQLite para poder ejecutar sin instalar Oracle. Las migraciones replican tabla por tabla el script Oracle (`database/oracle/transmetro_oracle.sql`).

---

## Cómo ejecutar

```bash
cd transmetro-app
composer install
cp .env.example .env && php artisan key:generate   # solo la primera vez
php artisan migrate:fresh --seed                    # crea la BD y carga datos de demostración
php artisan serve                                   # http://localhost:8000
```

Pruebas automatizadas:

```bash
php artisan test
```

### Usuarios de prueba (contraseña `Transmetro2026`)

| Usuario | Rol | Qué puede hacer |
|---|---|---|
| `admin` | ADMINISTRADOR | Todo, incluidos usuarios y auditoría |
| `transporte` | ADMINISTRADOR_TRANSPORTE | Líneas, estaciones, buses, parqueos, pilotos, recorridos, reportes |
| `operador` | OPERADOR_ESTACION | TAT, tickets, control de acceso, alertas, dispositivos, incidentes |
| `piloto` | PILOTO | Recorridos y Terminal de Control de Pasajeros (TCP), incidentes |
| `guardia` | GUARDIA | Control de acceso e incidentes |

La matriz completa de permisos está en `config/transmetro.php` y se muestra en la pantalla **Usuarios**.

### Usar Oracle en lugar de SQLite

1. Ejecutar `database/oracle/transmetro_oracle.sql` en el esquema de Oracle **o** usar las migraciones.
2. Instalar el controlador: `composer require yajra/laravel-oci8` (requiere la extensión `oci8` de PHP).
3. En `.env`: `DB_CONNECTION=oracle`, `DB_HOST`, `DB_PORT=1521`, `DB_SERVICE_NAME`, `DB_USERNAME`, `DB_PASSWORD`.

---

## Estructura

```
app/
  Http/Controllers/     Un controlador por módulo (Líneas, Buses, Recorridos, Tickets…)
  Http/Middleware/      VerificarModulo → permisos por rol (RN-015)
  Models/               Un modelo por tabla del script Oracle
  Services/             Reglas de negocio
    GestionFlota.php      RN-004, RN-005, RN-006
    ControlOcupacion.php  TCP: RN-010, RN-011, RN-012
    ControlAcceso.php     TAT y validación de tickets: RN-013
    Auditor.php           Bitácora (RF-024, RN-014)
  Support/Catalogos.php CRUD genérico de catálogos sencillos
config/transmetro.php   Umbrales (150 %, 25 %, 5 min) y permisos por rol
database/
  migrations/           Esquema (25 tablas del script Oracle)
  oracle/               Script Oracle original del documento
  seeders/              Datos de demostración
tests/Feature/          50 casos de prueba (CP-001 … CP-050)
```

---

## Matriz de trazabilidad

| Requisito | Implementación | Casos de prueba |
|---|---|---|
| RF-001 Usuarios y permisos | `UsuarioController`, `/usuarios` | CP-006, CP-007, CP-008, CP-009 |
| RF-002 Inicio de sesión | `AuthController`, `/login` | CP-001 … CP-005 |
| RF-003 Municipalidades | Catálogo `municipalidades` | CP-046 |
| RF-004 Líneas y recorridos | `LineaController`, `/lineas` | CP-018, CP-021, CP-022 |
| RF-005 Estaciones | Catálogo `estaciones` | CP-020 |
| RF-006 Accesos y seguridad | Catálogo `accesos`, `GuardiaAccesoController` | CP-041 |
| RF-007 Buses y asignación | `BusController`, `GestionFlota` | CP-010 … CP-013, CP-016 |
| RF-008 Parqueos | Catálogo `parqueos`, `GestionFlota::cambiarParqueo` | CP-014, CP-015 |
| RF-009 Pilotos | Catálogo `pilotos` | CP-049 |
| RF-010 Operadores | `OperadorController` | CP-050 |
| RF-011 Tickets y métodos de pago | `TicketController`, catálogo `metodos_pago` | CP-034, CP-037 |
| RF-012 Terminal de Autoservicio (TAT) | `/tat`, `ControlAcceso::emitirTicket` | CP-033 |
| RF-013 Control de acceso | `/control-acceso`, `ControlAcceso::validar` | CP-035 … CP-040 |
| RF-014 Movimientos de pasajeros | tabla `movimientos_pasajeros` | CP-035, CP-036 |
| RF-015 Control de pasajeros en buses (TCP) | `/recorridos/{id}`, `ControlOcupacion` | CP-023, CP-032 |
| RF-016 Cálculo de ocupación | `Recorrido::ocupacionActual` | CP-023, CP-029, CP-030 |
| RF-017 Alertas | `AlertaController` | CP-025, CP-042, CP-044 |
| RF-018 Demanda y tiempos de espera | `ControlOcupacion` | CP-025 … CP-028 |
| RF-019 Recorridos | `RecorridoController` | CP-024, CP-031, CP-032 |
| RF-020 Distancias | `Linea::distanciaTotal`, pivote `linea_estacion` | CP-017, CP-019 |
| RF-021 Dispositivos | Catálogo `dispositivos`, evento en `Dispositivo` | CP-042, CP-043 |
| RF-022 Incidentes | `IncidenteController` | CP-045 |
| RF-023 Reportes | `ReporteController` (11 reportes + CSV) | CP-047 |
| RF-024 Auditoría | `Auditor`, `/auditoria` | CP-001, CP-002, CP-046 |
| RF-025 Consulta integral | Panel general, `/lineas/{id}` | CP-048 |

| Regla | Dónde se aplica | Casos de prueba |
|---|---|---|
| RN-001 Orden de estaciones | `linea_estacion.orden_estacion` (único por línea), TCP sigue el orden | CP-018, CP-021, CP-024 |
| RN-002 Pertenencia de estaciones | `LineaController::quitarEstacion` | CP-020, CP-021 |
| RN-003 Accesos de estación | FK `accesos.id_estacion` | CP-035 |
| RN-004 Buses por línea (n … 2n) | `GestionFlota::asignarLinea`, `Linea::cumpleCantidadBuses` | CP-012, CP-013 |
| RN-005 Una línea por bus | `GestionFlota::asignarLinea` | CP-011 |
| RN-006 Parqueo obligatorio con historial | `GestionFlota::registrarBus/cambiarParqueo` | CP-010, CP-014, CP-015 |
| RN-007 Guardia por acceso | `GuardiaAccesoController::retirar` | CP-041 |
| RN-008 Municipalidad | FK obligatoria en líneas y estaciones | CP-022 |
| RN-009 Distancias | `LineaController::agregarEstacion` | CP-017, CP-019 |
| RN-010 Cálculo de ocupación | `ControlOcupacion` | CP-023 |
| RN-011 Alerta ≥ 150 % | `ControlOcupacion` | CP-025, CP-026 |
| RN-012 Espera de 5 min < 25 % | `ControlOcupacion` | CP-027, CP-028 |
| RN-013 Validación de acceso | `ControlAcceso::validar` | CP-035 … CP-039 |
| RN-014 Auditoría de operaciones críticas | `Auditor` en cada operación | CP-001, CP-032, CP-046 |
| RN-015 Permisos por rol | `VerificarModulo`, `config/transmetro.php` | CP-006 |

**Cobertura de requisitos funcionales (MT-05):** 25/25 RF con al menos un caso de prueba = 100 %.

### Decisiones de diseño a revisar

- **Demanda (RN-011):** el TCP pide "pasajeros que quedan en el andén". Demanda = a bordo + en andén; se compara con la capacidad del bus.
- **Espera adicional (RN-012):** se registra como alerta `ESPERA_ADICIONAL` (nivel BAJA, ya atendida), porque la tabla `CONTROL_PASAJEROS` no tiene una columna para eso.
- **Salida de pasajeros:** solo se permite con un ticket que registró una entrada validada, y una sola vez.
- **Dispositivo desconectado:** al cambiar a `DESCONECTADO` se genera automáticamente una alerta `FALLA_COMUNICACION` crítica (RF-021).
