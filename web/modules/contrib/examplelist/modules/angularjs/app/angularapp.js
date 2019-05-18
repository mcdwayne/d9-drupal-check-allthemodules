var app = angular.module('angularTable', ['angularUtils.directives.dirPagination']);

app.controller('listdata',function($scope, $http){
	$scope.userslist = []; //declare an empty array
	var base_url = drupalSettings.angular_js_example.url_base;
	
	
	$http.get(base_url + "/json/candidatedetails").success(function(response){ 
	    console.log(response);
		$scope.nodelist = response;  //ajax request to fetch data into $scope.data
		
	});
	
	$scope.sort = function(keyname){
		$scope.sortKey = keyname;   //set the sortKey to the param passed
		$scope.reverse = !$scope.reverse; //if true make it false and vice versa
	}
	
	// Handling the submit button for the form.
	  $scope.addNewBook = function(client) {
		  
		$params = jQuery.param({
		  "name": client.name,
		  "mail": client.mail,
		  "phone": client.phone,
		  "nid": client.nid,
		});
		client.name='';
		client.mail='';
		client.phone='';
		client.nid='';
	   
	   
		return $http({
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			url: base_url + '/angularjs/save_form',
			method: "POST",
			data: $params
		  })
		  .success(function(addData) {
			$http.get(base_url + "/json/candidatedetails").success(function(response){ 
	    
				$scope.nodelist = response;  //ajax request to fetch data into $scope.data
				return $scope.nodelist;
			});
			
			return $scope.nodelist;
		  });
	   
	  }
	
	// Handling the delete button for the form.
	$scope.deleteBooks =  function(id) {
		
    $params = jQuery.param({
		"nid": id,
    });
		
		  return $http({
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			url: base_url + '/angularjs/delete_books_json',
			method: "POST",
			data: $params
		  })
		  .success(function(addData) {
			  $http.get(base_url + "/json/candidatedetails").success(function(response){ 
	    
				$scope.nodelist = response;  //ajax request to fetch data into $scope.data
				return $scope.nodelist;
			});
			
			return $scope.nodelist;
			
		 });
  }
  
  
  $scope.editAnyBook = function(id,client) {
     var base_url = drupalSettings.angular_js_example.url_base;
     $http.get(base_url + '/json/candidatedetails/'+ id).then(function(response) {
		 
       $scope.client =  response.data[0];
	   //console.log(response.data[0].title);
	   $scope.client.name = response.data[0].title;
	   $scope.client.mail = response.data[0].field_candidate_email;
	   $scope.client.phone = response.data[0].field_phone_number;
	   $scope.client.nid = response.data[0].nid;
	   return $scope.client;
     });
   }
  
  
});

app.directive('ngConfirmClick', [
    function(){
        return {
            link: function (scope, element, attr) {
                var msg = attr.ngConfirmClick || "Are you sure?";
                var clickAction = attr.confirmedClick;
                element.bind('click',function (event) {
                    if ( window.confirm(msg) ) {
                        scope.$eval(clickAction)
                    }
                });
            }
        };
}])

app.factory('sharedBooks', ['$http', '$rootScope', function($http, $rootScope) {
  var books = [];
  return {
    getBooks: function() {
      var base_url = drupalSettings.angular_js_example.url_base;
	  
      return $http.get(base_url + '/list/get_list_json').then(function(response) {
        $scope.nodelist = response;
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
        $scope.nodelist = response;
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
        $scope.nodelist = response;
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
        $scope.nodelist = response;
      });
    },
  };
}]).config(["$httpProvider", function(provider) {
  provider.defaults.headers.common['X-ANGULARJS'] = 1;
}]);;