angular.module('app').controller('LightboxCtrl', function ($scope, Auth,
    Lightbox, RefilerGalleryModel) {
  // service
  this.Auth = Auth;

  // update the Lightbox whenever the model changes
  var off = $scope.$on('RefilerGalleryModelChange', function () {
    Lightbox.setImages(RefilerGalleryModel.filterImages());
  });

  // deregister the listener when this controller is destroyed
  $scope.$on('$destroy', off);
});
