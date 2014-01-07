/**
 * If both ngClick and ngHref are set on an anchor element, prevent the
 *   default action on left click.
 */
angular.module('app').directive('a', function () {
  return {
    'restrict': 'E',
    'link': function (scope, element, attrs) {
      if (typeof attrs.ngClick === 'string' &&
          typeof attrs.ngHref === 'string') {
        element.bind('click', function (event) {
          if (event.which === 1) { // left click
            event.preventDefault();
          }
        });
      }
    }
  };
});