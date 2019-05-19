module.exports = function(grunt) {

  require('load-grunt-tasks')(grunt);

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    sass: {
      options: {
        sourceMap: true
      },
      dist: {
        files: {
          'css/timed_messages.css': 'scss/timed_messages.scss'
        }
      }
    },

    watch: {
        files: '**/*.scss',
        tasks: ['sass']
    }

  });

  grunt.registerTask('default', ['watch']);

};
