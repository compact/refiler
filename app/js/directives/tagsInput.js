/**
 * Directive for tag selection.
 *
 * Usage: <tags-input ng-model=""></tags-input>
 */
angular.module('app').directive('tagsInput', function () {
  return {
    'restrict': 'E',
    'templateUrl': 'tagsInput.html',
    'controller': /* @ngInject */ function (_, RefilerModel) {
      this.tagNames = _.pluck(RefilerModel.tags, 'name');
    },
    'controllerAs': 'tagsInput'
  };
});
