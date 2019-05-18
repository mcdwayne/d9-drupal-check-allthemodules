var app = angular.module('angularTable', ['angularUtils.directives.dirPagination']);

app.controller('listdata',function($scope, $http){
	$scope.userslist = []; //declare an empty array
	var base_url = drupalSettings.angular_js_example.url_base;
	
	$http.get(base_url + "/list/get_list_json").success(function(response){ 
	    
		$scope.users = response;  //ajax request to fetch data into $scope.data
	});
	
	$scope.sort = function(keyname){
		$scope.sortKey = keyname;   //set the sortKey to the param passed
		$scope.reverse = !$scope.reverse; //if true make it false and vice versa
	}
	
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
	   
	   
		return $http({
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			url: base_url + '/list/save_form',
			method: "POST",
			data: $params
		  })
		  .success(function(addData) {
			$scope.users = addData;
			
			return $scope.users;
		  });
	   
	  }
	
	// Handling the delete button for the form.
	$scope.deleteBooks =  function(id) {
		
    $params = jQuery.param({
		"bid": id,
    });
		
		  return $http({
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			url: base_url + '/list/delete_books_json',
			method: "POST",
			data: $params
		  })
		  .success(function(addData) {
			  $scope.users = addData;
			
		 });
  }
  
  
  $scope.editAnyBook = function(id) {
     var base_url = drupalSettings.angular_js_example.url_base;
     $http.get(base_url + '/list/edit_books_json/'+ id).then(function(response) {
		 
       $scope.bookData =  response.data;
     });
   }
  
  
});


app.factory('sharedBooks', ['$http', '$rootScope', function($http, $rootScope) {
  var books = [];
  return {
    getBooks: function() {
      var base_url = drupalSettings.angular_js_example.url_base;
	  
      return $http.get(base_url + '/list/get_list_json').then(function(response) {
        $scope.users = response;
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
        $scope.users = response;
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
        $scope.users = response;
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
        $scope.users = response;
      });
    },
  };
}]).config(["$httpProvider", function(provider) {
  provider.defaults.headers.common['X-ANGULARJS'] = 1;
}]);;