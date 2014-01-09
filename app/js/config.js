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



  // $http response interceptor for requests to get/* and post/*
  $httpProvider.interceptors.push(['$q', function($q) {
    return {
      'response': function (response) {
        if (response.config.url.match(/^(get|post)/)) {
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
    'templateUrl': 'partials/gallery.html',
    'controller': 'GalleryCtrl',
    'resolve': {
      'data': ['$http', '$route', 'RefilerGalleryModel', function ($http,
          $route, RefilerGalleryModel) {
        // we use $route.current.params instead of $routeParams because the
        // latter gets updated only after the route resolves
        return $http.get('get/get-files-by-dir.php', {
          'params': {
            'path': $route.current.params.dir || '.'
          }
        }).success(function (data) {
          RefilerGalleryModel.set(data);
        });
      }]
    }
  };

  loginRoute = {
    'templateUrl': 'partials/login.html',
    'controller': 'LoginCtrl'
  };

  $routeProvider.when('/tag/:tag', {
    'templateUrl': 'partials/gallery.html',
    'controller': 'GalleryCtrl',
    'resolve': {
      'data': ['$http', '$route', 'RefilerGalleryModel', function ($http,
          $route, RefilerGalleryModel) {
        return $http.get('get/get-files-by-tag.php', {
          'params': {
            'url': $route.current.params.tag
          }
        }).success(function (data) {
          RefilerGalleryModel.set(data);
        });
      }]
    }
  });
  $routeProvider.when('/dir/:dir*', dirRoute);
  $routeProvider.when('/dir/', dirRoute);
  $routeProvider.when('/login', loginRoute);
  $routeProvider.when('/activate/:activationCode', loginRoute);
  $routeProvider.when('/', {
    'templateUrl': 'partials/gallery.html',
    'controller': 'GalleryCtrl',
    'resolve': {
      'data': ['Refiler', 'RefilerGalleryModel', function (Refiler,
          RefilerGalleryModel) {
        Refiler.page.title = 'Home';
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
  AuthProvider.defaultPermissions = {
    'view': false,
    'edit': false,
    'admin': false
  };
});



angular.module('app').config(function (RefilerProvider) {
  RefilerProvider.config.defaultParentlessTagsInNav = true;
  RefilerProvider.config.defaultParentlessDirsInNav = true;
});