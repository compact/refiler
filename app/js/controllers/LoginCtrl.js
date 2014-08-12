/**
 * Login or activate a user account.
 */
angular.module('app').controller('LoginCtrl', function ($location, $routeParams,
    Auth, RefilerModel) {
  var ctrl = this;

  // RefilerAPI method name
  this.action = typeof $routeParams.activationCode === 'string' ?
    'activate' : 'login';

  this.alerts = [];

  RefilerModel.page.title = this.action === 'activate' ? 'Activate' : 'Login';

  if (this.action === 'activate') {
    this.disabled = true;

    Auth.getUserByActivationCode(
      $routeParams.activationCode
    ).then(function (user) {
      ctrl.credentials = {
        'activationCode': $routeParams.activationCode,
        'id': user.id, // unused
        'email': user.email // unused
      };
      ctrl.disabled = false;
    }, function (error) {
      ctrl.alerts.push({'message': error});
    });
  }

  this.login = function (credentials) {
    this.disabled = true;

    // clear old alerts
    this.alerts = [];

    // login
    Auth.login(this.action, credentials).then(function () {
      $location.path(Auth.pathAfterLogin);
    }, function (error) {
      ctrl.alerts.push({'message': error});
      ctrl.disabled = false;
    });
  };
});
