angular.module('app').controller('LightboxCtrl', function ($scope, Auth,
    Lightbox, RefilerGalleryModel, RefilerModals) {
  $scope.Auth = Auth;

  // modals can be opened on top of the lightbox
  $scope.openModal = RefilerModals.open;

  // update the scope whenever the model changes
  $scope.$on('RefilerGalleryModelChange', function () {
    Lightbox.setImages(RefilerGalleryModel.filterImages());
  });
});
