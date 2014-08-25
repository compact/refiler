angular.module('app').factory('RefilerFile', function service(_, RefilerConfig,
    RefilerConstants) {
  /**
   * Each file gets an instance of this class.
   * @param {Object} file
   */
  var RefilerFile = function (file) {
    this.id = parseInt(file.id, 10);
    this.dirPath = file.dirPath;
    this.name = file.name;
    this.type = file.type;
    this.caption = file.caption;
    this.size = parseInt(file.size, 10);
    this.date = file.date;
    this.width = parseInt(file.width, 10);
    this.height = parseInt(file.height, 10);
    this.thumbType = file.thumb_type;

    // for Lightbox
    this.url = this.getPath();

    // whether the file is currently selected by the user (for actions such as
    // tag and move)
    this.selected = false;
  };



  // static helper functions
  RefilerFile.formatSize = function (size) {
    var index = 0, units = ['B', 'KB', 'MB', 'GB'];
    while (size / 1024 > 1) {
      size /= 1024;
      index++;
    }
    // round to 1 decimal place if MB or higher
    size = index > 1 ? Math.round(size * 10) / 10 : Math.round(size);
    return size + ' ' + units[index];
  };



  // output formatting functions
  RefilerFile.prototype.formatSize = function () {
    return RefilerFile.formatSize(this.size);
  };

  RefilerFile.prototype.formatDate = function () {
    return this.date; //.substr(0, 10);
  };

  RefilerFile.prototype.formatDimensions = function () {
    return this.width + 'x' + this.height;
  };

  RefilerFile.prototype.formatTags = function () {
    var html = '', links = [];
    if (typeof this.tags === 'object') {
      _.each(this.tags, function (tag) {
        links.push('<a href="#!/' + tag.url + '">' + tag.name + '</a>');
      });
      html = 'Tags: ' + links.join(', ');
    }
    return html;
  };

  RefilerFile.prototype.formatLink = function (attrs) {
    return '<a href="' + this.getPath() + '"' +
      _.reduce(attrs, function (html, value, attr) {
        return html + ' ' + attr + '="' + value + '"';
      }, '') +
      '>' + this.name + '</a>';
  };

  // path functions

  /**
   * @param  string [includeBasePath=true]
   * @return string
   */
  RefilerFile.prototype.getPath = function (includeBasePath) {
    var path = this.dirPath + '/' + this.name;
    if (typeof includeBasePath === 'undefined' || includeBasePath) {
      return RefilerConfig.basePath + path;
    } else {
      return path;
    }
  };

  RefilerFile.prototype.getThumb = function () {
    switch (this.thumbType) {
      case RefilerConstants.NO_THUMB:
        return RefilerConfig.noThumb;
      case RefilerConstants.NO_THUMB_TOO_LARGE:
        return RefilerConfig.noThumbTooLarge;
      case RefilerConstants.NO_THUMB_CORRUPT:
        return RefilerConfig.noThumbCorrupt;
      case RefilerConstants.SELF_THUMB:
        return this.getPath();
      default: // see Refiler\File::get_default_thumb_path()
        return RefilerConfig.basePath + 'thumbs' + this.getPath().replace(
          /\/([^\/]+)\.[^.]+$/,
          '/' + (this.type === 'gif' ? 'gif-' : '') +
          'thumb-$1.' + this.thumbType
        );
    }
  };

  RefilerFile.prototype.getThumbElement = function () {
    return this.getThumb().match(/[./]/) !== null ? 'img' : 'i';
  };

  RefilerFile.prototype.isImage = function () {
    return _.indexOf(RefilerConfig.imageExtensions, this.type) !== -1 &&
      this.width > 0 && this.height > 0; // additional check
  };

  return RefilerFile;
});
