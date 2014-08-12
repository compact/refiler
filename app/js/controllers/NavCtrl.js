angular.module('app').controller('NavCtrl', function ($http, Refiler,
    RefilerGalleryModel, RefilerModel) {
  this.RefilerModel = RefilerModel;
  this.searchText = '';

  /**
   * When the user has entered search text, filter the tags by name. If the
   *   config option 'defaultParentlessTagsInNav' is true, when there is no
   *   search text, show only parentless tags (otherwise the list of tags may
   *   be too long).
   * @param  {string} searchText
   * @return {Object} Object passed into ng.filter:filter.
   */
  this.tagFilter = function (searchText) {
    return Refiler.config.defaultParentlessTagsInNav && searchText === '' ?
      {'parentCount': 0} :
      {'name': searchText};
  };

  /**
   * Dir version of tagFilter(). The difference is dirs are searched by text
   *   following slashes, as in 'ba' filters 'foo/bar' but 'ar' does not.
   * @param  {string}   searchText
   * @return {Function} Function passed into ng.filter:filter.
   */
  this.dirFilter = function (searchText) {
    if (Refiler.config.defaultParentlessDirsInNav && searchText === '') {
      return function (dir) {
        return dir.path.indexOf('/') === -1;
      };
    } else {
      return {'displayPath': searchText};
    }
  };

  this.isSelected = function (type, id) {
    return type === RefilerGalleryModel.type &&
      id === RefilerGalleryModel[type].id;
  };

  this.perfectScrollbarOptions = {
    'wheelSpeed': 60,
    'wheelPropagation': true,
    'minScrollbarLength': 100,
    'suppressScrollX': true
  };

  this.dirDetails = function (dir) {
    if (dir.subdirs.length > 0) {
      return dir.fileCount + ' ~ ' + dir.subdirs.length;
    } else if (dir.fileCount > 0) {
      return dir.fileCount;
    } else {
      return '';
    }
  };

  this.highlightSearchText = Refiler.config.highlightSearchText;
});
