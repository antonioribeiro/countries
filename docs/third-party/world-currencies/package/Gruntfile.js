module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    clean: {
      dist: ["dist/*"]
    },
    json5_to_json: {
      options: {
        replacer: null,
        space: 2
      },
      target: {
        options: {
          space: 4
        },
        src: ['src/*.json5'],
        dest: 'dist/json/'
      },
    },
    "merge-json": {
      "currencies": {
        src: [ "dist/json/src/*.json" ],
        dest: "dist/json/currencies.json"
      }
    },
    rename: {
      main: {
        files: [
          {src: ['dist/json/src/'], dest: 'dist/json/countries/'},
        ]
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-json5-to-json');
  grunt.loadNpmTasks('grunt-merge-json');
  grunt.loadNpmTasks('grunt-contrib-rename');

  grunt.registerTask('default', ["clean","json5_to_json","merge-json:currencies","rename"]);

};