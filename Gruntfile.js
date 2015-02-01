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
    }
  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-phpunit');

  grunt.registerTask( 'wp', ['wp_readme_to_markdown']);
  grunt.registerTask( 'test', ['phpunit']);

};

