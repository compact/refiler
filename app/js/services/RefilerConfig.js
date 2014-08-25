/**
 * Configurable properties of the app.
 */
angular.module('app').provider('RefilerConfig', function () {
  // all file paths given are relative to this path; include the trailing slash
  this.basePath = '/';

  // extensions of files to be considered as images
  this.imageExtensions = ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'tiff', 'ico'];

  // either a path to an image relative to basePath, or classes for a Font
  // Awesome icon
  this.noThumb = 'fa fa-file fa-5x';
  this.noThumbTooLarge = 'fa fa-camera fa-5x';
  this.noThumbCorrupt = 'fa fa-exclamation-triangle fa-5x';

  // whether to show only parentless tags or dirs in the nav when the user has
  // not typed in a filter
  this.defaultParentlessTagsInNav = false;
  this.defaultParentlessDirsInNav = false;

  // whether to highlight the search text in the nav
  this.highlightSearchText = true;

  // TODO: default upload dir

  // service
  this.$get = function service() {
    return this;
  };
});
