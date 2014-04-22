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

  $http.get('get/get-user-tags-dirs.php').success(function (data) {
    self.user = data.user;
    self.tags = data.tags;

    self.dirs = (function (dirs) {
      var tree = (function (dirs) {
        var convert = function (dirs) {
          return _.map(dirs, function (data) {
            data.subdirs = convert(data.subdirs);
            return new RefilerDir(data);
          });
        };

        return convert(dirs);
      }(dirs));

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

      return flatten(tree);
    }(data.dirs));

    deferred.resolve(self);
  });

  /**
   * @return {Promise}
   */
  this.ready = function () {
    return deferred.promise;
  };

  /**
   * @param  {number} id
   * @return {RefilerDir|undefined}
   */
  this.getDir = function (id) {
    return _.where(this.dirs, {'id': id})[0];
  };

  /**
   * Add the given dir to the model.
   * @param {Object} data Does not have to be a RefilerDir.
   */
  this.addDir = function (data) {
    dir = new RefilerDir(data);

    // add to this.dirs and sort it
    this.dirs.push(dir);
    this.sortDirs();

    // add as a subdir to the parent dir
    var parentPath = dir.getParentPath();
    if (parentPath !== '.') {
      var parent = _.where(this.dirs, {'path': parentPath})[0];
      parent.subdirs.push(dir);
    }
  };

  /**
   * Remove the dir with the given id from the model.
   * @param {number} id
   */
  this.removeDir = function (id) {
    _.remove(this.dirs, {'id': id});
  };

  /**
   * Update the dir with the given id with the given data (properties of
   *   RefilerDir).
   * @param  {number} id
   * @param  {object} data
   */
  this.updateDir = function (id, data) {
    this.getDir(id).update(data);
    this.dirs = _.sortBy(this.dirs, 'path');
  };

  /**
   * Sort the dirs. Call this method after updating the model.
   */
  this.sortDirs = function () {
    this.dirs = _.sortBy(this.dirs, 'path');
  };
});
