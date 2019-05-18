/**
 * @file
 * Contains the definition for apiNG social wall design.
 */

(function ($, Drupal, drupalSettings) {
  "use strict";
  /**
   * Attaches...
   */
  Drupal.behaviors.socialWallDesign = {
    attach: function (context, settings) {
      angular.module('jtt_aping_design_default', ['wu.masonry', 'linkify', 'angularMoment', 'ngSanitize', 'jtt_imagesLoaded'])
          .run(['amMoment', function (amMoment) {
              amMoment.changeLocale('en');
          }])
          .controller('apingDefaultDesignController', ['$scope', '$sce', function ($scope, $sce) {

              $scope.$on('apiNG.resultMerged', function () {
                  //$scope.workingCopy = $scope.results;
              });
              
              $scope.getPlatformIcon = function (_platform) {
                  var modulePath = drupalSettings.aping.apingConfig.path;
                  switch (_platform) {
                      case "youtube":
                      case "twitter":
                      case "instagram":
                      case "vimeo":
                      case "vine":
                      case "facebook":
                      case "flickr":
                      case "dailymotion":
                      case "tumblr":
                      case "rss":
                      case "bandsintown":
                          return modulePath+"/aping/img/"+_platform+".png";

                  }

                  return false;
              };

              $scope.refresh = function () {
                  $scope.$broadcast("masonry.reload");
              };

              $scope.$on('imagesLoaded.SUCCESS', function() {
                  $scope.refresh();
              });
              $scope.$on('imagesLoaded.ALWAYS', function() {
                  $scope.refresh();
              });

              $scope.getUrl = function (url) {
                  if(url) {
                      return $sce.trustAsResourceUrl(url);
                  }
              };

              $scope.getHtml = function (string) {
                  if(string) {
                      return $sce.trustAsHtml(string);
                  }
              };

          }]);
    }
  };
})(jQuery, Drupal, drupalSettings);
