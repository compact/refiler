Refiler is a file manager for files on a web server. It is a work in progress.

Features
--------

* Tag files. Each tag can have parent tags, child tags, and a caption.
* View collections of files by tag or dir in a gallery layout, with thumbnails generated for images. Browse images in a lightbox.
* Upload local files or upload by URLs.
* Basic file operations: Move and delete files. Create, move, and delete dirs.
* Basic user management: Create and edit users with permissions.

Requirements
------------

Remote:

* PHP 5.4
* MySQL 5 (with `innodb_autoinc_lock_mode = 0` or `1`)

Local:

* Node.js, npm, Yeoman
* Composer

Deployment
----------

* Run the queries in `schema.sql`.
* Run the queries in Sentry's `schema/mysql.sql`.
* Edit `app/config.php`.

```
php composer.phar install
npm install
bower install
grunt build
```

* Upload `dist/`.
* Edit `admin/create-first-admin.php` and run it remotely.
* Run `admin/sync-dirs.php` remotely. If there are a lot of dirs, use the param `?path=path/to/dir`.
