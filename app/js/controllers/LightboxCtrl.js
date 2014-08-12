angular.module('app').controller('LightboxCtrl', function ($scope, Auth,
    Lightbox, RefilerGalleryModel) {
  // service
  this.Auth = Auth;

  // update the Lightbox whenever the model changes
  $scope.$on('RefilerGalleryModelChange', function () {
    Lightbox.setImages(RefilerGalleryModel.filterImages());
  });
});
