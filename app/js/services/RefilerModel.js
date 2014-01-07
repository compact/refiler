angular.module('app').service('RefilerModel', function ($http, $q) {
  this.tags = null;
  this.dirs = null;

  /**
   * @return Promise
   */
  this.getTags = function () {
    if (this.tags !== null) {
      return $q.when(this.tags);
    } else {
      var self = this, deferred = $q.defer();

      $http.get('get/get-tags.php').success(function (data) {
        self.tags = data.tags;
        deferred.resolve(self.tags);
      });

      return deferred.promise;
    }
  };

  /**
   * @return Promise
   */
  this.getDirs = function () {
    if (this.dirs !== null) {
      return $q.when(this.dirs);
    } else {
      var self = this, deferred = $q.defer();

      $http.get('get/get-dirs.php').success(function (data) {
        self.dirs = data.dirs;
        deferred.resolve(self.dirs);
      });

      return deferred.promise;
    }
  };
});