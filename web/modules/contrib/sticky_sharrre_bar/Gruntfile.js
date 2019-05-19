module.exports = function (grunt) {

  'use strict';

  // Time how long tasks take. Can help when optimizing build times.
  require('time-grunt')(grunt);

  // Automatically load required grunt tasks.
  require('jit-grunt')(grunt, {
    useminPrepare: 'grunt-usemin',
    sprite: 'grunt-spritesmith'
  });

  // Configurable.
  var config = {};

  // Project configuration.
  grunt.initConfig({

    // Config.
    config: config,

    // Compiles Sass to CSS and generates necessary files if requested.
    sass: {
      development: {
        options: {
          style: 'expanded',
          unixNewlines: true,
          sourcemap: true
        },
        files: [{
          expand: true,
          cwd: 'sass',
          src: ['*.scss'],
          dest: 'css',
          ext: '.css'
        }]
      }
    },
    autoprefixer: {
      dist: {
        options: {
          map: true,
          browsers: [
            'last 3 versions'
          ]
        },
        files: {
          'css/sticky_sharrre_bar.css': 'css/sticky_sharrre_bar.css'
        }
      }
    },

    // Watches files for changes and runs tasks based on the changed files.
    watch: {
      sass: {
        files: ['sass/*.scss'],
        tasks: ['sass:development', 'autoprefixer']
      },
      gruntfile: {
        files: ['Gruntfile.js']
      },
      js: {
        files: ['js/*.js'],
        options: {
          livereload: true
        }
      }
    }
  });

  // Force load tasks which can not be loaded by 'jit-grunt' plugin.
  grunt.loadNpmTasks('grunt-notify');

  grunt.registerTask('default', ['watch']);
};
