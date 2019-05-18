/**
 * @file
 * Implement a simple, angular js.
 */

var singlePageModule = angular.module('myapp', [])
singlePageModule.controller('bookViewCtrl', function($scope, $routeParams, sharedBooks) {
  $scope.name = 'bookViewCtrl';
  sharedBooks.getBooks().then(function(books) {
    $scope.books = books;
  });

  $scope.$on('handleSharedBooks', function(events, books) {
    $scope.books = books;
  });

  // Handling the submit button for the form.
  $scope.addNewBook = function(bookData) {
    $params = jQuery.param({
      "bookname": bookData.name,
      "bookprice": bookData.price,
      "authorid": bookData.authorId,
      "tokenid": Drupal.settings.angular_js.angularjsexample_csrf_token,
    });
    sharedBooks.saveBooks($params);
  }
});


singlePageModule.factory('sharedBooks', ['$http', '$rootScope', function($http, $rootScope) {
  var books = [];
  return {
    getBooks: function() {
      var base_url = Drupal.settings.angular_js_example.url_base;
      return $http.get(base_url + 'json/get_books_json').then(function(response) {
        books = response.data;
        $rootScope.$broadcast('handleSharedBooks', books);
        return books;
      })
    },
    saveBooks: function($params) {
      var base_url = Drupal.settings.angular_js_example.url_base;
      return $http({
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        url: base_url + 'json/save_form',
        method: "POST",
        data: $params
      })
        .success(function(addData) {
          books = addData;
          $rootScope.$broadcast('handleSharedBooks', books);
          return books;
        });
    }
  };
}]).config(["$httpProvider", function(provider) {
  provider.defaults.headers.common['X-CSRF-Token'] = Drupal.settings.angular_js.angularjs_csrf_token;
  provider.defaults.headers.common['X-ANGULARJS'] = 1;
}]);;
