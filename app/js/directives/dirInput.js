/**
 * Directive for dir selection.
 *
 * Usage: <dir-input ng-model=""></dir-input>
 */
angular.module('app').directive('dirInput', function () {
  return {
    'restrict': 'E',
    'templateUrl': 'dirInput.html',
    'controller': /* @ngInject */ function (_, RefilerModel) {
      // trim down the big stored model to reduce lag in the select element
      this.displayPaths = _.pluck(RefilerModel.dirs, 'displayPath');
    },
    'controllerAs': 'dirInput'
  };
});
