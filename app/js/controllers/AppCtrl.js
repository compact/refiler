angular.module('app').controller('AppCtrl', function ($scope, $rootScope,
    $location, Auth, ErrorHandler, RefilerModel) {
  $scope.error = false;

  $scope.page = RefilerModel.page;

  $rootScope.$on('$routeChangeError', function (e, c, p, rejection) {
    var errorMessage = ErrorHandler.parseMessage(rejection);

    // custom error 'Forbidden' from Refiler\Auth permission checks
    if (errorMessage === 'Forbidden') {
      // prompt the user to login
      Auth.pathAfterLogin = $location.path();
      $location.path('/login');
    }

    // display the error message to the user
    RefilerModel.page.error = true;
    RefilerModel.page.title = 'Error: ' + errorMessage;
  });

  $rootScope.$on('$routeChangeStart', function () {
    RefilerModel.page.error = false;
  });
});
