angular.module('app').service('ErrorHandler', function () {
  /**
   * @param  {String|Object} r Response or rejection.
   * @return {String}          Error message.
   */
  this.parseMessage = function (r) {
    var message = 'Error.'; // default message
    if (typeof r === 'string') {
      message = r;
    } else if (typeof r === 'object') {
      if (typeof r.data === 'string') {
        message = r.data;
      } else if (typeof r.data === 'object' &&
          typeof r.data.error === 'string') {
        message = r.data.error;
      }
    }
    return message;
  };
});
