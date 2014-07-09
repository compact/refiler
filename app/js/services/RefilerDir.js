angular.module('app').factory('RefilerDir', function service() {
  /**
   * Constructor for a dir.
   * @param {Object} data
   */
  var RefilerDir = function (data) {
    this.id = parseInt(data.id, 10);
    this.path = data.path;
    this.displayPath = '/' + data.path;
    this.fileCount = parseInt(data.fileCount, 10);
    this.subdirs = data.subdirs;
  };

  /**
   * @param  {Object} attrs
   * @return {string}
   */
  RefilerDir.prototype.formatLink = function (attrs) {
    return '<a href="#!/dir/' + this.path + '"' +
      _.reduce(attrs, function (html, value, attr) {
        return html + ' ' + attr + '="' + value + '"';
      }, '') +
      '>/' + this.path + '</a>';
  };

  /**
   * @return {string} HTML where each segment of the path, except the last
   *   segment, is linked.
   */
  RefilerDir.prototype.formatNestedLink = function () {
    var segments = this.path.split('/'), cumulativeSegment = '';
    return _.map(segments.slice(0, -1), function (segment) {
      cumulativeSegment += '/' + segment;
      return '/<a href="#!/dir' + cumulativeSegment + '">' + segment + '</a>';
    }).join('') + '/' + segments.slice(-1)[0];
  };

  /**
   * @return {string} The base name of this dir.
   */
  RefilerDir.prototype.getName = function () {
    return this.path.match(/[^\/]+$/)[0];
  };

  /**
   * @return {string} The path of the parent dir to this dir.
   */
  RefilerDir.prototype.getParentPath = function () {
    var matches = this.path.match(/^(.*)\/[^\/]+$/);
    return matches === null ? '.' : matches[1];
  };

  /**
   * @param {string} path
   */
  RefilerDir.prototype.setPath = function (path) {
    this.path = path;
    this.displayPath = '/' + this.path;
  };

  /**
   * Sanitize the given path.
   */
  RefilerDir.sanitizePath = function (path) {
    // remove trailing slashes
    path = path.replace(/^\/+|\/+$/, '');

    // trim repeated slashes
    path = path.replace(/\/+/g, '/');

    return path;
  };

  return RefilerDir;
});
