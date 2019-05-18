/**
 * @file
 * Contains Lolading Angular App controller.
 */

(function ($, Drupal, drupalSettings) {
  var base_path_url = drupalSettings.search_json.base_url;

  var app = angular.module('angularjsTable', ['angularUtils.directives.dirPagination', 'ngSanitize']).config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('{[{').endSymbol('}]}');
  });
  app.controller('listitemdata', function ($scope, $http) {

    $scope.Result = []; // Declare an empty array.
    $http.get(base_path_url + "/sites/default/files/search_json.json").success(function (response) {
      $scope.Result = response;  // Ajax request to fetch data into $scope.data.
    });

    $scope.sort = function (keyname) {
      $scope.sortBy = keyname;   // Set the sortBy to the param passed
      $scope.reverse = !$scope.reverse; // if true make it false and vice versa.
    };
  });
})(jQuery, Drupal, drupalSettings);
