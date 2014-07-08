angular.module('app').controller('MenuCtrl', function ($scope, $http, $route,
    $modal, Auth, Prefs, RefilerAPI, RefilerGalleryModel) {
  $scope.Prefs = Prefs;
  $scope.reload = $route.reload;
  $scope.Auth = Auth;
  $scope.RefilerGalleryModel = RefilerGalleryModel;



  // when the Folder → Reload data button is clicked
  $scope.updateDir = function () {
    RefilerAPI.getDir(RefilerGalleryModel.dir.id, true).then(function () {
      $route.reload(); // TODO: update the model instead of reloading
    });
  };

  // selection changes
  $scope.selectAll = function () {
    _.each(RefilerGalleryModel.files, function (file) {
      file.selected = true;
    });
  };
  $scope.selectNone = function () {
    _.each(RefilerGalleryModel.files, function (file) {
      file.selected = false;
    });
  };
  $scope.toggleSelection = function () {
    _.each(RefilerGalleryModel.files, function (file) {
      file.selected = !file.selected;
    });
  };
});