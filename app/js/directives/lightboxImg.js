angular.module('app').directive('lightboxImg', function ($window, cfpLoadingBar,
    Lightbox) {
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
        // 102px = 2 * (30px margin of .modal-dialog
        //              + 1px border of .modal-content
        //              + 20px padding of .modal-body)
        // with the goal of 30px side margins; however, the actual side margins
        // will be slightly less (at 22.5px) due to the vertical scrollbar
        imageMaxWidth = $windowElement.width() - 102;
        // 156px = 102px as above
        //         + 22px height of .lightbox-nav
        //         + 8px margin-top of .lightbox-image-details
        //         + 24px height of .lightbox-image-details
        //         + 8px margin-top of .lightbox-image-container
        imageMaxHeight = $windowElement.height() - 164;

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
        // 42px = 2 * (1px border of .modal-content
        //        + 20px padding of .modal-body)
        modalWidth = Math.max(
          imageDisplayWidth + 42,
          Lightbox.minModalWidth || 0
        );
        // 104px = 42px as above
        //         + 22px height of .lightbox-nav
        //         + 8px margin-top of .lightbox-image-details
        //         + 24px height of .lightbox-image-details
        //         + 8px margin-top of .lightbox-image-container
        modalHeight = Math.max(
          imageDisplayHeight + 104,
          Lightbox.minModalHeight || 0
        );

        // resize the image
        element.css({
          'width': imageDisplayWidth,
          'height': imageDisplayHeight
        });

        // setting the height on .modal-dialog does not expand the div with the
        // background, which is .modal-content
        element.closest('.modal-dialog').css({
          'width': modalWidth
        });

        // .modal-content has no width specified; if we set the width on .modal-
        // .content and not on.modal-dialog, .modal-dialog retains its default
        // .width of 600px and that places .modal-content off center
        element.closest('.modal-content').css({
          'height': modalHeight
        });
      };

      // initial resize for the first image
      resize();

      // bind
      element.bind('load', function () {
        cfpLoadingBar.complete();
        resize();
      });
      $windowElement.bind('resize', resize);
    }
  };
});