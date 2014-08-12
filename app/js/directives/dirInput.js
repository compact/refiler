/**
 * Select2 for dirs.
 *
 * Usage: <dir-input ng-model=""></dir-input>
 */
angular.module('app').directive('dirInput', function () {
  return {
    'restrict': 'E',
    'replace': true, // replace to get the element's ng-model
    'template': '<input ui-select2="dirInput.select2Options" style="width: 100%;"></input>',
    'controller': /* @ngInject */ function (_, RefilerModel) {
      this.select2Options = {
        'data': _.map(RefilerModel.dirs, function (dir) {
          // format required by Select2
          return {
            'id': dir.id,
            'text': dir.displayPath
          };
        }),
        'matcher': function (search, dir) {
          var pattern = new RegExp('(^|\/)' + search, 'i');
          return pattern.test(dir);
        }
      };
    },
    'controllerAs': 'dirInput'
  };
});
