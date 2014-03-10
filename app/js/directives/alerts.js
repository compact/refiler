/**
 * Display a collection of Bootstrap alerts.
 * <div alerts="alerts"></div> displays $scope.alerts, an array of objects.
 */
angular.module('app').directive('alerts', function ($parse) {
  return {
    'scope': true, // new child scope that inherits from the parent scope
    'templateUrl': 'alerts.html',
    'link': function (scope, element, attrs) {
      // in the case alerts="alerts", scope.alerts is inherited from the parent
      // scope and no watching is necessary
      if (attrs.alerts !== 'alerts') {
        scope.$watch($parse(attrs.alerts), function (alerts) {
          scope.alerts = alerts;
        });
      }
    }
  };
});