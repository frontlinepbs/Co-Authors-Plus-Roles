module.exports = function(grunt) {

  grunt.initConfig({
    wp_readme_to_markdown: {
      plugin: {
        files: {
          "README.md": "readme.txt"
        }
      },
    },

    autoprefixer: {
      options: {
        browsers: ['last 2 versions', 'ie 8', 'ie 9']
      },
      css: {
        src: 'includes/css/admin-ui.src.css',
        dest: 'includes/css/admin-ui.css',
      }
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
      },
      css: {
        files: ['includes/**/*.src.css'],
        tasks: ['autoprefixer']
      }
    }

  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-autoprefixer');

  grunt.registerTask( 'wp', ['wp_readme_to_markdown']);
  grunt.registerTask( 'test', ['phpunit']);

};

