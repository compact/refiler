angular.module('app').service('RefilerModel', function ($http, $q, RefilerDir) {
  var self = this;

  // page
  this.page = {};
  this.page.title = ''; // the current title displayed in <h1>
  this.page.error = false; // whether a $routeChangeError has occurred

  var deferred = $q.defer();
  // these properties can be used directly when it's known that the promise must
  // have resolved
  this.user = null;
  this.tags = null;
  this.dirs = null;
  this.dirTree = null;

  $http.get('get/get-user-tags-dirs.php').success(function (data) {
    self.user = data.user;
    self.tags = data.tags;

    self.dirTree = (function (dirs) {
      var convert = function (dirs) {
        return _.map(dirs, function (dir) {
          dir.subdirs = convert(dir.subdirs);
          return new RefilerDir(dir);
        });
      };

      return convert(dirs);
    }(data.dirs));

    self.dirs = (function (dirs) {
      var flatten = function (dirs) {
        var result = [];

        _.each(dirs, function (dir, i) {
          result.push(dir);

          if (dir.subdirs.length > 0) {
            result = result.concat(flatten(dir.subdirs));
          }
        });

        return result;
      };

      return flatten(dirs);
    }(self.dirTree));

    deferred.resolve(self);
  });

  /**
   * @return {Promise}
   */
  this.ready = function () {
    return deferred.promise;
  };

  /**
   * @return {RefilerDir|undefined}
   */
  this.getDir = function (id) {
    return _.where(this.dirs, {'id': id})[0];
  };
});
