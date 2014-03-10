/**
 * Remember the route changes for each tag or dir, so this controller does not
 *   persist through those changes.
 */
angular.module('app').controller('GalleryCtrl', function ($scope, $modal, Auth,
    Lightbox, Prefs, RefilerModel, RefilerGalleryModel, RefilerModals) {
  if (RefilerGalleryModel.type === 'tag') {
    // shortcut to not pollute the HTML with "RefilerGalleryModel.tag"
    $scope.tag = RefilerGalleryModel.tag;

    RefilerModel.page.title = RefilerGalleryModel.tag.name;
  } else if (RefilerGalleryModel.type === 'dir') {
    $scope.dir = RefilerGalleryModel.dir;

    RefilerModel.page.title = RefilerGalleryModel.dir.formatNestedLink();
  }

  // services
  $scope.Auth = Auth;
  $scope.Prefs = Prefs;
  $scope.RefilerGalleryModel = RefilerGalleryModel;

  // pagination
  $scope.page = 1;

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
  $scope.openLightboxModal = function (file) {
    Lightbox.openModal(
      RefilerGalleryModel.filterImages(),
      RefilerGalleryModel.getImageIndex(file)
    );
  };
});
