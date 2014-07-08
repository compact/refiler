/**
 * Login or activate a user account.
 */
angular.module('app').controller('LoginCtrl', function ($scope, $location,
    $routeParams, Auth, RefilerModel) {
  // RefilerAPI method name
  $scope.action = typeof $routeParams.activationCode === 'string' ?
    'activate' : 'login';

  $scope.alerts = [];

  RefilerModel.page.title = $scope.action === 'activate' ? 'Activate' : 'Login';

  if ($scope.action === 'activate') {
    $scope.disabled = true;

    Auth.getUserByActivationCode(
      $routeParams.activationCode
    ).then(function (user) {
      $scope.credentials = {
        'activationCode': $routeParams.activationCode,
        'id': user.id, // unused
        'email': user.email // unused
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
    }, function (error) {
      $scope.alerts.push({'message': error});
      $scope.disabled = false;
    });
  };
});