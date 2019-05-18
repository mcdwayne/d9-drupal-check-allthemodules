/**
 * @file
 * Implement a simple, angular js.
 */

var singlePageModule = angular.module('myapp', [])
singlePageModule.controller('bookViewCtrl', function($scope,$http, $routeParams, sharedBooks) {
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
      "name": bookData.name,
      "price": bookData.price,
      "authorId": bookData.authorId,
      "bid": bookData.bid,
    });
   sharedBooks.saveBooks($params);
  }
  $scope.deleteBooks =  function(id) {
    $params = jQuery.param({
      "bid": id,
    });
    sharedBooks.deleteBooks($params);
  }
   $scope.editAnyBook = function(id) {
     var base_url = drupalSettings.angular_js_example.url_base;
     $http.get(base_url + '/json/edit_books_json/'+ id).then(function(response) {
       $scope.bookData =  response.data;
     });
   }
});


singlePageModule.factory('sharedBooks', ['$http', '$rootScope', function($http, $rootScope) {
  var books = [];
  return {
    getBooks: function() {
      var base_url = drupalSettings.angular_js_example.url_base;
      return $http.get(base_url + '/json/get_books_json').then(function(response) {
        books = response.data;
        $rootScope.$broadcast('handleSharedBooks', books);
        return books;
      })
    },
    saveBooks: function($params) {
      var base_url = drupalSettings.angular_js_example.url_base;
      return $http({
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        url: base_url + '/json/save_form',
        method: "POST",
        data: $params
      })
      .success(function(addData) {
        books = addData;
        $rootScope.$broadcast('handleSharedBooks', books);
        return books;
      });
    },
    deleteBooks: function($params) {
      var base_url = drupalSettings.angular_js_example.url_base;
      return $http({
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        url: base_url + '/json/delete_books_json',
        method: "POST",
        data: $params
      })
      .success(function(addData) {
        books = addData;
        $rootScope.$broadcast('handleSharedBooks', books);
        return books;
      });
    },
  };
}]).config(["$httpProvider", function(provider) {
  provider.defaults.headers.common['X-ANGULARJS'] = 1;
}]);;
