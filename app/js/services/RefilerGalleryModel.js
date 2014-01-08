/**
 * An object storing the tag/dir/files currently being viewed, with methods
 *   for manipulating that data.
 */
angular.module('app').service('RefilerGalleryModel', function ($filter,
    $rootScope, Prefs, RefilerFile) {
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

  this.set = function (data) {console.log(data);
    if (typeof data.tag === 'object') {
      // set tag data
      this.type = 'tag';
      this.tag = data.tag;
    } else if (typeof data.dir === 'object') {
      // set dir data
      this.type = 'dir';
      this.dir = data.dir;
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

    return this;
  };

  this.getSelectedFileIds = function () {
    return _.pluck(_.where(this.files, {'selected': true}), 'id');
  };

  this.addFile = function (file) {
    this.files.push(new RefilerFile(file));
    this.files = sortFiles(this.files);
    return this;
  };

  this.addFiles = function (files) {
    this.files = sortFiles(_.map(files, function (file) {
      return new RefilerFile(file);
    }).concat(this.files));
    return this;
  };

  this.removeFile = function (id) {
    _.remove(this.files, {'id': id});
    return this;
  };

  this.removeFiles = function (ids) {
    this.files = _.reject(this.files, function (file) {
      return _.contains(ids, file.id);
    });
    return this;
  };
});