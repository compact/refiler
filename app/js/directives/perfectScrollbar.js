angular.module('app').directive('perfectScrollbar', function($timeout,
    $window) {
  return function(scope, element, attrs) {
    var Ps = $window.Ps;
    var options = scope.$eval(attrs.perfectScrollbar);
    if (typeof options !== 'object') {
      options = {};
    }

    Ps.initialize(element[0], options);

    // update when the window gets resized
    angular.element($window).bind('resize', function () {
      Ps.update(element[0]);
    });

    // update when the element gets resized
    scope.$watch(function () {
      return {
        'width': element.width(),
        'height': element.height()
      };
    }, function () {
      Ps.update(element[0]);
    }, true);

    // scroll to the top whenever the ul height changes, so the list doesn't
    // appear to disappear when the user scrolls down and filters it
    // TODO: the child selector should be specified in an attr
    var ul = element.find('ul');
    scope.$watch(function () {
      return ul.height();
    }, function () {
      element.scrollTop(0);
      Ps.update(element[0]);
    });
  };
});
