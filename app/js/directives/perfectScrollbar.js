angular.module('app').directive('perfectScrollbar', function($timeout,
    $window) {
  return function(scope, element, attrs) {
    var options = scope.$eval(attrs.perfectScrollbar);
    if (typeof options !== 'object') {
      options = {};
    }

    element.perfectScrollbar(options);

    // initialize the size of the perfect scrollbar
    $timeout(function () {
      element.perfectScrollbar('update');
    });

    // update when the window gets resized
    angular.element($window).bind('resize', function () {
      element.perfectScrollbar('update');
    });

    // update when the element gets resized
    scope.$watch(function () {
      return {
        'width': element.width(),
        'height': element.height()
      };
    }, function () {
      element.perfectScrollbar('update');
    }, true);
  };
});