/**
 * Select2 for tags
 * Usage: <tags-input ng-model=""></tags-input>
 */
angular.module('app').directive('tagsInput', function () {
  return {
    'restrict': 'E',
    'replace': true, // replace to get the element's ng-model
    'template':
      '<input ui-select2="select2Options" style="width: 100%;"></input>',
    'controller': /* @ngInject */ function ($scope, _, RefilerModel) {
      $scope.select2Options = {
        'multiple': true,
        'simple_tags': true,
        'tokenSeparators': ',',
        'matcher': function (search, tag) {
          var pattern = new RegExp('(^| )' + search, 'i');
          return pattern.test(tag);
        },
        'tags': _.pluck(RefilerModel.tags, 'name')
      };
    }
  };
});
