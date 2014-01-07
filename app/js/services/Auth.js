angular.module('app').provider('Auth', function () {
  // configurable guest permissions; must match the values in config.php
  this.defaultPermissions = {
    'view': true,
    'edit': false,
    'admin': false
  };

  // service
  this.$get = function service($http, $location, $q) {
    var Auth = {};

    // whether the user is currently logged in
    Auth.loggedIn = false;

    // when setting a new var to this object, remember to clone it
    Auth.defaultPermissions = this.defaultPermissions;

    // permissions are set when this service is constructed, and when the user
    // logs in successfully
    Auth.permissions = _.clone(Auth.defaultPermissions);

    // get permissions right away
    $http.get('get/get-user.php').success(function (data) {
      Auth.loggedIn = data.user.loggedIn;
      Auth.permissions = data.user.permissions;
    });

    /**
     * User activation is absorbed into this method since its logic is the same
     *   as logging in.
     * @param  {string}  action 'login' or 'activate'
     * @param  {Object}  data
     * @return {Promise}
     */
    Auth.login = function (action, data) {
      var deferred = $q.defer();

      $http.post('post/' + action + '.php', data).success(function (data) {
        Auth.loggedIn = true;
        Auth.permissions = data.user.permissions;

        deferred.resolve();
      }).error(function (data) {
        deferred.reject(data.error || 'Error.');
      });

      return deferred.promise;
    };

    Auth.logout = function () {
      $http.get('get/logout.php').success(function () {
        Auth.loggedIn = false;
        $location.search({
          'path': $location.path() // path to set after login
        }).path('/login');
      });
    };

    Auth.getEmailByActivationCode = function (activationCode) {
      var deferred = $q.defer();

      $http.get('get/get-email-by-activation-code.php', {
        'params': {
          'activationCode': activationCode
        }
      }).success(function (data) {
        deferred.resolve(data.email);
      }).error(function (data) {
        deferred.reject(data.error || 'Error.');
      });

      return deferred.promise;
    };

    return Auth;
  };
});