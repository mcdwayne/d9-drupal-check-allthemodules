/**
 * @file
 * Helper js to load the mirador viewer.
 */

(function ($, Drupal) {
  Drupal.Mirador = Drupal.Mirador || {};
  Drupal.behaviors.Mirador = {
    attach: function (context, settings) {
      var manifestUri = settings.init.entity.manifest_uri;
      var annotationUri = settings.init.entity.annotation_uri;
      var viewerID = settings.init.entity.viewer_id;
      var imageRefEntityID = settings.init.entity.entity_id;
      var userID = settings.init.entity.user_id;
      var annotationPermission = settings.init.perform_annotation;
      var annotationSettings = settings.init.annotation_settings;
      var tokenUrl = settings.init.token_url;
      var endpoint = settings.init.endpoint;
      $(document).on("click", ".mirador-osd-annotations-layer", function() {
        if (annotationPermission == false) {
          var anonymousUserHelpText = Drupal.t("Please login to annotate");
          $('.mirador-osd-context-controls .mirador-osd-edit-mode').text(anonymousUserHelpText);
          $('.mirador-viewer').addClass('annotate-no-permission');
        }
      });
      xcrfToken = null;
      if ($('#' + viewerID + ' .mirador-viewer').length == 0) {
        // Fetch the api token.
        if (endpoint === "rest_endpoint") {
          jQuery.ajax({
            url: tokenUrl,
            type: "GET",
          }).done(function(data) {
            xcrfToken = data;
            attachMirador(viewerID, manifestUri, annotationPermission, xcrfToken, imageRefEntityID, userID, annotationSettings);
          });
        }
        else {
          xcrfToken = null;
          attachMirador(viewerID, manifestUri, annotationPermission, xcrfToken, imageRefEntityID, userID, annotationSettings);
        }
      }
    }
  };

  /**
   * Attaches mirador viewer to the viewerID Provided.
   */
  function attachMirador(viewerID, manifestUri, annotationPermission, xcrfToken, imageRefEntityID, userID, annotationSettings) {
    Mirador({
      "id": viewerID,
      'workspaces' : {
        'singleObject': {
          'label': 'Single Object',
          'addNew': false,
          'move': false,
          'iconClass': 'image'
        },
        'compare': {
          'label': 'Compare',
          'iconClass': 'columns'
        },
        'bookReading': {
          'defaultWindowOptions': {
          },
          'label': 'Book Reading',
          'addNew': true,
          'move': false,
          'iconClass': 'book'
        }
      },
      'showAddFromURLBox' : false,
      "layout": "1x1",
      'openManifestsPage' : false,
      'showAddFromURLBox' : false,
      "saveSession": false,
      "data": [
        { "manifestUri": manifestUri, "location": "National Virtual Library Of India"},
      ],
      "windowObjects":[{
        "loadedManifest" : manifestUri,
        "viewType" : "ImageView",
        "annotationLayer": true,
        "annotationCreation": annotationPermission,
        "layoutOptions": {
          "newObject" : false,
          "close": false,
          "slotRight": false,
          "slotLeft": false,
          "slotAbove": false,
          "slotBelow": false,
        }
      }],
      annotationEndpoint: {
        name: 'Mirador Endpoint',
        module: 'MiradorEndpoint',
        options: {
          xcrfToken: xcrfToken,
          imageRefEntityID: imageRefEntityID,
          annotationOwner: userID,
          annotationSettings: annotationSettings
        }
      }
    });
  }
})(jQuery, Drupal);
