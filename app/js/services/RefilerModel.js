angular.module('app').service('RefilerModel', function ($http, $q, RefilerDir) {
  var self = this, deferreds = {};

  // page
  this.page = {};
  this.page.title = ''; // the current title displayed in <h1>
  this.page.error = false; // whether a $routeChangeError has occurred

  this.user = null;
  this.tags = null;
  this.dirs = null;

  deferreds.user = $q.defer();
  deferreds.tags = $q.defer();
  deferreds.dirs = $q.defer();

  $http.get('get/get-user-tags-dirs.php').success(function (data) {
    var dirs = _.map(data.dirs, function (dir) {
      return new RefilerDir(dir);
    });

    deferreds.user.resolve(data.user);
    deferreds.tags.resolve(data.tags);
    deferreds.dirs.resolve(dirs);

    // these can be used when it's clear the promises must have resolved
    self.user = data.user;
    self.tags = data.tags;
    self.dirs = dirs;
  });

  /**
   * @return Promise
   */
  this.getUser = function () {
    return deferreds.user.promise;
  };

  /**
   * @return Promise
   */
  this.getTags = function () {
    return deferreds.tags.promise;
  };

  /**
   * @return Promise
   */
  this.getDirs = function () {
    return deferreds.dirs.promise;
  };

  /**
   * @param  {Object}  dir          Only the path property is used in this
   *   method.
   * @param  {boolean} [deep=false] Whether to return all deep subdirs.
   * @return {Array}   RefilerDir objects.
   */
  this.getSubdirs = function (dir, deep) {
    if (typeof deep === 'undefined') {
      deep = false;
    }

    var subdirs;
    var segment = dir.path + '/';

    if (deep) {
      subdirs = _.where(this.dirs, function (dir) {
        return dir.path.indexOf(segment) === 0;
      });
    } else {
      var regExp = new RegExp(
        '^' + segment.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, '\\$1') + '[^\/]+$'
      );
      subdirs = _.where(this.dirs, function (dir) {
        return dir.path.match(regExp);
      });
    }

    return subdirs;
  };
});
