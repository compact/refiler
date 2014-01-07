angular.module('app').factory('Prefs', function service() {
  var defaultPrefs, Prefs;

  defaultPrefs = {
    'showFileDetails': false,
    'sort': '-date',
    'filesPerPage': 60
  };

  // initialize prefs
  try {
    Prefs = JSON.parse(localStorage.getItem('prefs')) || {};
  } catch (e) {
    Prefs = {};
  }
  Prefs = _.assign({}, defaultPrefs, Prefs);

  Prefs.set = function (key, value) {
    Prefs[key] = value;
    localStorage.setItem('prefs', JSON.stringify(Prefs));
  };

  // for boolean prefs
  Prefs.toggle = function (key) {
    Prefs.set(key, !Prefs[key]);
  };

  return Prefs;
});