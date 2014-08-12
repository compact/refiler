/**
 * This directive is used only in menu.html.
 */
angular.module('app').directive('pref', function () {
  return {
    'scope': true, // new child scope that inherits from the parent scope
    'replace': true,
    'template':
      '<a ng-click="gallery.Prefs.set(key, value)">' +
        '<i class="fa fa-fw" ng-class="{' +
          '\'fa-check\': gallery.Prefs[key] == value' +
        '}"></i> {{label}}' +
      '</a>',
    'link': function (scope, element, attrs) {
      scope.key = attrs.key;
      scope.value = attrs.value;
      scope.label = attrs.label || attrs.value;
    }
  };
});
