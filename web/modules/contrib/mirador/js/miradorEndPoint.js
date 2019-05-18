/**
 * @file
 * Drupal Endpoint for mirador annoations.
 *
 * All Endpoints need to have at least the following:
 * annotationsList - current list of OA Annotations
 * dfd - Deferred Object
 * init()
 * search(uri)
 * create(oaAnnotation, returnSuccess, returnError)
 * update(oaAnnotation, returnSuccess, returnError)
 * deleteAnnotation(annotationID, returnSuccess, returnError) (delete is a reserved word)
 * TODO:
 * There is a bug in that if you create an annotation and then delete it (without moving pages) then click either the write annotation button
 * or try to create a new annotation the deleted annotation re-appears.
 * Changing pages fixes the issue as the annoation is delete from the annotation store.
 */

(function($){

  $.MiradorEndpoint = function(options) {
    // If user does not have permission to annotate, display a message instead
      // of add annotation link.
    jQuery.extend(this, {
      token:     null,
      uri:      null,
      url:      options.url,
      imageRefEntity: options.imageRefEntityID,
      annotationOwner: options.annotationOwner,
      annotationSettings: options.annotationSettings,
      xcrfToken: options.xcrfToken,
      dfd:       null,
      annotationsList: [],
      // Internal list for module use to map id to URI.
      idMapper: {}
    }, options);

    this.init();
  };

  $.MiradorEndpoint.prototype = {
    // Any set up for this endpoint, and triggers a search of the URI passed to object.
    init: function() {
      this.catchOptions = {
        user: {
          id: this.userid,
          name: this.username
        },
        permissions: {
          'read':   [],
          'update': [this.userid],
          'delete': [this.userid],
          'admin':  [this.userid]
        }
      };
      this.search({ uri: this.uri });
    },

    // Search endpoint for all annotations with a given URI.
    search: function(options, successCallback, errorCallback) {
      var _this = this;
      annotationSettings = jQuery.parseJSON(_this.annotationSettings);
      // Clear out current list.
      this.annotationsList = [];
      jQuery.ajax({
        url: annotationSettings.annotation_search_uri,
        type: annotationSettings.annotation_search_method,
        dataType: 'json',
        headers: {
        },
        data: {
          uri:options.uri,
          id: _this.imageRefEntityID,
          media: "image",
          limit: 100,
        },
        contentType: "application/json; charset=utf-8",
        success: function(data) {
          jQuery.each(data, function(index, value) {
            value.data['@id'] = value.id;
            value.data['resource']['0']['chars'] = value.text;
            value.data['id'] = value.id;
            value.data['@fullId'] = value.id;
            annotationsList = value.data;
            _this.annotationsList.push(annotationsList);
            value.fullId = value.id;
            value["@id"] = $.genUUID();
            _this.idMapper[value["@id"]] = value.fullId;
            _this.annotationId = value.id;
            value.endpoint = _this;
          });
          if (typeof successCallback === "function") {
            successCallback(_this.annotationsList);
          }
          else {
            _this.dfd.resolve(false);
          }
        },
        error: function() {
          if (typeof errorCallback === "function") {
            errorCallback();
          }
          else {
            console.log("The request for annotations has caused an error for endpoint: " + options.uri);
          }
        }
      });
    },

    deleteAnnotation: function(annotationID, returnSuccess, returnError) {
      var _this = this;
      annotationSettings = jQuery.parseJSON(_this.annotationSettings);
      var xcrfToken = _this.xcrfToken;
      var drupal_annotation_id = annotationID;
      var annotationDeleteUri = annotationSettings.annotation_delete_uri;
      annotationDeleteUri = annotationDeleteUri.replace("{annotation_id}", _this.annotationId);
      jQuery.ajax({
        url: annotationDeleteUri,
        type: annotationSettings.annotation_delete_method,
        headers: {
          "Content-Type": 'application/json',
          "X-CSRF-Token": xcrfToken,
          "id": drupal_annotation_id,
        },
        contentType: "application/json; charset=utf-8",
        success: function(data) {
          returnSuccess();
        },
        error: function() {
          returnError();
        }

      });
    },

    update: function(oaAnnotation, returnSuccess, returnError) {
      var annotation = oaAnnotation,
          _this = this;
      shortId = annotation["@id"];
      var xcrfToken = _this.xcrfToken;
      annotation["@id"] = annotation['@fullId'];
      annotationID = annotation["id"];
      annotationSettings = jQuery.parseJSON(_this.annotationSettings);
      var annotationUpdateUri = annotationSettings.annotation_update_uri;
      annotationUpdateUri = annotationUpdateUri.replace("{annotation_id}", annotationID);
      var drupalAnnotationStore = {};
      // Generate annotation data to be stored as Drupal entity.
      if (annotationSettings.endpoint == "rest_endpoint") {
        drupalAnnotationStore['_links'] = {};
        drupalAnnotationStore['_links']['type'] = {};
        drupalAnnotationStore['_links']['type']['href'] = annotationSettings.type_url;
        drupalAnnotationStore[annotationSettings.annotation_text] = {};
        drupalAnnotationStore[annotationSettings.annotation_text]['value'] = annotation['resource']['0']['chars'];
        var annotation_data = JSON.stringify(drupalAnnotationStore);
      }
      else {
        drupalAnnotationStore['uri'] = {};
        drupalAnnotationStore['uri'] = annotation['on']['source'];
        drupalAnnotationStore['id'] = {};
        drupalAnnotationStore['id'] = annotationID;
        drupalAnnotationStore['text'] = {};
        drupalAnnotationStore['text'] = annotation['resource']['0']['chars'];
        drupalAnnotationStore['data'] = {};
        drupalAnnotationStore['data'] = annotation;
        drupalAnnotationStore['media'] = 'image';
        var annotation_data = JSON.stringify(drupalAnnotationStore);
      }
      jQuery.ajax({
        url: annotationUpdateUri,
        type: annotationSettings.annotation_update_method,
        dataType: 'json',
        headers: {
          "Content-Type": 'application/hal+json',
          "X-CSRF-Token": xcrfToken,
        },
        data: annotation_data,
        contentType: "application/json; charset=utf-8",
        success: function(data) {
          // This returned data doesn't seem to be used anywhere.
          returnSuccess();
        },
        error: function() {
          returnError();
        }
      });
      // This is what updates the viewer.
      annotation.endpoint = _this;
      annotation.fullId = annotation["@id"];
      annotation["@id"] = shortId;
    },

    create: function(oaAnnotation, returnSuccess, returnError) {
      var annotation = oaAnnotation,
          _this = this;
      var xcrfToken = _this.xcrfToken;
      annotation['imgRefEntity'] = _this.imageRefEntityID;
      annotation['annotationOwner'] = this.annotationOwner;
      annotationSettings = jQuery.parseJSON(_this.annotationSettings);
      var drupalAnnotationStore = {};
      if (annotationSettings.endpoint == "rest_endpoint") {
        // Generate annotation data to be stored as Drupal entity.
        drupalAnnotationStore['_links'] = {};
        drupalAnnotationStore['_links']['type'] = {};
        drupalAnnotationStore['_links']['type']['href'] = annotationSettings.type_url;
        if (annotationSettings.annotation_text != "title") {
          drupalAnnotationStore['title'] = {};
          drupalAnnotationStore['title']['value'] = annotation['resource']['0']['chars'];
        }
        drupalAnnotationStore[annotationSettings.annotation_text] = {};
        drupalAnnotationStore[annotationSettings.annotation_text]['value'] = annotation['resource']['0']['chars'];
        drupalAnnotationStore[annotationSettings.annotation_image_entity] = {};
        drupalAnnotationStore[annotationSettings.annotation_image_entity]['und'] = {};
        drupalAnnotationStore[annotationSettings.annotation_image_entity]['und']['target_id'] = annotation['imgRefEntity'];
        drupalAnnotationStore[annotationSettings.annotation_viewport] = {};
        drupalAnnotationStore[annotationSettings.annotation_viewport]['value'] = annotation['on']['selector']['value'];
        drupalAnnotationStore[annotationSettings.annotation_resource] = {};
        drupalAnnotationStore[annotationSettings.annotation_resource]['value'] = annotation['on']['source'];
        drupalAnnotationStore[annotationSettings.annotation_data] = {};
        drupalAnnotationStore[annotationSettings.annotation_data]['value'] = JSON.stringify(annotation);
        drupalAnnotationStore['type'] = {};
        drupalAnnotationStore['type']['target_id'] = annotationSettings.annotation_entity_bundle;
        var annotation_data = JSON.stringify(drupalAnnotationStore);
      }
      else {
        drupalAnnotationStore['uri'] = {};
        drupalAnnotationStore['uri'] = annotation['on']['source'];
        drupalAnnotationStore['text'] = {};
        drupalAnnotationStore['text'] = annotation['resource']['0']['chars'];
        drupalAnnotationStore['id'] = {};
        drupalAnnotationStore['id'] = _this.imageRefEntityID;
        drupalAnnotationStore['data'] = {};
        drupalAnnotationStore['data'] = annotation;
        drupalAnnotationStore['media'] = 'image';
        var annotation_data = JSON.stringify(drupalAnnotationStore);
      }
      jQuery.ajax({
        url: annotationSettings.annotation_create_uri,
        type: annotationSettings.annotation_create_method,
        dataType: 'json',
        headers: {
          "Content-Type": 'application/hal+json',
          "X-CSRF-Token": xcrfToken,
        },
        data: annotation_data,
        contentType: "application/json; charset=utf-8",
        success: function(result, status, xhr) {
          var annotation_id = result.id;
          if (annotationSettings.endpoint == "rest_endpoint") {
            if (result['nid']['0']['value'] != 'undefined') {
              annotation_id = result['nid']['0']['value'];
            }
          }
          annotation['id'] = annotation_id;
          annotation['@id'] = annotation_id;
          annotation['fullId'] = annotation_id;
          data = JSON.stringify(annotation);
          data.fullId = annotation_id;
          data["@id"] = $.genUUID();
          data.endpoint = _this;
          _this.idMapper[data["@id"]] = annotation_id;
          _this.annotationId = annotation_id;
          returnSuccess(data);
        },
        error: function() {
          returnError();
        }
      });
    },

    set: function(prop, value, options) {
      if (options) {
        this[options.parent][prop] = value;
      }
      else {
        this[prop] = value;
      }
    },
    userAuthorize: function(action, annotation) {
      return true;
    }
  };
}(Mirador));
