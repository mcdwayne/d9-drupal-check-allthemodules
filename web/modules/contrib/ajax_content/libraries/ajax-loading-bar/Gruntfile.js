module.exports = function (grunt) {
    grunt.initConfig({
            uglify: {
                js: {
                    files: {
                        'build/jquery-loading-bar.min.js': [
                            'jquery-loading-bar.js'
                        ]
                    }
                }
            },
            cssmin: {
                combine: {
                    files: {
                        'build/loading-bar.min.css': [
                            'loading-bar.css'
                        ]
                    }
                }
            },
            copy: {
                main: {
                    files: [
                        {expand: true, src: ['jquery-loading-bar.js'], dest: 'src/', filter: 'isFile'},
                        {expand: true, src: ['loading-bar.css'], dest: 'src/'},
                        {expand: true, src: ['jquery-loading-bar.js'], dest: 'build/', filter: 'isFile'},
                        {expand: true, src: ['loading-bar.css'], dest: 'build/'}
                    ]
                }
            }
        }
    );
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.registerTask('default', [ 'uglify', 'cssmin','copy']);

};