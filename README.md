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

* npm
* Ruby
* Composer

Setup
-----

```
npm install -g bower grunt-cli
gem install compass
composer install
npm install
bower install
```

Develop
-------

```
git update-index --assume-unchanged app/config.php  # then edit this file

grunt ngtemplates                                   # generate Angular templates
grunt compass:compile                               # compile Sass
```

Deploy
------

* Run the queries in `schema.sql`.
* Run the queries in Sentry's `schema/mysql.sql`.
* Edit `app/config.php`.
* `grunt build`.
* Upload `dist/`.
* Edit `admin/create-first-admin.php` and run it remotely.
* If there are existing dirs, click `Admin → Sync folders`. Navigate to each dir and click `Folder → Sync files`.

### Static build

This build contains static files only. It has no PHP, no users, and no file operations. The API is generated as JSON files.

* Edit `paths.url` in `Gruntfile.js` to be the local app URL; the default is 'http://localhost`.

```
grunt build
grunt buildStatic
```

* Upload `dist-static/`.
