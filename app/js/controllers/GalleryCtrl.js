angular.module('app').controller('GalleryCtrl', function ($scope, $modal, Auth,
    Lightbox, Prefs, Refiler, RefilerGalleryModel, RefilerModals) {
  // the following properties are set only once, when the tag or dir is loaded,
  // since the route changes for each tag or dir
  if (RefilerGalleryModel.type === 'tag') {
    // tag properties
    $scope.tag = {};

    Refiler.page.title = RefilerGalleryModel.tag.name;
    $scope.tag.parents = RefilerGalleryModel.tag.parents;
    $scope.tag.children = RefilerGalleryModel.tag.children;
  } else if (RefilerGalleryModel.type === 'dir') {
    // dir properties
    $scope.dir = {};

    Refiler.page.title = '/' + RefilerGalleryModel.dir.path;
  }

  // files
  $scope.RefilerGalleryModel = RefilerGalleryModel;

  // pagination
  $scope.Prefs = Prefs;
  $scope.page = 1;

  // Auth
  $scope.Auth = Auth;

  // alerts
  $scope.alerts = [];

  // modals
  $scope.openModal = function (key, data) {
    var modal = RefilerModals.open(key, data);

    modal.result.then(function close(alerts) { // resolved from $close()
      // modals may optionally pass in alerts for displaying in the gallery;
      // alerts may be a single alert object or an array of alert objects, and
      // concat() works for both cases
      if (typeof alerts === 'object') {
        $scope.alerts = $scope.alerts.concat(alerts);
      }
    });
  };

  // Lightbox modal
  $scope.openLightboxModal = Lightbox.openModal;
});