/**
 * This scope is a child of $rootScope. The given modal is not a service, but
 *   an object from the RefilerModals service.
 */
angular.module('app').controller('ModalCtrl', function ($scope, ErrorHandler,
    data, modal) {
  var ctrl = this;

  // methods used in RefilerModals
  this.broadcast = $scope.$broadcast.bind($scope);
  this.close = $scope.$close;

  // populated by child form elements with ng-model
  this.model = {};

  // alerts shown in the modal are different from alerts shown in the gallery
  this.alerts = [];

  // custom modal properties
  this.title = modal.title;
  this.buttonText = modal.buttonText;
  this.class = modal.class;
  this.formGroups = modal.formGroups;

  // optional custom handler for the modal open event; we could call this in the
  // promise provided by ui.bootstrap.modal called 'opened' instead, but that
  // resolves after this controller
  if (typeof modal.open === 'function') {
    modal.open(this, data);
  }

  // custom submit handler
  this.submit = function () {
    // disable the modal while submitting
    this.disabled = true;

    // submit
    modal.submit(this, data);
  };

  // error handler which is not called in the template directly, but used in the
  // modal definitions in RefilerModals as a shortcut
  this.$httpErrorHandler = function (response) {
    ctrl.alerts.push({
      'message': ErrorHandler.parseMessage(response)
    });
    ctrl.disabled = false;
  };
});
