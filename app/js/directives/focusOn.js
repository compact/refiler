/**
 * http://stackoverflow.com/a/18295416/2301179
 */
angular.module('app').directive('focusOn', function ($timeout) {
  return function (scope, element, attrs) {
    scope.$on(attrs.focusOn, function () {
      $timeout(function () {
        element[0].focus();
      });
    });
  };
});
