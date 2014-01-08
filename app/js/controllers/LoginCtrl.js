/**
 * Login or activate.
 */
angular.module('app').controller('LoginCtrl', function ($scope, $location,
    $routeParams, Auth, Refiler) {
  $scope.action = typeof $routeParams.activationCode === 'string' ?
    'activate' : 'login';

  $scope.alerts = [];

  Refiler.page.title = $scope.action === 'activate' ? 'Activate' : 'Login';

  if ($scope.action === 'activate') {
    $scope.disabled = true;

    Auth.getEmailByActivationCode(
      $routeParams.activationCode
    ).then(function (email) {
      $scope.credentials = {
        'activationCode': $routeParams.activationCode,
        'email': email
      };
      $scope.disabled = false;
    }, function (error) {
      $scope.alerts.push({'message': error});
    });
  }

  $scope.login = function (credentials) {
    $scope.disabled = true;

    // clear old alerts
    $scope.alerts = [];

    // login
    Auth.login($scope.action, credentials).then(function () {
      $location.path($location.search().path || '/');
      $scope.disabled = false;
    }, function (error) {
      $scope.alerts.push({'message': error});
      $scope.disabled = false;
    });
  };
});