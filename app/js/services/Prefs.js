angular.module('app').provider('Prefs', function () {
  // configurable default prefs
  this.defaults = {
    'showFileDetails': true,
    'sort': '-date',
    'filesPerPage': 60
  };

  // service
  this.$get = function service() {
    var Prefs;

    // initialize prefs based on localStorage and defaults
    try {
      Prefs = JSON.parse(localStorage.getItem('prefs')) || {};
    } catch (e) {
      Prefs = {};
    }
    Prefs = _.assign({}, this.defaults, Prefs);

    Prefs.set = function (key, value) {
      Prefs[key] = value;
      localStorage.setItem('prefs', JSON.stringify(Prefs));
    };

    // for boolean prefs
    Prefs.toggle = function (key) {
      Prefs.set(key, !Prefs[key]);
    };

    return Prefs;
  }
});