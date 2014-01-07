/**
 * We use this directive instead of ngDisabled because that fails to disable
 *   Select2 inputs.
 */
angular.module('app').directive('disableFormElements', function ($parse) {
  return function (scope, element, attrs) {
    scope.$watch($parse(attrs.disableFormElements), function (disabled) {
      element.find('input, select, textarea, button')
        .prop('disabled', disabled);
    });
  };
});