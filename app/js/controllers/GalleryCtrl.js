/**
 * Remember the route changes for each tag or dir, so this controller does not
 *   persist through those changes.
 */
angular.module('app').controller('GalleryCtrl', function ($modal, Auth,
    Lightbox, Prefs, RefilerModel, RefilerGalleryModel, RefilerModals) {
  var ctrl = this;

  if (RefilerGalleryModel.type === 'tag') {
    // shortcut to not pollute the HTML with "RefilerGalleryModel.tag"
    this.tag = RefilerGalleryModel.tag;

    RefilerModel.page.title = RefilerGalleryModel.tag.name;
  } else if (RefilerGalleryModel.type === 'dir') {
    this.dir = RefilerGalleryModel.dir;

    RefilerModel.page.title = RefilerGalleryModel.dir.formatNestedLink();
  }

  // services
  this.Auth = Auth;
  this.Prefs = Prefs;
  this.Model = RefilerGalleryModel; // shorthand

  // pagination
  this.page = 1;

  // alerts
  this.alerts = [];

  // open a file modal
  this.openFile = function ($event, file) {
    if ($event.which === 1 && file.isImage()) {
      // open the lightbox modal
      Lightbox.openModal(
        RefilerGalleryModel.filterImages(),
        RefilerGalleryModel.getImageIndex(file)
      );
    } else {
      // trigger middle click
      // TODO: the cursor gets stuck in the loading animation
      $event.which = 2;
    }
  };

  // modals
  this.openModal = function (key, data) {
    var modal = RefilerModals.open(key, data);

    modal.result.then(function close(alerts) { // resolved from $close()
      // modals may optionally pass in alerts for displaying in the gallery;
      // alerts may be a single alert object or an array of alert objects, and
      // concat() works for both cases
      if (typeof alerts === 'object') {
        ctrl.alerts = ctrl.alerts.concat(alerts);
      }
    });
  };
});
