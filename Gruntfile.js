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
        bootstrap: 'tests/phpunit/bootstrap.php',
      },
    },

    jasmine: {
      adminUi: {
        src: 'includes/**/*.js',
        options: {
          specs: 'tests/jasmine/*Spec.js',
          vendor: [
            'https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.1/jquery.js',
            'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.js',
            'https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore.js',
            'https://cdnjs.cloudflare.com/ajax/libs/backbone.js/1.1.2/backbone.js',
          ],
          helpers: 'spec/*Helper.js',
        }
      }
    },

    watch: {
      php: {
        files: ['*.php','includes/**/*.php','tests/**/*.php'],
        tasks: ['phpunit']
      },
      js: {
        files: ['includes/**/*.js','tests/jasmine/*.js'],
        tasks: ['jasmine']
      },
      css: {
        files: ['includes/**/*.src.css'],
        tasks: ['autoprefixer']
      }
    }

  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-contrib-jasmine');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-autoprefixer');

  grunt.registerTask( 'wp', ['wp_readme_to_markdown']);
  grunt.registerTask( 'test', ['phpunit', 'jasmine']);

};

