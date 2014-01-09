angular.module('app').factory('RefilerDir', function service() {
  /**
   * Constructor for a dir.
   * @param {Object} dir
   */
  var RefilerDir = function (dir) {
    this.id = parseInt(dir.id, 10);
    this.path = dir.path;
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

  return RefilerDir;
});