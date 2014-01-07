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
   * @param  {[type]} searchText [description]
   * @return {[type]}            [description]
   */
  $scope.tagFilter = function (searchText) {
    return Refiler.config.defaultParentlessTagsInNav && searchText === '' ?
      {'parentCount': 0} :
      {'name': searchText};
  };

  $scope.isSelected = function (type, id) {
    return type === RefilerGalleryModel.type && id === RefilerGalleryModel.id;
  };
});