angular.module('app').service('RefilerAPI', function ($resource) {
  this.getInitData = function () {
    return $resource('api/init.json').get().$promise;
  };



  this.getTag = function (id) {
    return $resource('api/tag/:id.json').get({
      'id': id
    }).$promise;
  };


  this.deleteTag = function (id) {
    return $resource('api/tag/:id.json').delete({
      'id': id
    }).$promise;
  };

  this.editTag = function (data) {
    return $resource('api/tag/:id.json', {
      'id': '@id'
    }).save(data).$promise;
  };




  this.createDir = function (path) {
    return $resource('api/dir.json').save({
      'path': path
    }).$promise;
  };

  this.getDir = function (id, update) {
    return $resource('api/dir/:id.json').get({
      'id': id,
      'update': update || false
    }).$promise;
  };

  this.moveDir = function (data) {
    return $resource('api/dir/:id.json', {
      'id': '@id'
    }).save(data).$promise;
  };

  this.deleteDir = function (id) {
    return $resource('api/dir/:id.json').delete({
      'id': id
    }).$promise;
  };

  this.curl = function (data) {
    return $resource('api/dir/:id/files.json', {
      'id': '@dirId'
    }).save(data).$promise;
  };

  this.tagFilesByDir = function (data) {
    return $resource('api/dir/:id/files/tags.json', {
      'id': '@dirId'
    }).save(data).$promise;
  };

  this.syncSubdirs = function (id) {
    return $resource('api/dir/:id/subdirs.json', {
      'id': '@id'
    }).save({
      'id': id
    }).$promise;
  };



  this.uploadFileUrl = 'api/file.json'; // to be passed into $fileUploader

  this.editFile = function (data) {
    return $resource('api/file/:id.json', {
      'id': '@id'
    }).save(data).$promise;
  };

  this.deleteFile = function (id) {
    return $resource('api/file/:id.json').delete({
      'id': id
    }).$promise;
  };

  this.getTagsByFile = function (id) {
    return $resource('api/file/:id/tags.json').get({
      'id': id
    }).$promise;
  };

  this.updateFileThumb = function (id) {
    return $resource('api/file/:id/thumb.json', {
      'id': '@id'
    }).save({
      'id': id
    }).$promise;
  };

  this.deleteFilesByIds = function (ids) {
    return $resource('api/files/:ids.json', {
      'ids': '@fileIds'
    }).delete({
      'ids': ids.join(',')
    }).$promise;
  };

  this.moveFilesByIds = function (data) {
    data.fileIds = data.fileIds.join(',');
    return $resource('api/files/:ids.json', {
      'ids': '@fileIds'
    }).save(data).$promise;
  };

  this.tagFilesByIds = function (data) {
    data.fileIds = data.fileIds.join(',');
    return $resource('api/files/:ids/tags.json', {
      'ids': '@fileIds'
    }).save(data).$promise;
  };



  this.login = function (data) {
    return $resource('api/session.json').save(data).$promise;
  };

  this.logout = function () {
    return $resource('api/session.json').delete().$promise;
  };



  this.getUserByActivationCode = function (activationCode) {
    return $resource('api/user/:activationCode.json').get({
      'activationCode': activationCode
    }).$promise;
  };

  this.deleteUser = function (id) {
    return $resource('api/user/:id.json').delete({
      'id': id
    }).$promise;
  };

  this.activate = function (data) {
    return $resource('api/user/:id/activate.json', {
      'id': '@id'
    }).save(data).$promise;
  };



  this.getUsers = function () {
    return $resource('api/users.json').get().$promise;
  };

  this.editUsers = function (users) {
    return $resource('api/users.json').save({
      'users': users
    }).$promise;
  };

  this.syncThumbs = function () {
    return $resource('api/thumbs.json').save().$promise;
  };

  this.deleteThumbs = function () {
    return $resource('api/thumbs.json').delete().$promise;
  };
});
