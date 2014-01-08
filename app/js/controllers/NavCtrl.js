angular.module('app').controller('NavCtrl', function ($http, $scope, Refiler,
    RefilerGalleryModel, RefilerModel) {
  // init
  $scope.searchText = '';

  // populate tab content
  RefilerModel.getTags().then(function (tags) {
    $scope.tags = tags;
  });
  RefilerModel.getDirs().then(function (dirs) {
    $scope.dirs = dirs;
  });

  /**
   * When the user has entered search text, filter the tags by name. If the
   *   config option 'defaultParentlessTagsInNav' is true, when there is no
   *   search text, show only parentless tags (otherwise the list of tags may
   *   be too long).
   * @param  {string} searchText
   * @return {Object} Object passed into ng.filter:filter.
   */
  $scope.tagFilter = function (searchText) {
    return Refiler.config.defaultParentlessTagsInNav && searchText === '' ?
      {'parentCount': 0} :
      {'name': searchText};
  };

  /**
   * Dir version of tagFilter(). The difference is dirs are searched by text
   *   following slashes, as in 'ba' filters 'foo/bar' but 'ar' does not.
   * @param  {string}   searchText
   * @return {Function} Function passed into ng.filter:filter.
   */
  $scope.dirFilter = function (searchText) {
    var pattern;

    if (Refiler.config.defaultParentlessDirsInNav && searchText === '') {
      return function (dir) {
        return dir.path.indexOf('/') === -1;
      };
    } else {
      pattern = new RegExp('(^|\/)' + searchText, 'i');
      return function (dir) {
        return pattern.test(dir.path);
      };
    }
  };

  $scope.isSelected = function (type, id) {
    return type === RefilerGalleryModel.type &&
      id === RefilerGalleryModel[type].id;
  };
});