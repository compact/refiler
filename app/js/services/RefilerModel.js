angular.module('app').service('RefilerModel', function ($http, $q, RefilerAPI,
    RefilerDir) {
  var self = this;

  // page
  this.page = {};
  this.page.title = ''; // the current title displayed in <h1>
  this.page.error = false; // whether a $routeChangeError has occurred

  var deferred = $q.defer();
  // these properties can be used directly when it's known that the promise must
  // have resolved
  this.user = null;
  this.tags = null;
  this.dirs = null;

  RefilerAPI.getInitData().then(function (data) {
    self.user = data.user;
    self.tags = data.tags;
    self.setDirs(data.dirs);

    deferred.resolve(self);
  });



  /**
   * @return {Promise}
   */
  this.ready = function () {
    return deferred.promise;
  };



  this.getTagByUrl = function (url) {
    return _.where(this.tags, {'url': url})[0] || null;
  };

  /**
   * Add the given tag names to the model, if they don't already exist in it.
   * @param {array} names
   */
  this.addTagNames = function (names) {
    var tagsPushed = false; // determines whether the tags have to be sorted

    _.each(names, function (name) {
      if (_.where(self.tags, {'name': name}).length === 0) {
        // see Tag::get_default_url()
        var url = name.toLowerCase().replace(/[ \W]+/g, '-');

        self.tags.push({
          // id not set until the user navigates to the tag
          'name': name,
          'url': url,
          'caption': '',
          'fileCount': 'new', // displayed placeholder until the user reloads
          'parentCount': 0,
          'childCount': 0
        });

        tagsPushed = true;
      }
    });

    if (tagsPushed) {
      this.sortTags();
    }
  };

  /**
   * Remove the tag with the given id from the model.
   * @param  {number} id
   */
  this.removeTag = function (id) {
    _.remove(this.tags, {'id': id});
  };

  /**
   * The tag to be updated is matched by id, or name (if the id isn't found).
   *   The second case is useful when a new tag's id is not yet known.
   * @param  {object} data
   */
  this.updateTag = function (data) {
    var tags = _.where(self.tags, {'id': data.id});

    if (tags.length === 0) {
      tags = _.where(self.tags, {'name': data.name});
    }

    _.assign(tags[0], data);
  };

  /**
   * Sort the dirs. Call this method after updating the model.
   */
  this.sortTags = function () {
    this.tags = _.sortBy(this.tags, 'name');
  };



  /**
   * Set the dirs with the given data, which is a tree of plain objects that
   *   must be flattened and converted to RefilerDir objects.
   * @param {Object[]} dirs
   */
  this.setDirs = function (dirs) {
    var convert = function (dirs) {
      return _.map(dirs, function (data) {
        data.subdirs = convert(data.subdirs);
        return new RefilerDir(data);
      });
    };

    var flatten = function (dirs) {
      var result = [];

      _.each(dirs, function (dir) {
        result.push(dir);

        if (dir.subdirs.length > 0) {
          result = result.concat(flatten(dir.subdirs));
        }
      });

      return result;
    };

    this.dirs = flatten(convert(dirs));
  };

  /**
   * @param  {number}          id
   * @return {RefilerDir|null}
   */
  this.getDir = function (id) {
    return _.where(this.dirs, {'id': id})[0] || null;
  };

  /**
   * @param  {string}          path
   * @return {RefilerDir|null}
   */
  this.getDirByPath = function (path) {
    return _.where(this.dirs, {'path': path})[0] || null;
  };

  /**
   * Add the given dir to the model.
   * @param {object} data Does not have to be a RefilerDir.
   */
  this.addDir = function (data) {
    var dir = new RefilerDir(data);

    // add to this.dirs, and sort it
    this.dirs.push(dir);
    this.sortDirs();

    // add as a subdir to the parent dir, and sort the subdirs
    var parentPath = dir.getParentPath();
    if (parentPath !== '.') {
      var parent = _.where(this.dirs, {'path': parentPath})[0];
      parent.subdirs.push(dir);
      parent.subdirs = _.sortBy(parent.subdirs, 'path');
    }
  };

  /**
   * Remove the dir with the given id from the model.
   * @param {number} id
   */
  this.removeDir = function (id) {
    _.remove(this.dirs, {'id': id});
  };

  /**
   * Sort the dirs. Call this method after updating the model.
   */
  this.sortDirs = function () {
    this.dirs = _.sortBy(this.dirs, 'path');
  };
});
