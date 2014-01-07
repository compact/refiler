angular.module('app').controller('AppCtrl', function ($scope, $rootScope,
    $location, Auth, Refiler) {
  $scope.error = false;

  $scope.page = Refiler.page;

  $rootScope.$on('$routeChangeError', function (e, c, p, rejection) {
    var errorMessage = '';

    if (typeof rejection === 'string') {
      errorMessage = rejection;
    } else if (typeof rejection === 'object' &&
        typeof rejection.data === 'object' &&
        typeof rejection.data.error === 'string') {
      if (rejection.data.error === 'Forbidden' && !Auth.loggedIn) {
        // prompt the user to login
        $location.search({
          'path': $location.path() // path to set after login
        }).path('/login');
      } else {
        errorMessage = rejection.data.error;
      }
    } else {
      // this case should never occur
      errorMessage = rejection;
    }

    // display the error message to the user
    Refiler.page.error = true;
    Refiler.page.title = 'Error: ' + errorMessage;
  });

  $rootScope.$on('$routeChangeStart', function () {
    Refiler.page.error = false;
  });
});