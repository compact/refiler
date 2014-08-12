/**
 * Usage: <div ng-if="Auth.permissions.edit" file-edit-btn-group="file"></div>
 */
angular.module('app').directive('fileEditBtnGroup', function ($parse) {
  return {
    'replace': true,
    'templateUrl': 'fileEditBtnGroup.html',
    'controller': /* @ngInject */ function (RefilerModals) {
      this.openModal = RefilerModals.open;
    },
    'controllerAs': 'fileEditBtnGroup',
    'link': function (scope, element, attrs) {
      // the file needs to be watched in the case of the lightbox
      scope.$watch($parse(attrs.fileEditBtnGroup), function (file) {
        scope.file = file;
      });
    }
  };
});
