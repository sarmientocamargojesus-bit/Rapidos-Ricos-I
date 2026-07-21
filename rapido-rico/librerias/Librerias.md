# 📚 Librerías utilizadas — Rápido & Rico

Documentación completa de todas las librerías externas y nativas PHP
utilizadas en el proyecto. Ningún archivo del sistema fue modificado.

---

## Índice de librerías

| # | Librería | Tipo | Capa del proyecto | Archivo de documentación |
|---|----------|------|-------------------|--------------------------|
| 1 | Bootstrap 5.3.3 | Externa (CDN) | Vista | [01_bootstrap.md](01_bootstrap.md) |
| 2 | Bootstrap Icons 1.11.3 | Externa (CDN) | Vista | [02_bootstrap_icons.md](02_bootstrap_icons.md) |
| 3 | Google Fonts (Poppins + Nunito) | Externa (CDN) | Vista | [03_google_fonts.md](03_google_fonts.md) |
| 4 | jQuery 3.7.0 | Externa (CDN) | Vista — Admin | [04_jquery.md](04_jquery.md) |
| 5 | DataTables 1.13.8 | Externa (CDN) | Vista — Admin | [05_datatables.md](05_datatables.md) |
| 6 | Toastr.js (latest) | Externa (CDN) | Vista — Admin | [06_toastr.md](06_toastr.md) |
| 7 | SweetAlert2 v11 | Externa (CDN) | Vista — Cliente + Admin | [07_sweetalert2.md](07_sweetalert2.md) |
| 8 | Chart.js 4.4.0 | Externa (CDN) | Vista — Admin | [08_chartjs.md](08_chartjs.md) |
| 9 | PDO (PHP Data Objects) | Nativa PHP | Config + DAO | [09_pdo.md](09_pdo.md) |
| 10 | PHP Sessions | Nativa PHP | Helpers + Controllers + Vistas | [10_php_sessions.md](10_php_sessions.md) |
| 11 | password_hash / password_verify | Nativa PHP | Helpers (Seguridad.php) | [11_password_hash.md](11_password_hash.md) |
| 12 | json_encode / json_decode | Nativa PHP | Vistas + Helpers | [12_json.md](12_json.md) |
| 13 | Fetch API + FileReader | Nativa JS | Frontend JS | [13_fetch_filereader.md](13_fetch_filereader.md) |

---

## Resumen por capa de arquitectura

```
rapido-rico/
├── view/                   ← Bootstrap, Bootstrap Icons, Google Fonts,
│   ├── layouts/              jQuery, DataTables, Toastr, SweetAlert2,
│   ├── cliente/              Chart.js, bootstrap.Toast, Fetch API,
│   └── admin/                SweetAlert2 (Swal), json_encode (PHP→JS)
│
├── helpers/
│   ├── Seguridad.php       ← PHP Sessions, password_hash/verify, CSRF
│   ├── Validador.php       ← (validaciones puras, sin librerías externas)
│   └── Logger.php          ← json_encode (logs estructurados)
│
├── config/
│   └── conexion.php        ← PDO (singleton de conexión MySQL)
│
├── dao/                    ← PDO (prepare, execute, transacciones)
│   ├── PagoDAO.php
│   ├── PedidoDAO.php
│   ├── ProductoDAO.php
│   ├── UsuarioDAO.php
│   └── CategoriaDAO.php
│
├── controller/             ← PHP Sessions (lectura/escritura),
│   └── UsuarioController.php  password_verify (login)
│
├── service/                ← PHP Sessions (info_pago temporal)
│   └── MetodoPagoService.php
│
└── js/                     ← Fetch API (AJAX carrito),
    ├── main.js               FileReader (preview imagen),
    ├── carrito.js            bootstrap.Toast (notificaciones)
    └── validaciones.js       (validaciones nativas, sin libs externas)
```

---

> Proyecto **Rápido & Rico** — arquitectura MVC en PHP nativo.
> Las librerías externas se cargan exclusivamente mediante CDN, sin instalación local (sin Composer ni npm).
