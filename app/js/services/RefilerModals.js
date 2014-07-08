/**
 * The modals defined here are opened in GalleryCtrl, its descendant MenuCtrl,
 *   and in the lightbox. 
 */
angular.module('app').service('RefilerModals', function ($location, $route,
    $timeout, $modal, $fileUploader, Auth, RefilerAPI, RefilerDir, RefilerFile,
    RefilerGalleryModel, RefilerModel) {
  var modals = {};



  /**
   * Open the modal with the given key.
   * @param {string} key
   * @param {*}      [data] Passed directly into the modal's open and submit
   *                        event handlers.
   */
  this.open = function (key, data) {
    var modal = modals[key]; // modal-specific data

    return $modal.open({
      'templateUrl': 'modal.html',
      'controller': ['$scope', function ($scope) {
        // modal scope, a child of $rootScope
        $scope.model = {}; // populated by child form elements with ng-model
        $scope.alerts = [];

        if (typeof modal.open === 'function') {
          // optional custom handler for the modal open event; we could call
          // this in the promise provided by ui.bootstrap.modal called
          // 'opened' instead, but that resolves after this controller
          modal.open($scope, data);
        }

        // bind the custom modal properties to the modal scope
        $scope.modal = {
          'title': modal.title,
          'buttonText': modal.buttonText,
          'class': modal.class,
          'formGroups': modal.formGroups
        };

        // bind the custom submit handler
        $scope.submit = function () {
          // disable the modal while submitting
          $scope.disabled = true;

          // submit
          modal.submit($scope, data);
        };

        // not called in the template directly, but used in the modal
        // definitions below as a shortcut
        $scope.$httpErrorHandler = function (data) {
          $scope.alerts.push({
            'message': data.error || 'Error.'
          });
          $scope.disabled = false;
        };
      }],
      'windowClass': modal.class
    });
  };



  // modals opened from the gallery
  modals.editFile = {
    'title': 'Edit file',
    'buttonText': 'Save',
    'formGroups': [
      {
        'label': 'File',
        'control':
          '<input type="text" value="{{path}}" class="form-control" disabled>'
      },
      {
        'label': 'New folder',
        'control': '<dir-input ng-model="model.dir"></dir-input>'
      },
      {
        'label': 'New name',
        'control':
          '<input type="text" ng-model="model.name" class="form-control">'
      },
      {
        'label': 'Caption',
        'control':
          '<textarea ng-model="model.caption" class="form-control"></textarea>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="model.tagNames"></tags-input>'
      },
      {
        'label': 'Thumbnail',
        'control': '<file-thumb file="file"></file-thumb><br>' +
          '<a ng-if="file.isImage()" ng-click="updateThumb()">Regenerate</a>'
      }
    ],
    'open': function (scope, file) {
      scope.disabled = true;

      scope.path = file.getPath();

      RefilerAPI.getTagsByFile(file.id).then(function (data) {
        scope.model.tagNames = _.pluck(data.tags, 'name');
        scope.disabled = false;
      });

      scope.model.caption = file.caption;
      scope.model.dir = {
        'id': file.id,
        'text': '/' + file.dirPath
      };
      scope.model.name = file.name;

      // used for showing the thumb
      scope.file = file;

      scope.updateThumb = function () {
        RefilerAPI.updateFileThumb(file.id).then(function (data) {
          // update the model
          RefilerGalleryModel.updateFile(data.file);

          // add alert (the modal isn't closed)
          scope.alerts.push({
            'class': 'alert-success',
            'message': 'Thumbnail updated.'
          });
        });
      };
    },
    'submit': function (scope, file) {
      var data, newDirPath, newName;

      data = {
        'id': file.id,
        'tagNames': scope.model.tagNames,
        'caption': scope.model.caption
      };

      // idiosyncratic: only POST the dir path and name if they have changed
      newDirPath = scope.model.dir.text.substr(1); // remove leading slash
      newName = scope.model.name;
      if (file.dirPath !== newDirPath || file.name !== newName) {
        data.dirPath = newDirPath;
        data.name = newName;
      }

      RefilerAPI.editFile(data).then(function (data) {
        scope.$close();

        // update the gallery
        if (RefilerGalleryModel.type === 'dir' &&
            data.file.dirPath !== RefilerGalleryModel.dir.path) {
          RefilerGalleryModel.removeFile(data.file.id);
        } else { // TODO for tags: remove the file if it has been untagged
          RefilerGalleryModel.updateFile(data.file);
        }

        // add any new tags to the model
        RefilerModel.addTagNames(scope.model.tagNames);
      }, scope.$httpErrorHandler);
    }
  };

  modals.deleteFile = {
    'title': 'Delete file',
    'buttonText': 'Delete',
    'formGroups': [
      {
        'label': 'File',
        'control':
          '<input type="text" value="{{name}}" class="form-control" disabled>'
      }
    ],
    'open': function (scope, file) {
      scope.name = file.name;

      // focus the submit button
      $timeout(function () {
        // wait for the next digest because the focus-on directive needs to be
        // linked first
        scope.$broadcast('focusButton');
      });
    },
    'submit': function (scope, file) {
      RefilerAPI.deleteFile(file.id).then(function () {
        RefilerGalleryModel.removeFile(file.id);

        scope.$close();
      }, scope.$httpErrorHandler);
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
        'control': '<dir-input ng-model="model.dir"></dir-input>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="model.tagNames"></tags-input>'
      },
      {
        'label': 'Files',
        'control':
          // file drop zone
          '<div ng-file-drop ng-file-over="alert-info"' +
              ' ng-if="uploader.hasHTML5" class="alert alert-warning">' +
            'Drag and drop files here, or use the input.<br><br>' +
            // file input
            '<input ng-file-select type="file" multiple>' +
          '</div>' +
          // table showing queued files
          '<table class="table" ng-show="uploader.queue.length > 0">' +
            '<thead>' +
              '<tr>' +
                '<th>Filename</th>' +
                '<th ng-if="uploader.hasHTML5">Size</th>' +
                '<th ng-if="uploader.hasHTML5">Progress</th>' +
                '<th></th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>' +
              // item row
              '<tr ng-repeat="(key, item) in uploader.queue">' +
                // file name
                '<td>' +
                  '<input type="text" ng-model="item.formData[0].name"' +
                      'class="form-control">' +
                '</td>' +
                // file size
                '<td ng-if="uploader.hasHTML5" style="white-space: nowrap;">' +
                  '{{formatSize(item.file.size)}}' +
                '</td>' +
                '<td ng-if="uploader.hasHTML5">' +
                  '<div class="progress" ng-class="{' +
                    "'progress-striped active': !item.isSuccess" +
                  '}">' +
                    '<div role="progressbar" class="progress-bar" ng-class="{' +
                      "'progress-bar-success': item.isSuccess" +
                    '}" ng-style="{' +
                      "'width': item.progress + '%'" +
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
    'open': function (scope) {
      // arrays of RefilerFile objects used to generate alerts in the
      // completeall event below
      var uploadedFiles = [], failedFiles = [];

      // init
      scope.model.dir = {
        'id': 0,
        'text': ''
      };

      if (RefilerGalleryModel.type === 'dir') {
        // if the user is currently viewing a dir, default to that dir for
        // upload
        scope.model.dir = {
          'id': RefilerGalleryModel.dir.id,
          'text': RefilerGalleryModel.dir.displayPath
        };
      } else if (RefilerGalleryModel.type === 'tag') {
        // likewise for a tag
        scope.model.tagNames = [RefilerGalleryModel.tag.name];
      }

      // $fileUploader configuration. $fileUploader uses an XMLHttpRequest
      // object rather than $http for requests, so our $http configurations do
      // not apply. I decided to handle requests and responses manually in a
      // different way than the rest of the app rather than try to integrate
      // usage of $http.
      scope.uploader = $fileUploader.create({
        'scope': scope,
        'url': RefilerAPI.uploadFileUrl,
         // purely for visual consistency so the queue doesn't disappear row by
         // row; we close the modal after all files have uploaded
        'removeAfterUpload': false
      });

      scope.uploader.bind('afteraddingfile', function (event, item) {
        item.formData.push({
          'name': item.file.name // used as ng-model to rename the file
        });
      });

      scope.uploader.bind('success', function (event, xhr, item, data) {
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
      });

      scope.uploader.bind('completeall', function () {
        // the attempt to upload all files in the queue has completed
        var dir = RefilerModel.getDir(scope.model.dir.id);

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
        scope.$close(alerts);
      });

      // file size formatting function
      scope.formatSize = RefilerFile.formatSize;
    },
    'submit': function (scope) {
      if (scope.uploader.queue.length === 0) {
        scope.disabled = false;
      } else {
        // add dir and tag data for each file
        _.each(scope.uploader.queue, function (item) {
          // item.formData is an array of plain objects, not a FormData object
          item.formData.push({
            'dirId': scope.model.dir.id,
            'tagNames': JSON.stringify(scope.model.tagNames)
          });
        });

        // upload all files; the event handlers were bound in .open above
        scope.uploader.uploadAll();

        // add any new tags to the model; these tags are added even if no files
        // were successfully uploaded
        RefilerModel.addTagNames(scope.model.tagNames);
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
        'control': '<dir-input ng-model="model.dir"></dir-input>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="model.tagNames"></tags-input>'
      },
      {
        'label': 'URLs',
        'control':
          '<textarea id="curl-files-urls" ng-model="model.urls"' +
            ' class="form-control" focus-on="focus"></textarea>'
      }
    ],
    'open': function (scope) {
      // init
      scope.model.dir = {
        'id': 0,
        'text': ''
      };

      if (RefilerGalleryModel.type === 'dir') {
        // if the user is currently viewing a dir, default to that dir for
        // upload
        scope.model.dir = {
          'id': RefilerGalleryModel.dir.id,
          'text': RefilerGalleryModel.dir.displayPath
        };
      } else if (RefilerGalleryModel.type === 'tag') {
        // likewise for a tag
        scope.model.tagNames = [RefilerGalleryModel.tag.name];
      }

      // focus the textarea
      $timeout(function () {
        scope.$broadcast('focus');
      });
    },
    'submit': function (scope) {
      RefilerAPI.curl({
        'dirId': scope.model.dir.id,
        'tagNames': scope.model.tagNames,
        'urls': scope.model.urls
      }).then(function (data) {
        // add to the gallery model any curled files that belong in it
        if (RefilerGalleryModel.type === 'dir') {
          RefilerGalleryModel.addFiles(_.where(data.files, {
            'dirPath': RefilerGalleryModel.dir.path
          }));
        }

        var dir = RefilerModel.getDir(scope.model.dir.id);

        // update the dir model
        dir.fileCount += data.files.length;

        // add any new tags to the model
        RefilerModel.addTagNames(scope.model.tagNames);

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
        scope.$close(alerts);
      }, scope.$httpErrorHandler);
    }
  };

  modals.editTag = {
    'title': 'Edit tag',
    'buttonText': 'Save',
    'formGroups': [
      {
        'label': 'Name',
        'control':
          '<input type="text" ng-model="model.name" class="form-control"' +
            ' focus-on="focus">'
      },
      {
        'label': 'URL',
        'control':
          '<input type="text" ng-model="model.url" class="form-control">'
      },
      {
        'label': 'Caption',
        'control':
          '<textarea ng-model="model.caption" class="form-control"></textarea>'
      },
      {
        'label': 'Parent tags',
        'control': '<tags-input ng-model="model.parentNames"></tags-input>'
      },
      {
        'label': 'Child tags',
        'control': '<tags-input ng-model="model.childNames"></tags-input>'
      }
    ],
    'open': function (scope) {
      var tag = RefilerGalleryModel.tag;

      scope.model.name = tag.name;
      scope.model.url = tag.url;
      scope.model.caption = tag.caption;
      scope.model.parentNames = _.pluck(tag.parents, 'name');
      scope.model.childNames = _.pluck(tag.children, 'name');

      // focus the name input
      $timeout(function () {
        scope.$broadcast('focus');
      });
    },
    'submit': function (scope) {
      RefilerAPI.editTag({
        'id': RefilerGalleryModel.tag.id,
        'name': scope.model.name,
        'url': scope.model.url,
        'caption': scope.model.caption,
        'parentNames': scope.model.parentNames,
        'childNames': scope.model.childNames
      }).then(function (data) {
        scope.$close();

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

        // add any new tags to the model
        RefilerModel.addTagNames(
          scope.model.parentNames.concat(scope.model.childNames)
        );
      }, scope.$httpErrorHandler);
    }
  };

  modals.deleteTag = {
    'title': 'Delete tag',
    'buttonText': 'Delete',
    'formGroups': [
      {
        'label': 'Name',
        'control':
          '<input type="text" value="{{name}}" class="form-control" disabled>'
      }
    ],
    'open': function (scope) {
      scope.name = RefilerGalleryModel.tag.name;

      // focus the button
      $timeout(function () {
        scope.$broadcast('focusButton');
      });
    },
    'submit': function (scope) {
      var id = RefilerGalleryModel.tag.id;

      RefilerAPI.deleteTag(id).then(function () {
        scope.$close();

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
        'control': '<dir-input ng-model="model.parent"></dir-input>'
      },
      {
        'label': 'Name',
        'control':
          '<input type="text" ng-model="model.name" class="form-control"' +
            ' focus-on="focus">'
      }
    ],
    'open': function (scope) {
      scope.model.parent = {
        'id': RefilerGalleryModel.dir.id,
        'text': RefilerGalleryModel.dir.displayPath
      };
      scope.model.name = '';

      // focus the name input
      $timeout(function () {
        scope.$broadcast('focus');
      });
    },
    'submit': function (scope) {
      var path = scope.model.parent.text.substr(1); // remove leading slash
      path += '/' + scope.model.name;

      RefilerAPI.createDir(path).then(function (data) {
        scope.$close();

        // update the model
        RefilerModel.addDir(data.dir);

        // route to the new dir
        $location.path('/dir/' + data.dir.path);
      }, scope.$httpErrorHandler);
    }
  };

  modals.moveDir = {
    'title': 'Move folder',
    'buttonText': 'Move',
    'formGroups': [
      {
        'label': 'Folder',
        'control':
          '<input type="text" value="{{path}}" class="form-control" disabled>'
      },
      {
        'label': 'New parent',
        'control': '<dir-input ng-model="model.parent"></dir-input>'
      },
      {
        'label': 'New name',
        'control':
          '<input type="text" ng-model="model.name" class="form-control"' +
            ' focus-on="focus">' +
          '<span class="help-block">Slashes are allowed.</span>'
      }
    ],
    'open': function (scope) {
      // old path for display only
      scope.path = RefilerGalleryModel.dir.displayPath;

      // model to set the new path
      var parentPath = RefilerGalleryModel.dir.getParentPath();
      scope.model.parent = {
        'id': _.where(RefilerModel.dirs, {
          'path': parentPath
        })[0].id, // TODO: turn this into a method of RefilerModel
        'text': '/' + parentPath
      };
      scope.model.name = RefilerGalleryModel.dir.getName();

      // focus the name input
      $timeout(function () {
        scope.$broadcast('focus');
      });
    },
    'submit': function (scope) {
      var newPath = scope.model.parent.text.substr(1) + '/' +
        scope.model.name;

      if (RefilerGalleryModel.dir.path === newPath) {
        scope.alerts.push({'message': 'No change.'});
      } else {
        RefilerAPI.moveDir({
          'id': RefilerGalleryModel.dir.id,
          'path': newPath
        }).then(function (data) {
          scope.$close();

          // update the model
          RefilerModel.getDir(data.dir.id).setPath(data.dir.path);
          RefilerModel.sortDirs();

          // route to the new dir path
          $location.path('/dir/' + data.dir.path);
        }, scope.$httpErrorHandler);
      }
    }
  };

  modals.deleteDir = {
    'title': 'Delete folder',
    'buttonText': 'Delete',
    'formGroups': [
      {
        'label': 'Folder',
        'control':
          '<input type="text" value="{{path}}" class="form-control" disabled>'
      }
    ],
    'open': function (scope) {
      scope.path = RefilerGalleryModel.dir.path;

      // focus the button
      $timeout(function () {
        scope.$broadcast('focusButton');
      });
    },
    'submit': function (scope) {
      var id = RefilerGalleryModel.dir.id;

      RefilerAPI.deleteDir(id).then(function () {
        scope.$close();

        // update the model
        RefilerModel.removeDir(id);

        $location.path('/');
      }, scope.$httpErrorHandler);
    }
  };

  modals.tagFilesByDir = {
    'title': 'Tag all files in folder',
    'buttonText': 'Tag',
    'formGroups': [
      {
        'label': 'Folder',
        'control':
          '<input type="text" value="{{dirPath}}" class="form-control"' +
            ' disabled>'
      },
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="model.tagNames"></tags-input>'
      },
      {
        'label': 'Recursive',
        'control':
          '<input type="checkbox" ng-model="model.recursive"> ' +
          'Include files in all subfolders'
      },
      {
        'label': 'Overwrite',
        'control':
          '<input type="checkbox" ng-model="model.overwrite"> ' +
          'Replace existing tags'
      }
    ],
    'open': function (scope) {
      scope.dirPath = RefilerGalleryModel.dir.path;
      scope.model.recursive = false;
      scope.model.overwrite = false;
    },
    'submit': function (scope) {
      RefilerAPI.tagFilesByDir({
        'dirId': RefilerGalleryModel.dir.id,
        'tagNames': scope.model.tagNames,
        'recursive': scope.model.recursive ? 1 : 0,
        'overwrite': scope.model.overwrite ? 1 : 0
      }).then(function () {
        scope.$close();

        // add any new tags to the model
        RefilerModel.addTagNames(scope.model.tagNames);
      }, scope.$httpErrorHandler);
    }
  };

  modals.tagSelectedFiles = {
    'title': 'Tag selected files',
    'buttonText': 'Tag',
    'formGroups': [
      {
        'label': 'Tags',
        'control': '<tags-input ng-model="model.tagNames"></tags-input>'
      },
      {
        'label': 'Overwrite',
        'control':
          '<input type="checkbox" ng-model="model.overwrite"> ' +
          'Replace existing tags'
      }
    ],
    'submit': function (scope) {
      var fileIds = RefilerGalleryModel.getSelectedFileIds();

      RefilerAPI.tagFilesByIds({
        'fileIds': fileIds,
        'tagNames': scope.model.tagNames,
        'overwrite': scope.model.overwrite ? 1 : 0
      }).then(function () {
        scope.$close();

        // add any new tags to the model
        RefilerModel.addTagNames(scope.model.tagNames);
      }, scope.$httpErrorHandler);
    }
  };

  modals.moveSelectedFiles = {
    'title': 'Move selected files',
    'buttonText': 'Move',
    'formGroups': [
      {
        'label': 'Folder',
        'control': '<dir-input ng-model="model.dir"></dir-input>'
      }
    ],
    'open': function (scope) {
      // init
      scope.model.dir = {
        'id': 0,
        'text': ''
      };

      if (RefilerGalleryModel.type === 'dir') {
        scope.model.dir = {
          'id': RefilerGalleryModel.dir.id,
          'text': RefilerGalleryModel.dir.displayPath
        };
      }
    },
    'submit': function (scope) {
      var fileIds = RefilerGalleryModel.getSelectedFileIds();

      RefilerAPI.moveFilesByIds({
        'fileIds': fileIds,
        'dirId': scope.model.dir.id
      }).then(function () {
        scope.$close();

        $route.reload();
      }, scope.$httpErrorHandler);
    }
  };

  modals.deleteSelectedFiles = {
    'title': 'Delete selected files',
    'buttonText': 'Delete',
    'formGroups': [],
    'open': function (scope) {
      // focus the button
      $timeout(function () {
        scope.$broadcast('focusButton');
      });
    },
    'submit': function (scope) {
      var ids = RefilerGalleryModel.getSelectedFileIds();

      RefilerAPI.deleteFilesByIds(ids).then(function () {
        RefilerGalleryModel.removeFiles(ids);

        scope.$close();
      }, scope.$httpErrorHandler);
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
                '<th ng-repeat="(key, permission) in users[0].permissions">' +
                  '{{key}}' +
                '</th>' +
                '<th></th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>' +
              '<tr ng-repeat="user in users"' +
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
                    '<button class="btn btn-xs btn-danger" title="Delete"' +
                        ' ng-click="deleteUser(user)">' +
                      '<i class="fa fa-fw fa-trash-o"></i>' +
                    '</button>' +
                  '</div>' +
                '</td>' +
              '</tr>' +
            '</tbody>' +
            '<tfoot>' +
              '<tr>' +
                '<td colspan="5">' +
                  '<a ng-click="createUser()">Create new user</a>' +
                '</td>' +
              '</tr>' +
            '</tfoot>' +
          '</table>' +
          '<span class="help-block">Note: Deletes apply instantly.</span>'
      }
    ],
    'open': function (scope) {
      scope.disabled = true;
      RefilerAPI.getUsers().then(function (data) {
        scope.users = data.users;
        scope.disabled = false;
      });

      scope.createUser = function () {
        scope.users.push({
          'email': '',
          'permissions': _.clone(Auth.guestPermissions)
        });
      };

      scope.deleteUser = function (user) {
        scope.disabled = true;
        RefilerAPI.deleteUser(user.id).then(function () {
          _.remove(scope.users, {'id': user.id});
          scope.disabled = false;
        }, scope.$httpErrorHandler);
      };
    },
    'submit': function (scope) {
      scope.disabled = true;
      RefilerAPI.editUsers(scope.users).then(function (data) {
        scope.users = data.users;
        scope.disabled = false;
      }, scope.$httpErrorHandler);
    }
  };
});
