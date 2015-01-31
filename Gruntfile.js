module.exports = function(grunt) {

  grunt.initConfig({
    wp_readme_to_markdown: {
      plugin: {
        files: {
          "README.md": "readme.txt"
        }
      },
    },
  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.registerTask( 'wp', [ 'wp_readme_to_markdown'  ])

};

