# Online shop (PHP)

E-commerce web application built for the *Web Technologies* exam (University of L'Aquila, 2025). Individual project.

## Features

- Product catalogue with categories, brands and promotions
- Shopping cart and checkout, wishlist, coupons
- Product reviews, orders and order details, assistance (support) requests
- User accounts: sign up, login, profile, password reset page
- Admin area for managing products and promotions
- Access control based on a users / groups / services permission model
- AJAX endpoints for dynamic parts of the pages

## Tech stack

PHP, MySQL (21-table relational schema), JavaScript / jQuery, HTML / CSS,
a custom template engine (`include/template2.inc.php`) and PHP sessions.

## Running locally

1. Use a local stack such as XAMPP (Apache + MySQL/MariaDB) and copy the project into the web root (e.g. `htdocs/`).
2. Create a database called `database` and import `database.sql` (schema).
   `dump/dump.sql` is a phpMyAdmin dump that can be imported instead, if you prefer to start from sample data.
3. Database credentials are configured in `include/dbms.inc.php` (defaults: `localhost`, user `root`, empty password: local development only).
4. Open `http://localhost/<folder>/` in the browser.

## Credits and notes

- The admin area (`nope/`) is based on the *NiceAdmin* Bootstrap template by BootstrapMade (see https://bootstrapmade.com/license/).
- University project: not meant for production use. Interface text is in Italian.

---

## Italiano

Applicazione web di e-commerce realizzata per l'esame di Tecnologie Web (Università dell'Aquila, 2025), progetto individuale.
PHP, MySQL (schema a 21 tabelle), JavaScript/jQuery, template engine personalizzato e sessioni.
Per l'installazione: importare `database.sql` in un database chiamato `database` e configurare le credenziali in `include/dbms.inc.php`.
