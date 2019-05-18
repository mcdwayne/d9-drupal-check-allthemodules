/**
 * @file
 * Implement a simple, angular js.
 */

var singlePageModule = angular.module('myapp', [])

/*
singlePageModule.config(function($routeProvider) {
	var base_url = drupalSettings.angular_js_example.url_base;
	
    $routeProvider
    .when("/", {
        templateUrl : base_url+"/modules/examplelist/templates/asinglepage-form.html"
    })
    .when("/add", {
 
       templateUrl : base_url+"/modules/examplelist/templates/addsinglepage-form.html.twig"
    })
    
});*/



singlePageModule.controller('bookViewCtrl', function($scope,$http, $routeParams, sharedBooks) {
  $scope.name = 'bookViewCtrl';
  sharedBooks.getBooks().then(function(books) {
	$scope.currentPage = 0;
	$scope.pageSize = 10;
	$scope.q = '';
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
	bookData.name='';
	bookData.price='';
	bookData.authorId='';
   sharedBooks.saveBooks($params);
  }
  
  // Handling the submit button for the form.
  $scope.add = function(bookData) {
    
  }
  
  // Handling the submit button for the form.
  $scope.paesizeNewBook = function(bookData) {
    $params = jQuery.param({
      "name": bookData.pageSize,
    });
	
   sharedBooks.sizeBooks($params);
  }
  
  $scope.deleteBooks =  function(id) {
    $params = jQuery.param({
      "bid": id,
    });
    sharedBooks.deleteBooks($params);
  }
   $scope.editAnyBook = function(id) {
     var base_url = drupalSettings.angular_js_example.url_base;
     $http.get(base_url + '/list/edit_books_json/'+ id).then(function(response) {
       $scope.bookData =  response.data;
     });
   }
});


singlePageModule.factory('sharedBooks', ['$http', '$rootScope', function($http, $rootScope) {
  var books = [];
  return {
    getBooks: function() {
      var base_url = drupalSettings.angular_js_example.url_base;
	  
      return $http.get(base_url + '/list/get_list_json').then(function(response) {
        books = response.data;
		
        $rootScope.$broadcast('handleSharedBooks', books);
        return books;
      })
    },
    saveBooks: function($params) {
      var base_url = drupalSettings.angular_js_example.url_base;
	  
      return $http({
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        url: base_url + '/list/save_form',
        method: "POST",
        data: $params
      })
      .success(function(addData) {
        books = addData;
        $rootScope.$broadcast('handleSharedBooks', books);
		
        return books;
      });
    },
	sizeBooks: function($params) {
      var base_url = drupalSettings.angular_js_example.url_base;
	  
      return $http({
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        url: base_url + '/list/get_list_json',
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
        url: base_url + '/list/delete_books_json',
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
