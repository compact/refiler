angular.module('app').controller('MenuCtrl', function ($scope, $http, $route,
    $modal, Auth, Prefs, RefilerGalleryModel) {
  $scope.Prefs = Prefs;
  $scope.reload = $route.reload;
  $scope.Auth = Auth;



  // when the Folder → Reload data button is clicked
  $scope.updateDir = function () {
    $http.get('get/update-dir.php', {
      'params': {
        'id': RefilerGalleryModel.dir.id
      }
    }).then(function success() {
      $route.reload();
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