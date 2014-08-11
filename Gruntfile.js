// Generated on 2013-12-15 using generator-angular 0.6.0
'use strict';

// # Globbing
// for performance reasons we're only matching one level down:
// 'test/spec/{,*/}*.js'
// use this if you want to recursively match all subfolders:
// 'test/spec/**/*.js'

module.exports = function (grunt) {

  // Load grunt tasks automatically
  require('load-grunt-tasks')(grunt);

  // Time how long tasks take. Can help when optimizing build times
  require('time-grunt')(grunt);

  // Define the configuration for all the tasks
  grunt.initConfig({

    // configurable paths
    paths: {
      app: require('./bower.json').appPath || 'app',
      dist: 'dist',
      distStatic: 'dist-static',
      url: 'http://localhost'
    },

    // Watches files for changes and runs tasks based on the changed files
    watch: {
      js: {
        files: ['{.tmp,<%= paths.app %>}/js/{,*/}*.js'],
        tasks: ['newer:jshint:all']
      },
      jsTest: {
        files: ['test/spec/{,*/}*.js'],
        tasks: ['newer:jshint:test', 'karma']
      },
      styles: {
        files: ['<%= paths.app %>/css/{,*/}*.css'],
        tasks: ['newer:copy:styles', 'autoprefixer']
      },
      gruntfile: {
        files: ['Gruntfile.js']
      },
      livereload: {
        options: {
          livereload: '<%= connect.options.livereload %>'
        },
        files: [
          '<%= paths.app %>/{,*/}*.html',
          '.tmp/css/{,*/}*.css',
          '<%= paths.app %>/images/{,*/}*.{png,jpg,jpeg,gif,webp,svg}'
        ]
      },
      sass: {
        files: ['<%= paths.app %>/css/sass/*.{scss,sass}'],
        tasks: ['compass:compile']
      }
    },

    // The actual grunt server settings
    connect: {
      options: {
        port: 9000,
        // Change this to '0.0.0.0' to access the server from outside.
        hostname: 'localhost',
        livereload: 35729
      },
      livereload: {
        options: {
          open: true,
          base: [
            '.tmp',
            '<%= paths.app %>'
          ]
        }
      },
      test: {
        options: {
          port: 9001,
          base: [
            '.tmp',
            'test',
            '<%= paths.app %>'
          ]
        }
      },
      dist: {
        options: {
          base: '<%= paths.dist %>'
        }
      }
    },

    // Make sure code styles are up to par and there are no obvious mistakes
    jshint: {
      options: {
        jshintrc: '.jshintrc',
        reporter: require('jshint-stylish')
      },
      all: [
        'Gruntfile.js',
        '<%= paths.app %>/js/**/*.js'
      ],
      test: {
        options: {
          jshintrc: 'test/.jshintrc'
        },
        src: ['test/spec/{,*/}*.js']
      }
    },

    // Empties folders to start fresh
    clean: {
      dist: {
        files: [{
          dot: true,
          src: [
            '.tmp',
            '<%= paths.dist %>/*',
            '!<%= paths.dist %>/.git*'
          ]
        }]
      },
      distStatic: ['<%= paths.distStatic %>'],
      server: '.tmp'
    },

    // Add vendor prefixed styles
    autoprefixer: {
      options: {
        browsers: ['last 1 version']
      },
      dist: {
        files: [{
          expand: true,
          cwd: '.tmp/css/',
          src: '{,*/}*.css',
          dest: '.tmp/css/'
        }]
      }
    },

    

    

    // Renames files for browser caching purposes
    rev: {
      dist: {
        files: {
          src: [
            '<%= paths.dist %>/js/{,*/}*.js',
            '<%= paths.dist %>/css/{,*/}*.css',
            '<%= paths.dist %>/images/{,*/}*.{png,jpg,jpeg,gif,webp,svg}',
            '<%= paths.dist %>/css/fonts/*'
          ]
        }
      }
    },

    // Reads HTML for usemin blocks to enable smart builds that automatically
    // concat, minify and revision files. Creates configurations in memory so
    // additional tasks can operate on them
    useminPrepare: {
      html: '<%= paths.app %>/index.html',
      options: {
        dest: '<%= paths.dist %>'
      }
    },

    // Performs rewrites based on rev and the useminPrepare configuration
    usemin: {
      html: ['<%= paths.dist %>/{,*/}*.html'],
      css: ['<%= paths.dist %>/css/{,*/}*.css'],
      options: {
        assetsDirs: ['<%= paths.dist %>']
      }
    },

    // The following *-min tasks produce minified files in the dist folder
    imagemin: {
      dist: {
        files: [{
          expand: true,
          cwd: '<%= paths.app %>/images',
          src: '{,*/}*.{png,jpg,jpeg,gif}',
          dest: '<%= paths.dist %>/images'
        }]
      }
    },
    svgmin: {
      dist: {
        files: [{
          expand: true,
          cwd: '<%= paths.app %>/images',
          src: '{,*/}*.svg',
          dest: '<%= paths.dist %>/images'
        }]
      }
    },
    htmlmin: {
      dist: {
        options: {
          // Optional configurations that you can uncomment to use
          // removeCommentsFromCDATA: true,
          // collapseBooleanAttributes: true,
          // removeAttributeQuotes: true,
          // removeRedundantAttributes: true,
          // useShortDoctype: true,
          // removeEmptyAttributes: true,
          // removeOptionalTags: true*/
        },
        files: [{
          expand: true,
          cwd: '<%= paths.app %>',
          src: ['*.html', 'views/*.html'],
          dest: '<%= paths.dist %>'
        }]
      }
    },

    ngAnnotate: {
      dist: {
        files: [{
          expand: true,
          cwd: '.tmp/concat/js',
          src: '*.js',
          dest: '.tmp/concat/js'
        }]
      }
    },

    // Replace Google CDN references
    cdnify: {
      dist: {
        html: ['<%= paths.dist %>/*.html']
      }
    },

    // Copies remaining files to places other tasks can use
    copy: {
      dist: {
        files: [{
          expand: true,
          dot: true,
          cwd: '<%= paths.app %>',
          dest: '<%= paths.dist %>',
          src: [
            '**',
            '!bower_components/**',
            '!css/**',
            '!js/**',
            '!partials/**'
          ]
        }, {
          // Select2 files; the CSS file is built into lib/select2 with usemin
          // in index.html
          expand: true,
          cwd: '<%= paths.app %>/bower_components',
          dest: '<%= paths.dist %>/lib',
          src: [
            'select2/select2-spinner.gif',
            'select2/select2.js',
            'select2/select2.png',
            'select2/select2x2.png'
          ]
        }, {
          // optional remote version of config.php
          expand: true,
          cwd: '<%= paths.app %>',
          dest: '<%= paths.dist %>',
          src: [
            'config-remote.php'
          ],
          rename: function (dest) {
            return dest + '/config.php';
          }
        }, {
          expand: true,
          cwd: '.tmp/images',
          dest: '<%= paths.dist %>/images',
          src: [
            'generated/*'
          ]
        }]
      },
      distStatic: {
        expand: true,
        cwd: '<%= paths.dist %>',
        dest: '<%= paths.distStatic %>',
        src: [
          'css/**/*',
          'js/**/*',
          'index.html'
        ]
      },
      styles: {
        expand: true,
        cwd: '<%= paths.app %>/css',
        dest: '.tmp/css/',
        src: '{,*/}*.css'
      }
    },

    // Run some tasks in parallel to speed up the build process
    concurrent: {
      server: [
        'copy:styles'
      ],
      test: [
        'copy:styles'
      ],
      dist: [
        'copy:styles',
        'imagemin',
        'svgmin',
        'htmlmin'
      ]
    },

    // By default, your `index.html`'s <!-- Usemin block --> will take care of
    // minification. These next options are pre-configured if you do not wish
    // to use the Usemin blocks.
    // cssmin: {
    //   dist: {
    //     files: {
    //       '<%= paths.dist %>/css/main.css': [
    //         '.tmp/css/{,*/}*.css',
    //         '<%= paths.app %>/css/{,*/}*.css'
    //       ]
    //     }
    //   }
    // },
    // uglify: {
    //   dist: {
    //     files: {
    //       '<%= paths.dist %>/js/scripts.js': [
    //         '<%= paths.dist %>/js/scripts.js'
    //       ]
    //     }
    //   }
    // },
    // concat: {
    //   dist: {}
    // },

    // Test settings
    karma: {
      unit: {
        configFile: 'karma.conf.js',
        singleRun: true
      }
    },

    compass: {
      watch: {
        options: {
          sassDir: '<%= paths.app %>/css/sass',
          cssDir: '<%= paths.app %>/css',
          watch: true // use the native compass watch command
        }
      },
      compile: {
        options: {
          sassDir: '<%= paths.app %>/css/sass',
          cssDir: '<%= paths.app %>/css'
        }
      }
    },

    ngtemplates: {
      app: {
        cwd: 'app/partials',
        src: '*.html',
        dest: '.tmp/templates.js',
        options: {
          htmlmin: {
            collapseBooleanAttributes: true,
            collapseWhitespace: false,
            removeAttributeQuotes: true,
            removeComments: true,
            removeEmptyAttributes: false,
            removeRedundantAttributes: true
          }
        }
      }
    },

    htmlrefs: {
      dist: {
        src: '<%= paths.dist %>/index.html',
        dest: '<%= paths.dist %>/index.html'
      }
    },
  });


  grunt.registerTask('serve', function (target) {
    if (target === 'dist') {
      return grunt.task.run(['build', 'connect:dist:keepalive']);
    }

    grunt.task.run([
      'clean:server',
      'concurrent:server',
      'autoprefixer',
      'connect:livereload',
      'watch'
    ]);
  });

  grunt.registerTask('server', function () {
    grunt.log.warn('The `server` task has been deprecated. Use `grunt serve` to start a server.');
    grunt.task.run(['serve']);
  });

  grunt.registerTask('test', [
    'clean:server',
    'concurrent:test',
    'autoprefixer',
    'connect:test',
    'karma'
  ]);

  grunt.registerTask('build', [
    'clean:dist',
    'ngtemplates',
    'compass:compile',
    'useminPrepare',
    'concurrent:dist',
    'autoprefixer',
    'concat',
    'ngAnnotate',
    'copy:dist',
    'cdnify',
    'cssmin',
    'uglify',
    'rev',
    'usemin',
    'htmlrefs',
  ]);

  // generate .json files in dist-static/api by copying output from the PHP API
  grunt.registerTask('generateStaticAPI', function () {
    var request = require('request');
    var async = require('async');
    var _ = require('lodash');

    var src = grunt.config('paths.url') + '/' + grunt.config('paths.app') +
      '/api';
    var dest = grunt.config('paths.distStatic') + '/api';

    var done = this.async();

    // copy the file at the given path, then call the optional callback
    var copy = function (path, callback) {
      request(src + '/' + path, function (error, response, body) {
        // log
        grunt.log.write('writing:', path, '... ');

        // catch errors
        if (error) {
          grunt.log.error(error);
          return;
        }
        if (response.statusCode !== 200) {
          grunt.log.error('status code:', response.statusCode);
          return;
        }

        // save the body
        grunt.file.write(dest + '/' + path, body);

        // log
        grunt.log.ok('written:', path);

        // callback
        if (typeof callback === 'function') {
          callback(error, response, body);
        }
      });
    };

    // helper method to flatten the dirs tree; see RefilerModel
    var flatten = function (dirs) {
      var result = [];
      _.each(dirs, function (dir) {
        result.push(dir);
        result = result.concat(flatten(dir.subdirs));
      });
      return result;
    };

    // copy init.json, then parse its data to copy the other files
    copy('init.json', function (error, response, body) {
      // parse data
      var data = JSON.parse(body);

      // store all paths for use in the loop below
      var paths = [];
      data.tags.forEach(function (tag) {
        paths.push('tag/' + tag.id + '.json');
      });
      flatten(data.dirs).forEach(function (dir) {
        paths.push('dir/' + dir.id + '.json');
      });

      // use async.each to take advantage of its callback when all iterator
      // functions have finished, where we can call done()
      async.each(paths, function (path, callback) {
        // copy the file
        copy(path, function () {
          callback();
        });
      }, function (error) {
        if (error) {
          grunt.log.error('error in async loop:', error);
          return;
        }
        done();
      });
    });
  });

  grunt.registerTask('buildStatic', [
    'clean:distStatic',
    'copy:distStatic',
    'generateStaticAPI',
  ]);

  grunt.registerTask('default', [
    'newer:jshint',
    'test',
    'build'
  ]);
};
