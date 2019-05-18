/**
 * @file
 */

(function ($) {
  Drupal.behaviors.exampleModule = {
    attach: function (context, settings) {

      'use strict';

      /* App Module */

      angular.element(document).ready(function () {
        angular.bootstrap(document, ['myapp']);
      });

      var myApp = angular.module('myapp', [
        'myControllers'
      ]);

      var myControllers = angular.module('myControllers', ['ngSanitize','ngAnimate']);

      myControllers.controller('myContrBasic', ['$scope', '$http', '$interval',
        function ($scope, $http, $interval) {

          $scope.isLoading = true;
          $scope.perpage = '5000';
          $scope.toggle = true;
          $scope.isRefresh = false;
          $scope.lastRow = 0;
          var stop;

          var errorCallback = function() {
            console.log('errorCallback--')  ;
          };
          
          $http.get(drupalSettings.path.baseUrl + 'dblog_quick_filter/get_dblog',
            {
              params: {
                page: $scope.perpage
              }
            }
          ).then(function successCall(response) {

            $scope.tbItems = response.data;
            $scope.filterLOG = response.data.rows;
            $scope.isLoading = false;
            $scope.theHeader = response.data.header;
            
          },errorCallback);

          $scope.perPage = function () {
            $scope.isLoading = true;
            $http.get(drupalSettings.path.baseUrl + 'dblog_quick_filter/get_dblog',
              {
                params: {
                  page: $scope.perpage
                }
              }
            ).then(function successCall(response) {
              $scope.tbItems = response.data;
              $scope.filterLOG = response.data.rows;

              $scope.isLoading = false;
            },errorCallback);

          };

          $scope.checkMe = function (data) {
            if (data.class == null) {
              return data;
            }
            else {
              return '';
            }
          };

          $scope.detView = function (context, wid) {

            if (context.toggle === true) {

              $scope.isLoading = true;
              $http.get(drupalSettings.path.baseUrl + 'dblog_quick_filter/get_event/' + wid)
                  .then(function successCall(response) {console.log(response);
                    angular.element('#' + wid + ' > td').html(response.data.data);
                    context.toggle = !context.toggle;
                    $scope.isLoading = false;
                  },errorCallback);
            }
            else {

              context.toggle = !context.toggle;
            }
          };

          $scope.clearFilter = function(inst) {
            if (inst === 'all') {
              $scope.query = '';
              $scope.type = undefined;
              $scope.severity = undefined;
              $scope.user = undefined;
            }
            else if (angular.isString($scope[inst])) {

              $scope[inst] = '';
            }
            else if (angular.isArray($scope[inst])) {

              $scope[inst] = undefined;
            }

            return true;
          };
          
          $scope.getStyle = function() {
              return 'background-color:red;'
          }
          this.getstyle = $scope.getStyle;
          
          $scope.setFirstKey = function(first,row){
              if(first === true && angular.isUndefined(row) === false) { 
                  $scope.lastRow = row; 
              }
          };
          
          $scope.$watch('isRefresh',function(newValue, oldValue) {
              if(newValue == true) {
                  stop = $interval(function(){
                    $http.get(drupalSettings.path.baseUrl + 'dblog_quick_filter/tail_dblog/' + $scope.lastRow,
                    {
                      params: {
                      }
                    }
                  ).then(refreshCallback,errorCallback);
                  },2000);
              } else {
                $interval.cancel(stop);
              }
          });
          
            var refreshCallback = function(responseData) {
                if ($scope.tbItems.rows.length > 0) {
                    if (responseData.data.rows.length > 0) {
                        angular.forEach(responseData.data.rows, function(value, key) {
                            $scope.tbItems.rows.unshift(value);
                        });
                        $scope.lastRow = responseData.data.rows[responseData.data.rows.length - 1].data[7];
                    }
                } else {
                    $scope.tbItems.rows = responseData.data.rows;
                    $scope.lastRow = responseData.data.rows[responseData.data.rows.length - 1].data[7];
                }

            };
          
        }
      ]);
      
      
       myControllers.filter('FilterQuery',
                function() {
            return function(items, query, match) {
                var term;
                var part;
                var toReturn = [];

                if (angular.isUndefined(query) === false) {
                    angular.forEach(items, function(value, key) {

                        if (match === true) {
                            term = value.data[3];
                            part = query;
                        }
                        else {
                            term = angular.lowercase(value.data[3]);
                            part = angular.lowercase(query);
                        }
                        if (term.indexOf(part) === -1) {
                            //===
                        }
                        else {
                            toReturn.push(value);
                        }
                    });
                }
                else {
                    return items;
                }
                return toReturn;
            };
        })
        .filter('FilterType',
                function() {
                    return function(items, type) {
                        var toReturn = [];
                        if (angular.isUndefined(type) === false) {
                            angular.forEach(items, function(value, key) {
                                if (type.indexOf(value.data[1]) !== -1) {
                                    toReturn.push(value);
                                }
                                else {
                                    //====
                                }
                            });
                        }
                        else {
                            return items;
                        }
                        return toReturn;
                    };
                })
        .filter('FilterSeverity',
                function() {
                    return function(items, severity) {
                        var toReturn = [];

                        if (angular.isUndefined(severity) === false) {
                            angular.forEach(items, function(value, key) {
                                if (severity.indexOf(value.data[6]) !== -1) {
                                    toReturn.push(value);
                                }
                                else {
                                    //=====
                                }
                            });
                        }
                        else {
                            return items;
                        }
                        return toReturn;
                    };
                })
        .filter('FilterUser',
                function() {
                    return function(items, user) {
                        var toReturn = [];

                        if (angular.isUndefined(user) === false) {
                            angular.forEach(items, function(value, key) {
                                if (user.length === 1 && value.data[4].indexOf(user) !== -1) {
                                    toReturn.push(value);
                                }
                                else if (user.length > 1) {

                                    for (var i = 0; i <= user.length; i++) {

                                        if (angular.isUndefined(user[i]) === false) {

                                            if (value.data[4].indexOf(user[i]) !== -1) {
                                                toReturn.push(value);
                                            }
                                            if (i === user.length) {
                                                //=====
                                            }
                                        }
                                    }
                                }
                                else {
                                    //=====
                                }
                            });
                        }
                        else {
                            return items;
                        }
                        return toReturn;
                    };
                });

    }
  };
}(jQuery));
