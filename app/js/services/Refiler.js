angular.module('app').provider('Refiler', function () {
  // constants
  this.constants = {};
  this.constants.NO_THUMB = 'none';
  this.constants.NO_THUMB_TOO_LARGE = 'large';
  this.constants.NO_THUMB_CORRUPT = 'corrupt';
  this.constants.SELF_THUMB = 'self';

  // config
  this.config = {};

  // with trailing slash all file paths given are relative to this path
  this.config.basePath = '/';
  this.config.imageExtensions = ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'tiff',
    'ico'];

  // either a path to an image relative to basePath, or classes for a Font
  // Awesome icon
  this.config.noThumb = 'fa fa-file fa-5x';
  this.config.noThumbTooLarge = 'fa fa-camera fa-5x';
  this.config.noThumbCorrupt = 'fa fa-exclamation-triangle fa-5x';

  // whether to show only parentless tags or dirs in the nav when the user has
  // not typed in a filter
  this.config.defaultParentlessTagsInNav = false;
  this.config.defaultParentlessDirsInNav = false;

  // TODO: default upload dir

  // service
  this.$get = function service() {
    var Refiler = {};

    // constants
    Refiler.constants = this.constants;

    // config
    Refiler.config = this.config;

    return Refiler;
  };
});