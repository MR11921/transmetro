# Graph Report - transmetro-app  (2026-10-12)

## Corpus Check
- Corpus is ~24,637 words - fits in a single context window. You may not need a graph.

## Summary
- 732 nodes · 1349 edges · 77 communities (12 shown, 65 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 16 edges (avg confidence: 0.88)
- Token cost: 63,426 input · 0 output

## Community Hubs (Navigation)
- Control de acceso y tickets
- Buses y asignaciones
- Reglas de negocio y trazabilidad
- Dependencias Composer
- Migraciones de BD
- Script Oracle (tablas)
- Operadores y rutas
- Dependencias npm
- Alertas y excepciones
- Incidentes y controladores
- Recorridos y control de pasajeros
- Líneas y estaciones
- Usuarios y roles
- Modelo Estación
- Generación de reportes
- Roles y datos semilla
- Auditoría
- Base de pruebas
- Pruebas de ocupación
- Autenticación
- CRUD de catálogos
- Panel general y alertas
- Arranque de la app
- Documentación y stack
- Pruebas de autenticación
- Dispositivos
- Incidentes (modelo)
- Pruebas de operación
- Service provider
- Permisos por módulo
- Definición de catálogos
- Vista acceso/index
- Vista guardias
- Vista alertas/index
- Vista auditoria/index
- Vista buses/form
- Vista buses/index
- Vista buses/show
- Vista catalogos/form
- Vista catalogos/index
- Vista dashboard
- Vista incidentes/form
- Vista incidentes/index
- Vista lineas/form
- Vista lineas/index
- Vista lineas/show
- Vista operadores/index
- Vista recorridos/form
- Vista recorridos/index
- Vista recorridos/show
- Vista reportes/index
- Vista reportes/show
- Vista tickets/index
- Vista tat
- Vista usuarios/form
- Vista usuarios/index

## God Nodes (most connected - your core abstractions)
1. `Auditor` - 48 edges
2. `Matriz de trazabilidad RF/RN -> implementacion -> CP` - 42 edges
3. `Casos de prueba CP-001 ... CP-050 (tests/Feature)` - 41 edges
4. `Bus` - 36 edges
5. `Linea` - 33 edges
6. `ReglaNegocioException` - 31 edges
7. `Estacion` - 27 edges
8. `Recorrido` - 27 edges
9. `Usuario` - 27 edges
10. `Alerta` - 21 edges

## Surprising Connections (you probably didn't know these)
- `RF-017 Alertas` --references--> `AlertaController`  [EXTRACTED]
  README.md → app/Http/Controllers/AlertaController.php
- `RF-002 Inicio de sesion` --references--> `AuthController`  [EXTRACTED]
  README.md → app/Http/Controllers/AuthController.php
- `RF-007 Buses y asignacion` --references--> `BusController`  [EXTRACTED]
  README.md → app/Http/Controllers/BusController.php
- `RF-006 Accesos y seguridad` --references--> `GuardiaAccesoController`  [EXTRACTED]
  README.md → app/Http/Controllers/GuardiaAccesoController.php
- `RN-007 Guardia por acceso` --references--> `GuardiaAccesoController`  [EXTRACTED]
  README.md → app/Http/Controllers/GuardiaAccesoController.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Reglas de ocupacion del TCP (ControlOcupacion)** — readme_rn_010, readme_rn_011, readme_rn_012, app_services_controlocupacion_controlocupacion, readme_tcp [EXTRACTED 1.00]
- **Reglas de flota (GestionFlota)** — readme_rn_004, readme_rn_005, readme_rn_006, app_services_gestionflota_gestionflota [EXTRACTED 1.00]
- **Flujo TAT -> ticket -> control de acceso** — readme_rf_011, readme_rf_012, readme_rf_013, readme_rf_014, readme_rn_013, app_services_controlacceso_controlacceso [INFERRED 0.85]

## Communities (77 total, 65 thin omitted)

### Community 0 - "Control de acceso y tickets"
Cohesion: 0.05
Nodes (15): ControlAccesoController, GuardiaAccesoController, TicketController, Acceso, Guardia, MetodoPago, MovimientoPasajero, Municipalidad (+7 more)

### Community 1 - "Buses y asignaciones"
Cohesion: 0.06
Nodes (11): BusController, Bus, BusLinea, BusParqueo, {closure#1}(), Parqueo, Piloto, {closure#1}() (+3 more)

### Community 2 - "Reglas de negocio y trazabilidad"
Cohesion: 0.08
Nodes (48): ControlOcupacion, GestionFlota, Casos de prueba CP-001 ... CP-050 (tests/Feature), Cobertura de RF (MT-05): 25/25 = 100%, Matriz de permisos por rol (config/transmetro.php), Matriz de trazabilidad RF/RN -> implementacion -> CP, RF-001 Usuarios y permisos, RF-002 Inicio de sesion (+40 more)

### Community 3 - "Dependencias Composer"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 4 - "Migraciones de BD"
Cohesion: 0.08
Nodes (31): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#10}() (+23 more)

### Community 5 - "Script Oracle (tablas)"
Cohesion: 0.14
Nodes (32): ACCESO_GUARDIA, ACCESOS, ALERTAS, AUDITORIA, BUS_LINEA, BUS_PARQUEO, BUSES, CONTROL_PASAJEROS (+24 more)

### Community 7 - "Dependencias npm"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 8 - "Alertas y excepciones"
Cohesion: 0.16
Nodes (4): ReglaNegocioException, AlertaController, RecorridoController, Auditor

### Community 10 - "Recorridos y control de pasajeros"
Cohesion: 0.12
Nodes (3): ControlPasajero, Recorrido, {closure#1}()

### Community 16 - "Roles y datos semilla"
Cohesion: 0.19
Nodes (3): Rol, DatabaseSeeder, RolesSeeder

### Community 24 - "Arranque de la app"
Cohesion: 0.28
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 25 - "Documentación y stack"
Cohesion: 0.25
Nodes (8): robots.txt (allow all crawlers), Transmetro README, PROYECTO FINAL TRANSMETRO.pdf (especificacion), Metodologia en espiral, Conexion Oracle via yajra/laravel-oci8, Script Oracle transmetro_oracle.sql, Stack PHP 8 + Laravel 13 MVC + Oracle (seccion 12), Transmetro - Sistema de Gestion y Control

## Ambiguous Edges - Review These
- `robots.txt (allow all crawlers)` → `Transmetro - Sistema de Gestion y Control`  [AMBIGUOUS]
  public/robots.txt · relation: conceptually_related_to

## Knowledge Gaps
- **79 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+74 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 304 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **65 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `robots.txt (allow all crawlers)` and `Transmetro - Sistema de Gestion y Control`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `Auditor` connect `Alertas y excepciones` to `Control de acceso y tickets`, `Buses y asignaciones`, `Reglas de negocio y trazabilidad`, `Operadores y rutas`, `Incidentes y controladores`, `Recorridos y control de pasajeros`, `Líneas y estaciones`, `Closures de reportes`, `Usuarios y roles`, `Auditoría`, `Autenticación`, `Closures de catálogos`, `CRUD de catálogos`?**
  _High betweenness centrality (0.059) - this node is a cross-community bridge._
- **Why does `TestCase` connect `Base de pruebas` to `Control de acceso y tickets`, `Buses y asignaciones`, `Modelo Estación`, `Pruebas de ocupación`, `Panel general y alertas`, `Pruebas de autenticación`, `Incidentes (modelo)`, `Pruebas de operación`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **Why does `Matriz de trazabilidad RF/RN -> implementacion -> CP` connect `Reglas de negocio y trazabilidad` to `Control de acceso y tickets`, `Documentación y stack`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Are the 5 inferred relationships involving `Auditor` (e.g. with `.emitirTicket()` and `{closure#1}()`) actually correct?**
  _`Auditor` has 5 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _79 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Control de acceso y tickets` be split into smaller, more focused modules?**
  _Cohesion score 0.05311676909569798 - nodes in this community are weakly interconnected._