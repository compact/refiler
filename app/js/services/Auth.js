angular.module('app').provider('Auth', function () {
  // configurable guest permissions; must match the values in config.php
  this.guestPermissions = {
    'view': true,
    'edit': false,
    'admin': false
  };

  // service
  this.$get = function service($location, _, ErrorHandler, RefilerAPI,
      RefilerModel) {
    var Auth = {};

    // whether the user is currently logged in
    Auth.loggedIn = false;

    // when setting a new var to this object, remember to clone it
    Auth.guestPermissions = this.guestPermissions;

    // permissions are set when this service is constructed, and when the user
    // logs in successfully
    Auth.permissions = _.clone(Auth.guestPermissions);

    // whether the user's permissions have been verified
    Auth.verified = false;

    // get permissions right away
    RefilerModel.ready().then(function (model) {
      Auth.loggedIn = model.user.loggedIn;
      Auth.permissions = model.user.permissions;
      Auth.verified = true;
    });

    /**
     * User activation is absorbed into this method since its logic is the same
     *   as logging in.
     * @param  {string}  action 'login' or 'activate', a method name of
     *   RefilerAPI.
     * @param  {Object}  data
     * @return {Promise}
     */
    Auth.login = function (action, data) {
      return RefilerAPI[action](data).then(function (data) {
        Auth.loggedIn = true;
        Auth.permissions = data.user.permissions;

        // update RefilerModel if needed
        if (typeof data.tags !== 'undefined') {
          RefilerModel.tags = data.tags;
        }
        if (typeof data.dirs !== 'undefined') {
          RefilerModel.setDirs(data.dirs);
        }
      }, function (response) {
        throw ErrorHandler.parseMessage(response);
      });
    };

    Auth.logout = function () {
      return RefilerAPI.logout().then(function (data) {
        Auth.loggedIn = false;

        // update RefilerModel if needed
        if (typeof data.tags !== 'undefined') {
          RefilerModel.tags = data.tags;
        }
        if (typeof data.dirs !== 'undefined') {
          RefilerModel.dirs = data.dirs;
        }

        $location.search({
          'path': $location.path() // path to set after login
        }).path('/login');
      });
    };

    Auth.getUserByActivationCode = function (activationCode) {
      return RefilerAPI.getUserByActivationCode(activationCode).then(function (data) {
        return data.user;
      }, function (response) {
        throw ErrorHandler.parseMessage(response);
      });
    };

    return Auth;
  };
});
