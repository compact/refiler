/**
 * http://stackoverflow.com/a/18295416/2301179
 */
angular.module('app').directive('focusOn', function () {
  return function (scope, element, attrs) {
    scope.$on(attrs.focusOn, function () {
      element[0].focus();
    });
  };
});