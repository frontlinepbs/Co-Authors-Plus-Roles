module.exports = function(grunt) {

  grunt.initConfig({
    wp_readme_to_markdown: {
      plugin: {
        files: {
          "README.md": "readme.txt"
        }
      },
    },

    phpunit: {
      coAuthorsPlusTests: {},
      options: {
        bin: 'vendor/bin/phpunit',
        bootstrap: 'tests/bootstrap.php',
      },
    },

    watch: {
      php: {
        files: ['*.php','includes/**/*.php','tests/**/*.php'],
        tasks: ['phpunit']
      }
    }

  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask( 'wp', ['wp_readme_to_markdown']);
  grunt.registerTask( 'test', ['phpunit']);

};

