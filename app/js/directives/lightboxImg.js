angular.module('app').directive('lightboxImg', function ($window,
    cfpLoadingBar) {
  return {
    'link': function (scope, element) {
      var $windowElement, resize;

      $windowElement = angular.element($window);

      // handler for resizing the image and the containing modal
      resize = function () {
        var imageWidth, imageHeight,
          imageDisplayWidth, imageDisplayHeight,
          imageMaxWidth, imageMaxHeight,
          modalWidth, modalHeight;

        imageWidth = scope.Lightbox.file.width;
        imageHeight = scope.Lightbox.file.height;

        // calculate the dimensions to display the image
        imageDisplayWidth = imageWidth;
        imageDisplayHeight = imageHeight;

        // calculate the max dimensions the image can have
        imageMaxWidth = $windowElement.width() - 50 * 2;
        imageMaxHeight = $windowElement.height() -
          element.parent().position().top - 100 * 2;

        // resize the image if it is too wide or tall
        if (imageWidth > imageMaxWidth && imageHeight > imageMaxHeight) {
          // the image is both too tall and wide, so compare the aspect ratios
          // to determine whether to max the width or height
          if (imageWidth / imageHeight > imageMaxWidth / imageMaxHeight) {
            imageDisplayWidth = imageMaxWidth;
            imageDisplayHeight = Math.round(
              imageHeight * imageMaxWidth / imageWidth
            );
          } else {
            imageDisplayHeight = imageMaxHeight;
            imageDisplayWidth = Math.round(
              imageWidth * imageMaxHeight / imageHeight
            );
          }
        } else if (imageWidth > imageMaxWidth) {
          // the image is too wide
          imageDisplayWidth = imageMaxWidth;
          imageDisplayHeight = Math.round(
            imageHeight * imageMaxWidth / imageWidth
          );
        } else if (imageHeight > imageMaxHeight) {
          // the image is too tall
          imageDisplayHeight = imageMaxHeight;
          imageDisplayWidth = Math.round(
            imageWidth * imageMaxHeight / imageHeight
          );
        }

        // calculate the dimensions of the modal container
        modalWidth = Math.max(imageDisplayWidth, 400);
        modalHeight = Math.max(imageDisplayHeight, 400);

        modalWidth += 42; // 1 px border on .modal-content
                          // 20 px padding on .modal-body
        modalHeight += 104;

        element.css({
          'width': imageDisplayWidth,
          'height': imageDisplayHeight
        });

        element.closest('.modal-dialog').css({
          'width': modalWidth,
          'height': modalHeight
        });
      };

      // bind
      element.bind('load', function () {
        cfpLoadingBar.complete();
        resize();
      });
      $windowElement.bind('resize', resize);
    }
  };
});