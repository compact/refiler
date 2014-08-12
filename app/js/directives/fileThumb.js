/**
 * The element shown is the thumbnail (img or i) only. It is not linked.
 * Usage: <file-thumb file="file"></file-thumb>
 */
angular.module('app').directive('fileThumb', function () {
  return {
    'restrict': 'E',
    'templateUrl': 'fileThumb.html',
    'link': function (scope, element, attrs) {
      scope.file = scope.$eval(attrs.file);

      // update the scope when the thumb gets updated
      var off = scope.$on('RefilerGalleryModelChange', function (event, model) {
        scope.file = model.getFile(scope.file.id);
      });

      // deregister the listener when this scope is destroyed
      scope.$on('$destroy', off);
    }
  };
});
