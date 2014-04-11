/**
 * An object storing the tag/dir/files currently being viewed, with methods
 *   for manipulating that data.
 */
angular.module('app').service('RefilerGalleryModel', function ($filter,
    $rootScope, Prefs, RefilerDir, RefilerFile) {
  var self, sortFiles;

  this.files = [];

  self = this;

  // the order of the this.files array is determined by the user preference
  // Prefs.sort; we apply the orderBy filter here instead of in a controller as
  // multiple controllers use this model
  sortFiles = function (files) {
    return $filter('orderBy')(files, Prefs.sort);
  };

  // sort the files whenever the user changes their sort preference
  $rootScope.$watch(function () {
    return Prefs.sort;
  }, function () {
    self.files = sortFiles(self.files);
  });

  this.set = function (data) {
    if (typeof data.tag === 'object') {
      // set tag data
      this.type = 'tag';
      this.tag = data.tag;
    } else if (typeof data.dir === 'object') {
      // set dir data
      this.type = 'dir';
      this.dir = new RefilerDir(data.dir);
    } else {
      this.type = '';
    }

    if (typeof data.files === 'object') {
      // set files as RefilerFile objects
      this.files = sortFiles(_.map(data.files, function (file) {
        return new RefilerFile(file);
      }));
    } else {
      this.files = [];
    }

    $rootScope.$broadcast('RefilerGalleryModelChange', this);
    return this;
  };

  this.getSelectedFileIds = function () {
    return _.pluck(_.where(this.files, {'selected': true}), 'id');
  };

  /**
   * @return boolean True if at least one file is selected.
   */
  this.hasSelectedFiles = function () {
    return _.some(this.files, {'selected': true});
  };

  this.getFile = function (id) {
    return _.where(this.files, {'id': id})[0];
  };

  this.addFile = function (file) {
    this.files.push(new RefilerFile(file));
    this.files = sortFiles(this.files);

    $rootScope.$broadcast('RefilerGalleryModelChange', this);
    return this;
  };

  this.addFiles = function (files) {
    this.files = sortFiles(_.map(files, function (file) {
      return new RefilerFile(file);
    }).concat(this.files));

    $rootScope.$broadcast('RefilerGalleryModelChange', this);
    return this;
  };

  this.removeFile = function (id) {
    _.remove(this.files, {'id': id});

    $rootScope.$broadcast('RefilerGalleryModelChange', this);
    return this;
  };

  this.removeFiles = function (ids) {
    this.files = _.reject(this.files, function (file) {
      return _.contains(ids, file.id);
    });

    $rootScope.$broadcast('RefilerGalleryModelChange', this);
    return this;
  };

  this.updateFile = function (file) {
    var index = _.findIndex(this.files, {'id': file.id});
    this.files[index] = new RefilerFile(file);

    $rootScope.$broadcast('RefilerGalleryModelChange', this);
    return this;
  };

  this.filterImages = function () {
    return _.filter(this.files, function (file) {
      return file.isImage();
    });
  };

  this.getImageIndex = function (file) {
    return _.findIndex(this.filterImages(), {'id': file.id});
  };
});
