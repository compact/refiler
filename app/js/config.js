angular.module('app').config(function ($httpProvider) {
  // POST data fix from http://victorblog.com/2012/12/20/make-angularjs-http-service-behave-like-jquery-ajax/

  // Use x-www-form-urlencoded Content-Type
  $httpProvider.defaults.headers.post['Content-Type'] =
    'application/x-www-form-urlencoded;charset=utf-8';
 
  // Override $http service's default transformRequest
  $httpProvider.defaults.transformRequest = [function (data) {
    /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */
    var param = function (obj) {
      var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

      for (name in obj) {
        value = obj[name];

        if (value instanceof Array) {
          for (i = 0; i < value.length; ++i) {
            subValue = value[i];
            fullSubName = name + '[' + i + ']';
            innerObj = {};
            innerObj[fullSubName] = subValue;
            query += param(innerObj) + '&';
          }
        } else if (value instanceof Object) {
          for (subName in value) {
            subValue = value[subName];
            fullSubName = name + '[' + subName + ']';
            innerObj = {};
            innerObj[fullSubName] = subValue;
            query += param(innerObj) + '&';
          }
        } else if (value !== undefined && value !== null) {
          query += encodeURIComponent(name) + '=' + encodeURIComponent(value) +
            '&';
        }
      }

      return query.length ? query.substr(0, query.length - 1) : query;
    };

    return angular.isObject(data) && String(data) !== '[object File]' ?
      param(data) : data;
  }];



  // $http response interceptor for requests to get and post
  $httpProvider.interceptors.push(['$q', function($q) {
    return {
      'response': function (response) {
        if (response.config.url.match(/^api/)) {
          // these urls being intercepted all respond with a success boolean
          if (typeof response.data.success === 'boolean') {
            if (response.data.success) {
              return $q.when(response);
            } else if (typeof response.data.error === 'string') {
              console.warn('$http response error, case 1', response.data);
              return $q.reject(response);
            }
          }

          // mostly a case where an error gets output inadvertently as a string
          // (outside the intended JSON)
          console.warn('$http response error, case 2', response.data);
          return $q.reject(response);
        } else {
          // default behaviour for all other urls
          return $q.when(response);
        }
      },
      'responseError': function (rejection) {
        console.warn('$http response error, case 3', rejection);
        return $q.reject(rejection);
        // if (typeof rejection.status === 'number') {
        //   switch (rejection.status) {
        //   case 200:
        //     return $q.reject(rejection.data);
        //   case 404:
        //     return $q.reject('Not found.');
        //   case 500:
        //     return $q.reject('Internal server error.');
        //   }
        // }
        // return $q.reject('The response has no status code.');
      }
    };
  }]);
});



angular.module('app').config(function ($locationProvider) {
  // set the hashbang
  $locationProvider.html5Mode(false);
  $locationProvider.hashPrefix('!');
});



angular.module('app').config(function ($routeProvider) {
  // routes
  var dirRoute, loginRoute;

  dirRoute = {
    'templateUrl': 'gallery.html',
    'controller': 'GalleryCtrl',
    'resolve': {
      'data': ['$route', 'RefilerAPI', 'RefilerModel', 'RefilerGalleryModel',
          function ($route, RefilerAPI, RefilerModel, RefilerGalleryModel) {
        // use $route.current.params instead of $routeParams because the latter
        // gets updated only after the route resolves
        var path = $route.current.params.path || '.';

        // RefilerModel needs to be ready in order to get the dir id
        return RefilerModel.ready().then(function (model) {
          var dir = model.getDirByPath(path);

          if (dir === null) {
            throw 'Folder not found';
          }

          return RefilerAPI.getDir(dir.id).then(function (data) {
            RefilerGalleryModel.set({
              'dir': dir,
              'files': data.files
            });
          });
        });
      }]
    }
  };

  loginRoute = {
    'templateUrl': 'login.html',
    'controller': 'LoginCtrl'
  };

  $routeProvider.when('/tag/:url', {
    'templateUrl': 'gallery.html',
    'controller': 'GalleryCtrl',
    'resolve': {
      'data': ['$route', 'RefilerAPI', 'RefilerModel', 'RefilerGalleryModel',
          function ($route, RefilerAPI, RefilerModel, RefilerGalleryModel) {
        var url = $route.current.params.url;

        // RefilerModel needs to be ready in order to get the tag id
        return RefilerModel.ready().then(function (model) {
          var tag = model.getTagByUrl(url);

          if (tag === null) {
            throw 'Tag not found';
          }

          return RefilerAPI.getTag(tag.id).then(function (data) {
            // in case the tag is new, and its data is not yet known
            RefilerModel.updateTag(data.tag);

            RefilerGalleryModel.set(data);
          });
        });
      }]
    }
  });
  $routeProvider.when('/dir/:path*', dirRoute);
  $routeProvider.when('/dir/', dirRoute);
  $routeProvider.when('/nav', {
    'templateUrl': 'nav.html',
    'resolve': {
      'data': ['RefilerModel', function (RefilerModel) {
        RefilerModel.page.title = 'Navigation';
      }]
    }
  });
  $routeProvider.when('/login', loginRoute);
  $routeProvider.when('/activate/:activationCode', loginRoute);
  $routeProvider.when('/', {
    'templateUrl': 'gallery.html',
    'controller': 'GalleryCtrl',
    'resolve': {
      'data': ['RefilerGalleryModel', 'RefilerModel', function (
          RefilerGalleryModel, RefilerModel) {
        RefilerModel.page.title = 'Home';
        RefilerGalleryModel.set({});
      }]
    }
  });
});



angular.module('app').config(function (cfpLoadingBarProvider) {
  // loading bar: disable the spinner
  cfpLoadingBarProvider.includeSpinner = false;
});



angular.module('app').config(function (AuthProvider) {
  AuthProvider.guestPermissions = {
    'view': false,
    'edit': false,
    'admin': false
  };
});



angular.module('app').config(function (LightboxProvider) {
  LightboxProvider.templateUrl = 'lightbox.html';

  // see the default method in the Lightbox provider
  LightboxProvider.calculateImageDimensionLimits = function (dimensions) {
    return {
      'maxWidth': dimensions.windowWidth - 102,
      // 156px = 102px as above
      //         + 22px height of .lightbox-nav
      //         + 8px margin-top of .lightbox-image-details
      //         + 24px height of .lightbox-image-details
      //         + 8px margin-top of .lightbox-image-container
      'maxHeight': dimensions.windowHeight - 164
    };
  };

  // see the default method in the Lightbox provider
  LightboxProvider.calculateModalDimensions = function (dimensions) {
    var width = Math.max(400, dimensions.imageDisplayWidth + 42);

    // 104px = 42px as above
    //         + 22px height of .lightbox-nav
    //         + 8px margin-top of .lightbox-image-details
    //         + 24px height of .lightbox-image-details
    //         + 8px margin-top of .lightbox-image-container
    var height = Math.max(200, dimensions.imageDisplayHeight + 104);

    if (width >= dimensions.windowWidth - 20 || dimensions.windowWidth < 768) {
      width = 'auto';
    }

    // the modal height cannot be larger than the window height
    if (height >= dimensions.windowHeight) {
      height = 'auto';
    }

    return {
      'width': width,
      'height': height
    };
  };
});



angular.module('app').config(function (RefilerProvider) {
  RefilerProvider.config.defaultParentlessTagsInNav = true;
  RefilerProvider.config.defaultParentlessDirsInNav = true;
  RefilerProvider.config.highlightSearchText = false;
});
