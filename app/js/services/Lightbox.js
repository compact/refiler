angular.module('app').provider('Lightbox', function () {
  this.minModalWidth = 0;
  this.minModalHeight = 0;

  this.$get = function service($document, $modal, $timeout, cfpLoadingBar, Auth,
      RefilerGalleryModel, RefilerModals) {
    var opened, imageIndex, incrementImageIndex, Lightbox;

    // whether the lightbox is currently open; used in the keydown event handler
    opened = false;

    // the index of the current image in RefilerGalleryModel.files; used for
    // next and prev image navigation
    imageIndex = 0;

    // extra logic here since some files may not be images
    incrementImageIndex = function (step) {
      imageIndex = (imageIndex + step + RefilerGalleryModel.files.length) %
        RefilerGalleryModel.files.length;
      if (RefilerGalleryModel.files[imageIndex].isImage()) {
        return imageIndex;
      } else {
        return incrementImageIndex(step);
      }
    };

    // the service object
    Lightbox = {};

    // config
    Lightbox.minModalWidth = this.minModalWidth;
    Lightbox.minModalHeight = this.minModalHeight;

    Lightbox.openModal = function (file) {
      imageIndex = _.findIndex(RefilerGalleryModel.files, {'id': file.id});
      Lightbox.file = file;
      cfpLoadingBar.start();

      $modal.open({
        'templateUrl': 'partials/lightbox.html',
        'controller': ['$scope', function ($scope) {
          // modal scope, a child of $rootScope
          $scope.Lightbox = Lightbox;
          $scope.Auth = Auth;
          $scope.openModal = RefilerModals.open;
          opened = true;
        }],
        'windowClass': 'lightbox-modal'
      }).result.finally(function () {
        cfpLoadingBar.complete();
        opened = false;
      });
    };

    Lightbox.nextImage = function () {
      Lightbox.file = RefilerGalleryModel.files[incrementImageIndex(1)];
      cfpLoadingBar.start();
    };

    Lightbox.prevImage = function () {
      Lightbox.file = RefilerGalleryModel.files[incrementImageIndex(-1)];
      cfpLoadingBar.start();
    };

    // no unbind
    $document.bind('keydown', function (event) {
      if (opened) {
        switch (event.which) {
        case 39: // right arrow key
          // don't know why the view doesn't update without this manual digest
          $timeout(function () {
            Lightbox.nextImage();
          });
          return false;
        case 37: // left arrow key
          $timeout(function () {
            Lightbox.prevImage();
          });
          return false;
        }
      }
    });

    return Lightbox;
  };
});