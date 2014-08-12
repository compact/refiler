angular.module('app').controller('MenuCtrl', function ($http, $modal, $route,
    $scope, _, RefilerAPI, RefilerGalleryModel) {
  // when the Tag → Reload or Folder → Reload button is clicked
  this.reload = $route.reload;

  // when the Folder → Reload data button is clicked
  this.updateDir = function () {
    RefilerAPI.getDir(RefilerGalleryModel.dir.id, true).then(function () {
      $route.reload(); // TODO: update the model instead of reloading
    });
  };
  // when the Folder → Sync subfolders button is clicked
  this.syncSubdirs = function () {
    RefilerAPI.syncSubdirs(RefilerGalleryModel.dir.id).then(function (data) {
      $scope.gallery.alerts.push({
        'class': 'alert-success',
        'message': data.output
      });
    });
  };
  // when the Admin → Sync folders button is clicked
  this.syncDirs = function () {
    RefilerAPI.syncSubdirs(0).then(function (data) {
      $scope.gallery.alerts.push({
        'class': 'alert-success',
        'message': data.output
      });
    });
  };
  // when the Admin → Sync thumbnails button is clicked
  this.syncThumbs = function () {
    RefilerAPI.syncThumbs().then(function (data) {
      $scope.gallery.alerts.push({
        'class': 'alert-success',
        'message': data.output
      });
    });
  };

  // selection changes
  this.selectAll = function () {
    _.each(RefilerGalleryModel.files, function (file) {
      file.selected = true;
    });
  };
  this.selectNone = function () {
    _.each(RefilerGalleryModel.files, function (file) {
      file.selected = false;
    });
  };
  this.toggleSelection = function () {
    _.each(RefilerGalleryModel.files, function (file) {
      file.selected = !file.selected;
    });
  };
});
