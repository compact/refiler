angular.module('app').service('RefilerModel', function ($http, $q) {
  var self = this, deferreds = {};

  deferreds.user = $q.defer();
  deferreds.tags = $q.defer();
  deferreds.dirs = $q.defer();

  this.user = null;
  this.tags = null;
  this.dirs = null;

  $http.get('get/get-user-tags-dirs.php').success(function (data) {
    deferreds.user.resolve(data.user);
    deferreds.tags.resolve(data.tags);
    deferreds.dirs.resolve(data.dirs);

    // these can be used when it's clear the promises must have resolved
    self.user = data.user;
    self.tags = data.tags;
    self.dirs = data.dirs;
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
});