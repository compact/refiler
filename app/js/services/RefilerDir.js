angular.module('app').factory('RefilerDir', function service() {
  /**
   * Constructor for a dir.
   * @param {Object} dir
   */
  var RefilerDir = function (dir) {
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

  return RefilerDir;
});