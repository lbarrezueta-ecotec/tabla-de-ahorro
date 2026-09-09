# Tabla de Ahorro — Web pública para ahorro individual y grupal

Aplicación web en **PHP + MySQL (MVC)** para gestionar retos de ahorro con una tabla interactiva. Cada sala genera 55 montos aleatorios que suman exactamente la meta configurada. El usuario marca casillas a medida que ahorra y ve su progreso en tiempo real. Soporta uso **individual** o **grupal** (sala compartida con código).

> Proyecto académico — Materia: Programación de Sistemas Web. Cumple el flujo `Vista → Controlador → Modelo → MySQL` y está pensado para uso público.

## Demo público

- **Web:** https://tabla-ahorro.site.je
- **Repositorio:** https://github.com/lbarrezueta-ecotec/tabla-de-ahorro

> Si ves un challenge `aes.js` la primera vez, es la protección anti-bot de InfinityFree: recargá en incógnito y entra normal.

## Qué hace

**Para cualquier persona** que quiere ahorrar con un reto visual:

1. **Crea cuenta o inicia sesión** (usuario + contraseña, 3-50 y 4+ caracteres).
2. **Crea una sala:**
   - **Individual:** ingresás el nombre de la sala y entras directo al tablero. Meta por defecto $1500.
   - **Grupal:** wizard con nombre de sala (obligatorio), meta ($10 a $100.000), número de personas (2 a 20 con botones -/+) y tipo de suma (**conjunta** o **individual**). Al crearla recibís un **código de 6 caracteres** (ej. `KLV7ED`) para compartir. La sala grupal muestra una pantalla intermedia con el código y botón `Continuar a tu tabla`.
3. **Unirse a una sala:** con el código que te compartieron. Si ya tenés sesión, te lleva directo al tablero de esa sala (crea tu usuario en esa sala automáticamente si no existías allí).
4. **Tablero de ahorro:**
   - Grilla de 55 celdas con montos en `$` (distribución que suma la meta). Click para marcar/desmarcar (verde = ahorrado).
   - Header con **Ahorrado / Meta / Restante**, barra de progreso y estado (`¡Completado!`).
   - En modo **grupal + conjunta**, el total es la suma de todos los participantes; en **individual** es solo tu avance.
   - Solo el **admin** (creador) puede editar la meta (`Editar`) — si hay casillas marcadas, se preservan al recalcular; si todas estaban marcadas, se agregan casillas nuevas.
   - `Menú` vuelve al inicio, `Cerrar sesión` sale, `Ver mis salas` (landing) lista todas tus salas con `Ir` y `Eliminar` (solo oculta en el modal).

Validaciones en cliente (`js/validaciones.js`) y servidor: nombre único global, contraseña hasheada (`password_hash`), códigos únicos, rangos de meta y personas.

## Arquitectura

```
Vista (views/ + css/ + js/) → Controlador (controllers/) → Modelo (models/) → MySQL (pdo)
index.php  = punto de entrada único (target/action: landing/sala/usuario)
config/conexion.php = PDO MySQL (hosting: sql110.infinityfree.com / if0_42868444_integradora + fallback local)
```

- **Controladores:** `SalaController` (crear, crearConfigurado, entrar, generar valores), `UsuarioController` (registrar, login, logout), `ProgresoController` (guardar índices/valores, actualizar_meta)
- **Modelos:** `Sala`, `Usuario`, `Progreso` — consultas preparadas, `UNIQUE(sala_id, nombre)` + check global de nombre, `ON DELETE CASCADE`
- **Vistas:** `views/salas/sala.php` (landing intermedia), `views/salas/crear_wizard.php` (grupal), `views/salas/sala_creada.php` (código), `views/progreso/tablero.php` (tablero con `SALA_VALORES` y `TOTAL_GRUPAL`)

## Modelo de datos

`sql/esquema.sql` (`integradora` local) / `sql/esquema_fixed.sql` (hosting sin `CREATE DATABASE`):

- **salas** `id, codigo UNIQUE, nombre, url_unica UNIQUE, meta, modo (individual|grupal), admin_usuario_id, num_personas, tipo_suma (conjunta|individual), valores TEXT, creada_en`
  - `valores` = JSON con los 55 montos de la sala (compartidos en grupal).
- **usuarios** `id, sala_id FK, nombre, contrasena (hash), creado_en` + `UNIQUE(sala_id, nombre)` (nombres únicos globalmente a nivel app).
- **progreso** `id, sala_id FK, usuario_id FK, valor_ahorrado, casillas_marcadas, casillas_indices TEXT, valores TEXT, actualizado_en` + `UNIQUE(sala_id, usuario_id)`
  - `casillas_indices` guarda los índices marcados (incluye `0`), `valores` copia por compatibilidad.

Generación de valores: suma exacta a la meta, `maxVal` dinámico (metas grandes permiten montos mayores) y reparto con variedad para que la grilla no quede monótona.

## Estructura

```
/
├── index.php              # Router + landing 2 pasos (cuenta → sala)
├── index.html             # Coming-soon (se elimina en hosting)
├── config/conexion.php    # PDO
├── controllers/           # SalaController, UsuarioController, ProgresoController
├── models/                # Sala, Usuario, Progreso
├── views/
│   ├── salas/             # sala.php, crear_wizard.php, sala_creada.php
│   └── progreso/tablero.php
├── css/estilos.css        # Landing + tablero (bento, gradiente #ffb6c1→#b0c4de, Geist)
├── js/                    # script.js (tablero), validaciones.js
└── sql/                   # esquema.sql, esquema_fixed.sql
```

## Instalación local (XAMPP)

Requisitos: PHP 8.x, MySQL/MariaDB, Apache o `php -S`.

1. Clonar en `htdocs` o en cualquier carpeta:
   ```bash
   git clone https://github.com/lbarrezueta-ecotec/tabla-de-ahorro.git
   cd tabla-de-ahorro
   ```
2. Crear DB e importar:
   - En phpMyAdmin crear `integradora` e importar `sql/esquema.sql`.
   - O por CLI: `mysql -u root < sql/esquema.sql`
3. Configurar `config/conexion.php` (por defecto `localhost / integradora / root / ""`). En hosting ya apunta a `sql110.infinityfree.com`.
4. Levantar:
   ```bash
   php -S localhost:8000
   # o
   php -S localhost:8000 -t .
   ```
   Abrir `http://localhost:8000`.

## Despliegue (InfinityFree)

- Dominio addon: `tabla-ahorro.site.je` → `htdocs` en `ftpupload.net` (usuario `if0_42868444`). Servidor MySQL `sql110.infinityfree.com`, DB `if0_42868444_integradora`.
- FTP: `ftpupload.net:21` / `if0_42868444` / `54NVhQoDKx` — dos `htdocs`: raíz y `tabla-ahorro.site.je/htdocs` (mantener sincronizados).
- En phpMyAdmin del hosting importar `sql/esquema_fixed.sql` (sin `CREATE DATABASE`).
- `config/conexion.php` ya con credenciales del hosting y fallback a `localhost` para desarrollo local.

## Uso público — flujo recomendado

1. Entrá a https://tabla-ahorro.site.je → `Registrarse` → creá tu usuario.
2. Elegí `Crear sala (individual)` o `Crear sala (grupal)` con tu meta y cantidad de personas. Compartí el código si es grupal.
3. En el tablero, hacé click en las celdas a medida que ahorres. `Guardar` persiste en `progreso`. Recargá y sigue todo marcado.
4. Desde `Menú` o `Ver mis salas` podés saltar entre tus salas sin perder sesión.

## Tecnologías

PHP 8.2, MySQL (MyISAM en hosting), PDO, JavaScript vanilla, CSS3 (Geist, grid, flex). Sin dependencias de build. Tests locales con Playwright (`tests/local-testsprite-verification.spec.ts`).

## Licencia

Uso académico y público. Podés clonar y desplegar tu propia instancia cambiando `config/conexion.php` y `sql/esquema.sql`.
