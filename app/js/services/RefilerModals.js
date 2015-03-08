/**
 * The modals defined here are opened in GalleryCtrl, its descendant MenuCtrl,
 *   and in the lightbox.
 */
angular.module('app').service('RefilerModals', function ($location, $modal,
    $route, $timeout, _, Auth, FileUploader, RefilerAPI, RefilerFile,
    RefilerGalleryModel, RefilerModel) {
  var modals = {};



  /**
   * Open the modal with the given key.
   * @param {string} key
   * @param {*}      [data] Passed directly into the modal's open and submit
   *                        event handlers.
   */
  this.open = function (key, data) {
    return $modal.open({
      'templateUrl': 'modal.html',
      'controller': 'ModalCtrl as modal',
      'resolve': {
        'data': function () {
          return data;
        },
        'modal': function () {
          return modals[key]; // one of the objects below
        }
      },
      'windowClass': modals[key].class
    });
  };



  // modals opened from the gallery
  modals.editFile = {
    'title': 'Edit file',
    'buttonText': 'Save',
    'formGroups': [
      {
        'label': 'File',
        'control': '<input type="text" value="{{modal.path}}"' +
          ' class="form-control" disabled>'
      },
      {
        'label': 'New folder',
        'control': '<dir-input ng-model="modal.model.dirDisplayPath"></dir-input>'
      },
      {
        'label': 'New name',
        'control': '<input type="text" ng-model="modal.model.name"' +
          ' class="form-control">'
      },
      {
        'label': 'Caption',
        'control': '<textarea ng-model="modal.model.caption"' +
          ' class="form-control"></textarea>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="modal.model.tagNames"></tags-input>'
      },
      {
        'label': 'Thumbnail',
        'control': '<file-thumb file="modal.file"></file-thumb><br>' +
          '<a ng-if="modal.file.isImage()" ng-click="modal.updateThumb()">' +
            'Regenerate' +
          '</a>'
      }
    ],
    'open': function (ctrl, file) {
      ctrl.disabled = true;

      ctrl.path = file.getPath(); // contains the dir's display path

      RefilerAPI.getTagsByFile(file.id).then(function (data) {
        ctrl.model.tagNames = _.pluck(data.tags, 'name');
        ctrl.disabled = false;
      });

      ctrl.model.caption = file.caption;
      ctrl.model.dirDisplayPath = RefilerModel.getDirByPath(file.dirPath)
        .displayPath;
      ctrl.model.name = file.name;

      // used for showing the thumb
      ctrl.file = file;

      ctrl.updateThumb = function () {
        RefilerAPI.updateFileThumb(file.id).then(function (data) {
          // update the model
          RefilerGalleryModel.updateFile(data.file);

          // add alert (the modal isn't closed)
          ctrl.alerts.push({
            'class': 'alert-success',
            'message': 'Thumbnail updated.'
          });
        });
      };
    },
    'submit': function (ctrl, file) {
      var data, newDirPath, newName;

      data = {
        'id': file.id,
        'tagNames': ctrl.model.tagNames,
        'caption': ctrl.model.caption
      };

      // idiosyncratic: only POST the dir path and name if they have changed
      newDirPath = RefilerModel.getDirByDisplayPath(ctrl.model.dirDisplayPath)
        .path;
      newName = ctrl.model.name;
      if (file.dirPath !== newDirPath || file.name !== newName) {
        data.dirPath = newDirPath;
        data.name = newName;
      }

      RefilerAPI.editFile(data).then(function (data) {
        ctrl.close();

        // update the gallery
        if (RefilerGalleryModel.type === 'dir' &&
            data.file.dirPath !== RefilerGalleryModel.dir.path) {
          RefilerGalleryModel.removeFile(data.file.id);
        } else { // TODO for tags: remove the file if it has been untagged
          RefilerGalleryModel.updateFile(data.file);
        }

        // update the tags model
        RefilerModel.mergeTags(data.tags);
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.deleteFile = {
    'title': 'Delete file',
    'buttonText': 'Delete',
    'formGroups': [
      {
        'label': 'File',
        'control': '<input type="text" value="{{modal.name}}"' +
          ' class="form-control" disabled>'
      }
    ],
    'open': function (ctrl, file) {
      ctrl.name = file.name;

      // focus the submit button
      $timeout(function () {
        // wait for the next digest because the focus-on directive needs to be
        // linked first
        ctrl.broadcast('focusButton');
      });
    },
    'submit': function (ctrl, file) {
      RefilerAPI.deleteFile(file.id).then(function () {
        RefilerGalleryModel.removeFile(file.id);

        ctrl.close();
      }, ctrl.$httpErrorHandler);
    }
  };



  // modals opened from the menu
  modals.uploadFiles = {
    'title': 'Upload files from computer',
    'buttonText': 'Upload',
    'class': 'modal-wide',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<dir-input ng-model="modal.model.dirDisplayPath"></dir-input>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="modal.model.tagNames"></tags-input>'
      },
      {
        'label': 'Files',
        'control':
          // file drop area
          '<div nv-file-drop nv-file-over over-class="alert-info"' +
              ' uploader="modal.uploader"' +
              ' ng-if="modal.uploader.isHTML5" class="alert alert-warning">' +
            'Drag and drop files here, or use the input.<br><br>' +
            // file input
            '<input type="file" nv-file-select uploader="modal.uploader"' +
              ' multiple>' +
          '</div>' +
          // table showing queued files
          '<table class="table" ng-show="modal.uploader.queue.length > 0">' +
            '<thead>' +
              '<tr>' +
                '<th>Filename</th>' +
                '<th ng-if="modal.uploader.isHTML5">Size</th>' +
                '<th ng-if="modal.uploader.isHTML5">Progress</th>' +
                '<th></th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>' +
              // item row
              '<tr ng-repeat="(key, item) in modal.uploader.queue">' +
                // file name
                '<td>' +
                  '<input type="text" ng-model="item.formData[0].name"' +
                      'class="form-control">' +
                '</td>' +
                // file size
                '<td ng-if="modal.uploader.isHTML5"' +
                    ' style="white-space: nowrap;">' +
                  '{{modal.formatSize(item.file.size)}}' +
                '</td>' +
                '<td ng-if="modal.uploader.isHTML5">' +
                  '<div class="progress" ng-class="{' +
                    '\'progress-striped active\': !item.isSuccess' +
                  '}">' +
                    '<div role="progressbar" class="progress-bar" ng-class="{' +
                      '\'progress-bar-success\': item.isSuccess' +
                    '}" ng-style="{' +
                      '\'width\': item.progress + \'%\'' +
                    '}">' +
                    '</div>' +
                  '</div>' +
                '</td>' +
                '<td style="white-space: nowrap;">' +
                  '<button type="button" class="btn btn-xs btn-danger"' +
                      ' ng-click="item.remove()">' +
                    '<i class="fa fa-fw fa-trash-o"></i>' +
                  '</button>' +
                '</td>' +
              '</tr>' +
            '</tbody>' +
          '</table>'
      }
    ],
    'open': function (ctrl) {
      // arrays of RefilerFile objects used to generate alerts in the
      // completeall event below
      var uploadedFiles = [], failedFiles = [];

      // init
      ctrl.model.dir = {};

      if (RefilerGalleryModel.type === 'dir') {
        // if the user is currently viewing a dir, default to that dir for
        // upload
        ctrl.model.dirDisplayPath = RefilerGalleryModel.dir.displayPath;
      } else if (RefilerGalleryModel.type === 'tag') {
        // likewise for a tag
        ctrl.model.tagNames = [RefilerGalleryModel.tag.name];
      }

      // FileUploader configuration. FileUploader uses an XMLHttpRequest
      // object rather than $http for requests, so our $http configurations do
      // not apply. I decided to handle requests and responses manually in a
      // different way than the rest of the app rather than try to integrate
      // usage of $http.
      ctrl.uploader = new FileUploader({
        'scope': ctrl,
        'url': RefilerAPI.uploadFileUrl,
         // purely for visual consistency so the queue doesn't disappear row by
         // row; we close the modal after all files have uploaded
        'removeAfterUpload': false
      });

      ctrl.uploader.onAfterAddingFile = function (item) {
        item.formData.push({
          'name': item.file.name // used as ng-model to rename the file
        });
      };

      ctrl.uploader.onSuccessItem = function (item, data) {
        // the attempt to upload one file has completed
        if (typeof data.success === 'boolean') {
          if (data.success) {
            uploadedFiles.push({
              'dirPath': data.file.dirPath,
              'name': data.file.name
            });

            // add the file to the model immediately, if it belong in the model
            if (RefilerGalleryModel.type === 'dir' &&
                data.file.dirPath === RefilerGalleryModel.dir.path) {
              RefilerGalleryModel.addFile(data.file);
            }
          } else if (typeof data.error === 'string') {
            // if data.file doesn't exist, there is a general error such as dir
            // not found
            failedFiles.push({
              'name': typeof data.file === 'object' ? data.file.name : '',
              'error': data.error
            });
          }
        } else {
          // should never get to this point; probably an uncaught exception or a
          // printed error
          console.warn('reached unexpected case', data);
        }

        // update the tags model
        RefilerModel.mergeTags(data.tags);
      };

      ctrl.uploader.onCompleteAll = function () {
        // the attempt to upload all files in the queue has completed
        var dir = RefilerModel.getDirByDisplayPath(ctrl.model.dirDisplayPath);

        // update the model
        dir.fileCount += uploadedFiles.length;

        var alerts = [];
        var dirLink = dir.formatLink({'class': 'alert-link'});

        // format an alert for all uploaded files
        if (uploadedFiles.length > 0) {
          alerts.push({
            'class': 'alert-success',
            'message':
              'Uploaded ' + uploadedFiles.length + ' files to ' + dirLink +
                ':<br>' +
              _.map(uploadedFiles, function (file) {
                file = new RefilerFile(file);
                return file.formatLink({
                  'target': '_blank'
                });
              }).join('<br>')
          });
        }

        // format an alert for all files that failed to be uploaded
        if (failedFiles.length > 0) {
          alerts.push({
            'message':
              'Failed to upload ' + failedFiles.length + ' files to ' +
                dirLink + ':<br>' +
              _.map(failedFiles, function (file) {
                return file.name + ': ' + file.error;
              }).join('<br>')
          });
        }

        // close the modal and display the alerts in the gallery
        ctrl.close(alerts);
      };

      // file size formatting function
      ctrl.formatSize = RefilerFile.formatSize;
    },
    'submit': function (ctrl) {
      if (ctrl.uploader.queue.length === 0) {
        ctrl.disabled = false;
      } else {
        // add dir and tag data for each file
        _.each(ctrl.uploader.queue, function (item) {
          // item.formData is an array of plain objects, not a FormData object
          item.formData.push({
            'dirId': RefilerModel.getDirByDisplayPath(ctrl.model.dirDisplayPath)
              .id,
            'tagNames': JSON.stringify(ctrl.model.tagNames)
          });
        });

        // upload all files; the event handlers were bound in .open above
        ctrl.uploader.uploadAll();
      }
    }
  };

  modals.curlFiles = {
    'title': 'Upload files by URLs',
    'buttonText': 'Upload',
    'class': 'modal-wide',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<dir-input ng-model="modal.model.dirDisplayPath"></dir-input>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="modal.model.tagNames"></tags-input>'
      },
      {
        'label': 'URLs',
        'control': '<textarea id="curl-files-urls"' +
          ' ng-model="modal.model.urls" class="form-control"' +
          ' focus-on="focus"></textarea>'
      }
    ],
    'open': function (ctrl) {
      // init
      ctrl.model.dir = {};

      if (RefilerGalleryModel.type === 'dir') {
        // if the user is currently viewing a dir, default to that dir for
        // upload
        ctrl.model.dirDisplayPath = RefilerGalleryModel.dir.displayPath;
      } else if (RefilerGalleryModel.type === 'tag') {
        // likewise for a tag
        ctrl.model.tagNames = [RefilerGalleryModel.tag.name];
      }

      // focus the textarea
      $timeout(function () {
        ctrl.broadcast('focus');
      });
    },
    'submit': function (ctrl) {
      var dir = RefilerModel.getDirByDisplayPath(ctrl.model.dirDisplayPath);

      RefilerAPI.curl({
        'dirId': dir.id,
        'tagNames': ctrl.model.tagNames,
        'urls': ctrl.model.urls
      }).then(function (data) {
        // add to the gallery model any curled files that belong in it
        if (RefilerGalleryModel.type === 'dir') {
          RefilerGalleryModel.addFiles(_.where(data.files, {
            'dirPath': RefilerGalleryModel.dir.path
          }));
        }

        // update the dir model
        dir.fileCount += data.files.length;

        // update the tags model
        RefilerModel.mergeTags(data.tags);

        var alerts = [];
        var dirLink = dir.formatLink({
          'class': 'alert-link'
        });

        // format an alert for all curled files
        if (data.files.length > 0) {
          alerts.push({
            'class': 'alert-success',
            'message':
              'Uploaded ' + data.files.length + ' files to ' + dirLink +
                ':<br>' +
              _.map(data.files, function (file) {
                file = new RefilerFile(file);
                return file.formatLink({
                  'target': '_blank'
                });
              }).join('<br>')
          });
        }

        // format an alert for all urls that failed to be curled
        if (data.failedUrls.length > 0) {
          alerts.push({
            'message':
              'Failed to upload ' + data.failedUrls.length + ' files to ' +
                dirLink + ' :<br>' +
              data.failedUrls.join('<br>')
          });
        }

        // close the modal and display the alerts in the gallery
        ctrl.close(alerts);
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.editTag = {
    'title': 'Edit tag',
    'buttonText': 'Save',
    'formGroups': [
      {
        'label': 'Name',
        'control': '<input type="text" ng-model="modal.model.name"' +
          ' class="form-control" focus-on="focus">'
      },
      {
        'label': 'URL',
        'control': '<input type="text" ng-model="modal.model.url"' +
          ' class="form-control">'
      },
      {
        'label': 'Caption',
        'control': '<textarea ng-model="modal.model.caption"' +
          ' class="form-control"></textarea>'
      },
      {
        'label': 'Parent tags',
        'control': '<tags-input ng-model="modal.model.parentNames">' +
          '</tags-input>'
      },
      {
        'label': 'Child tags',
        'control': '<tags-input ng-model="modal.model.childNames"></tags-input>'
      }
    ],
    'open': function (ctrl) {
      var tag = RefilerGalleryModel.tag;

      ctrl.model.name = tag.name;
      ctrl.model.url = tag.url;
      ctrl.model.caption = tag.caption;
      ctrl.model.parentNames = _.pluck(tag.parents, 'name');
      ctrl.model.childNames = _.pluck(tag.children, 'name');

      // focus the name input
      $timeout(function () {
        ctrl.broadcast('focus');
      });
    },
    'submit': function (ctrl) {
      RefilerAPI.editTag({
        'id': RefilerGalleryModel.tag.id,
        'name': ctrl.model.name,
        'url': ctrl.model.url,
        'caption': ctrl.model.caption,
        'parentNames': ctrl.model.parentNames,
        'childNames': ctrl.model.childNames
      }).then(function (data) {
        ctrl.close();

        if (data.tag.url !== RefilerGalleryModel.tag.url) {
          // if the url has changed, route to the new url
          $location.path('/tag/' + data.tag.url);
        } else {
          // update both models
          RefilerModel.page.title = data.tag.name;
          RefilerModel.updateTag(data.tag);
          // no need to trigger RefilerGalleryModelChange, because the files
          // haven't changed
          RefilerGalleryModel.tag = data.tag;
        }

        // update the tags model
        RefilerModel.mergeTags(data.tags);
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.deleteTag = {
    'title': 'Delete tag',
    'buttonText': 'Delete',
    'formGroups': [
      {
        'label': 'Name',
        'control': '<input type="text" value="{{modal.name}}"' +
          ' class="form-control" disabled>'
      }
    ],
    'open': function (ctrl) {
      ctrl.name = RefilerGalleryModel.tag.name;

      // focus the button
      $timeout(function () {
        ctrl.broadcast('focusButton');
      });
    },
    'submit': function (ctrl) {
      var id = RefilerGalleryModel.tag.id;

      RefilerAPI.deleteTag(id).then(function () {
        ctrl.close();

        // update the model
        RefilerModel.removeTag(id);

        $location.path('/');
      });
    }
  };

  modals.createDir = {
    'title': 'Create folder',
    'buttonText': 'Create',
    'formGroups': [
      {
        'label': 'Parent',
        'control': '<dir-input ng-model="modal.model.parentDisplayPath"></dir-input>'
      },
      {
        'label': 'Name',
        'control': '<input type="text" ng-model="modal.model.name"' +
          ' class="form-control" focus-on="focus">'
      }
    ],
    'open': function (ctrl) {
      ctrl.model.parentDisplayPath = RefilerGalleryModel.dir.displayPath;
      ctrl.model.name = '';

      // focus the name input
      $timeout(function () {
        ctrl.broadcast('focus');
      });
    },
    'submit': function (ctrl) {
      var path = RefilerModel.getDirByDisplayPath(ctrl.model.parentDisplayPath)
        .path + '/' + ctrl.model.name;

      RefilerAPI.createDir(path).then(function (data) {
        ctrl.close();

        // update the model
        RefilerModel.addDir(data.dir);

        // route to the new dir
        $location.path('/dir/' + data.dir.path);
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.moveDir = {
    'title': 'Move folder',
    'buttonText': 'Move',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<input type="text" value="{{modal.displayPath}}"' +
          ' class="form-control" disabled>'
      },
      {
        'label': 'New parent',
        'control': '<dir-input ng-model="modal.model.parentDisplayPath"></dir-input>'
      },
      {
        'label': 'New name',
        'control': '<input type="text" ng-model="modal.model.name"' +
            ' class="form-control" focus-on="focus">' +
          '<span class="help-block">Slashes are allowed.</span>'
      }
    ],
    'open': function (ctrl) {
      // old path for display only
      ctrl.displayPath = RefilerGalleryModel.dir.displayPath;

      // model to set the new path
      // TODO: handle unexpected dir not found error here
      // TODO: maybe write RefilerDir.getParent() instead
      ctrl.model.parentDisplayPath = RefilerModel.getDirByPath(
        RefilerGalleryModel.dir.getParentPath()
      ).displayPath;

      ctrl.model.name = RefilerGalleryModel.dir.getName();

      // focus the name input
      $timeout(function () {
        ctrl.broadcast('focus');
      });
    },
    'submit': function (ctrl) {
      var newPath = RefilerModel.getDirByDisplayPath(
        ctrl.model.parentDisplayPath
      ).path + '/' + ctrl.model.name;

      if (RefilerGalleryModel.dir.path === newPath) {
        ctrl.alerts.push({'message': 'No change.'});
      } else {
        RefilerAPI.moveDir({
          'id': RefilerGalleryModel.dir.id,
          'path': newPath
        }).then(function (data) {
          ctrl.close();

          // update the model
          RefilerModel.getDir(data.dir.id).setPath(data.dir.path);
          RefilerModel.sortDirs();

          // route to the new dir path
          $location.path('/dir/' + data.dir.path);
        }, ctrl.$httpErrorHandler);
      }
    }
  };

  modals.deleteDir = {
    'title': 'Delete folder',
    'buttonText': 'Delete',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<input type="text" value="{{modal.displayPath}}"' +
          ' class="form-control" disabled>'
      }
    ],
    'open': function (ctrl) {
      ctrl.displayPath = RefilerGalleryModel.dir.displayPath;

      // focus the button
      $timeout(function () {
        ctrl.broadcast('focusButton');
      });
    },
    'submit': function (ctrl) {
      var id = RefilerGalleryModel.dir.id;

      RefilerAPI.deleteDir(id).then(function () {
        ctrl.close();

        // update the model
        RefilerModel.removeDir(id);

        $location.path('/');
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.tagFilesByDir = {
    'title': 'Tag all files in folder',
    'buttonText': 'Tag',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<input type="text" value="{{modal.dirDisplayPath}}"' +
          ' class="form-control" disabled>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="modal.model.tagNames"></tags-input>'
      },
      {
        'label': 'Recursive',
        'control': '<input type="checkbox" ng-model="modal.model.recursive"> ' +
          'Include files in all subfolders'
      },
      {
        'label': 'Overwrite',
        'control': '<input type="checkbox" ng-model="modal.model.overwrite"> ' +
          'Replace existing tags'
      }
    ],
    'open': function (ctrl) {
      ctrl.dirDisplayPath = RefilerGalleryModel.dir.displayPath;
      ctrl.model.recursive = false;
      ctrl.model.overwrite = false;
    },
    'submit': function (ctrl) {
      RefilerAPI.tagFilesByDir({
        'dirId': RefilerGalleryModel.dir.id,
        'tagNames': ctrl.model.tagNames,
        'recursive': ctrl.model.recursive ? 1 : 0,
        'overwrite': ctrl.model.overwrite ? 1 : 0
      }).then(function (data) {
        ctrl.close();

        // update the tags model
        RefilerModel.mergeTags(data.tags);
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.tagSelectedFiles = {
    'title': 'Tag selected files',
    'buttonText': 'Tag',
    'formGroups': [
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="modal.model.tagNames"></tags-input>'
      },
      {
        'label': 'Overwrite',
        'control': '<input type="checkbox" ng-model="modal.model.overwrite"> ' +
          'Replace existing tags'
      }
    ],
    'submit': function (ctrl) {
      var fileIds = RefilerGalleryModel.getSelectedFileIds();

      RefilerAPI.tagFilesByIds({
        'fileIds': fileIds,
        'tagNames': ctrl.model.tagNames,
        'overwrite': ctrl.model.overwrite ? 1 : 0
      }).then(function (data) {
        ctrl.close();

        // update the tags model
        RefilerModel.mergeTags(data.tags);
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.moveSelectedFiles = {
    'title': 'Move selected files',
    'buttonText': 'Move',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<dir-input ng-model="modal.model.dirDisplayPath"></dir-input>'
      }
    ],
    'open': function (ctrl) {
      // init
      if (RefilerGalleryModel.type === 'dir') {
        ctrl.model.dirDisplayPath = RefilerGalleryModel.dir.displayPath;
      }
    },
    'submit': function (ctrl) {
      var fileIds = RefilerGalleryModel.getSelectedFileIds();

      RefilerAPI.moveFilesByIds({
        'fileIds': fileIds,
        'dirId': RefilerModel.getDirByDisplayPath(ctrl.model.dirDisplayPath).id
      }).then(function () {
        ctrl.close();

        $route.reload();
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.deleteSelectedFiles = {
    'title': 'Delete selected files',
    'buttonText': 'Delete',
    'formGroups': [],
    'open': function (ctrl) {
      // focus the button
      $timeout(function () {
        ctrl.broadcast('focusButton');
      });
    },
    'submit': function (ctrl) {
      var ids = RefilerGalleryModel.getSelectedFileIds();

      RefilerAPI.deleteFilesByIds(ids).then(function () {
        RefilerGalleryModel.removeFiles(ids);

        ctrl.close();
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.manageUsers = {
    'title': 'Manage users',
    'buttonText': 'Update',
    'class': 'modal-wide',
    'formGroups': [
      {
        'label': 'Users',
        'control':
          '<table class="table table-condensed">' +
            '<thead>' +
              '<tr>' +
                '<th>Email</th>' +
                '<th ng-repeat="(key, permission) in' +
                    ' modal.users[0].permissions">' +
                  '{{key}}' +
                '</th>' +
                '<th></th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>' +
              '<tr ng-repeat="user in modal.users"' +
                  // class for new user rows
                  ' ng-class="{\'warning\': !user.id}">' +
                '<td>' +
                  '<input type="text" ng-model="user.email"' +
                    ' class="form-control">' +
                  // activation link for a non-activated user
                  '<span ng-if="user.activationCode" class="help-block">' +
                    '<a href="#!/activate/{{user.activationCode}}">' +
                      'Activation link' +
                    '</a>' +
                  '</span>' +
                '</td>' +
                '<td ng-repeat="(key, permission) in user.permissions">' +
                  '<input type="checkbox" ng-model="user.permissions[key]">' +
                    //' ng-true-value="1" ng-false-value="-1">' +
                '</td>' +
                '<td>' +
                  // only existing users can be deleted
                  '<div ng-if="user.id">' +
                    '<button type="button" class="btn btn-xs btn-danger"' +
                        ' title="Delete" ng-click="modal.deleteUser(user)">' +
                      '<i class="fa fa-fw fa-trash-o"></i>' +
                    '</button>' +
                  '</div>' +
                '</td>' +
              '</tr>' +
            '</tbody>' +
            '<tfoot>' +
              '<tr>' +
                '<td colspan="5">' +
                  '<a ng-click="modal.createUser()">Create new user</a>' +
                '</td>' +
              '</tr>' +
            '</tfoot>' +
          '</table>' +
          '<span class="help-block">Note: Deletes apply instantly.</span>'
      }
    ],
    'open': function (ctrl) {
      ctrl.disabled = true;
      RefilerAPI.getUsers().then(function (data) {
        ctrl.users = data.users;
        ctrl.disabled = false;
      });

      ctrl.createUser = function () {
        ctrl.users.push({
          'email': '',
          'permissions': _.clone(Auth.guestPermissions)
        });
      };

      ctrl.deleteUser = function (user) {
        ctrl.disabled = true;
        RefilerAPI.deleteUser(user.id).then(function () {
          _.remove(ctrl.users, {'id': user.id});
          ctrl.disabled = false;
        }, ctrl.$httpErrorHandler);
      };
    },
    'submit': function (ctrl) {
      ctrl.disabled = true;
      RefilerAPI.editUsers(ctrl.users).then(function (data) {
        ctrl.users = data.users;
        ctrl.disabled = false;
      }, ctrl.$httpErrorHandler);
    }
  };

  modals.deleteThumbs = {
    'title': 'Delete all thumbnails',
    'buttonText': 'Delete',
    'formGroups': [],
    'submit': function (ctrl) {
      RefilerAPI.deleteThumbs.then(function () {
        ctrl.close();
      }, ctrl.$httpErrorHandler);
    }
  };
});
