<?php

namespace Refiler;
require '../require.php';
require __DIR__ . '/../vendor/autoload.php';

// init Slim
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

// set the default Content-Type header, which can be overwritten in any script
// below
$app->response->headers->set('Content-Type', 'application/json');

require 'routes/init/get.php';            # get the user, tags, and dirs

require 'routes/tag/get.php';             # get a tag and files with that tag
require 'routes/tag/post.php';            # edit a tag
require 'routes/tag/delete.php';          # delete a tag

require 'routes/dir/post-new.php';        # create a new dir
require 'routes/dir/get.php';             # get a dir and its files
require 'routes/dir/post.php';            # move a dir
require 'routes/dir/delete.php';          # delete a dir
require 'routes/dir/post-files.php';      # curl files to a dir
require 'routes/dir/post-files-tags.php'; # tag the files in a dir

require 'routes/file/post-new.php';       # upload a new file
require 'routes/file/post.php';           # edit a file
require 'routes/file/delete.php';         # delete a file
require 'routes/file/get-download.php';   # download a file
require 'routes/file/get-tags.php';       # get the tags of a file
require 'routes/file/post-thumb.php';     # regenerate the thumb of a file

require 'routes/files/delete.php';        # delete the given files
require 'routes/files/post.php';          # move the given files
require 'routes/files/post-tags.php';     # tag the given files

require 'routes/session/post.php';        # login with the given credentials
require 'routes/session/delete.php';      # logout the current user

require 'routes/user/get.php';            # get the email with the given code
require 'routes/user/delete.php';         # delete a user
require 'routes/user/post-activate.php';  # activate a user with the given email

require 'routes/users/get.php';           # get all users
require 'routes/users/post.php';          # edit all users

$app->run();

?>
