angular.module('app').config(function (RefilerProvider) {
  RefilerProvider.config.staticMode = false;
});

angular.module('app').config(function (AuthProvider) {
  AuthProvider.guestPermissions = {
    'view': true,
    'edit': false,
    'admin': false
  };
});
