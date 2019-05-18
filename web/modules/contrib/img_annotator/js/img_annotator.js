(function ($, Drupal) {
  
  // Drupal variables.
  var baseUrl = drupalSettings.img_annotator.baseUrl;
  var nodeId = drupalSettings.img_annotator.nodeId;
  var nodeOwner = drupalSettings.img_annotator.nodeOwner;
  var showAlerts = drupalSettings.img_annotator.showAlerts;
  var promptMsg = drupalSettings.img_annotator.promptMsg;
  
  var canCreate = drupalSettings.img_annotator.canCreate;
  var canUpdate = drupalSettings.img_annotator.canUpdate;
  var canDelete = drupalSettings.img_annotator.canDelete;
  var canView = drupalSettings.img_annotator.canView;

  var canCreateOwn = drupalSettings.img_annotator.canCreateOwn;
  var canUpdateOwn = drupalSettings.img_annotator.canUpdateOwn;
  var canDeleteOwn = drupalSettings.img_annotator.canDeleteOwn;
  var canViewOwn = drupalSettings.img_annotator.canViewOwn;

  // Local variables.
  var canCreateOnly = false;
  var canEditOnly = false;
  var canViewOnly = false;

  var imgFieldSrcArr = getImgFieldSrcArr();

  // Only create the annotorious content.
  var checkCreateOnly1 = canCreate || canCreateOwn;
  var checkCreateOnly2 = !canUpdate && !canDelete && !canView;
  var checkCreateOnly3 = !canUpdateOwn && !canDeleteOwn && !canViewOwn;
  if (checkCreateOnly1 && checkCreateOnly2 && checkCreateOnly3) {
    canCreateOnly = true;
  }

  // Only edit the annotorious content.
  var checkEditOnly1 = (canUpdate && canDelete) || (canUpdateOwn && canDeleteOwn);
  var checkEditOnly2 = !canCreateOwn && !canCreate && !canView && !canViewOwn;
  if (checkEditOnly1 && checkEditOnly2) {
    canEditOnly = true;
  }

  // Only view the annotorious content.
  var checkViewOnly1 = canView || canViewOwn;
  var checkViewOnly2 = !canCreate && !canUpdate && !canDelete;
  var checkViewOnly3 = !canCreateOwn && !canUpdateOwn && !canDeleteOwn;
  if (checkViewOnly1 && checkViewOnly2 && checkViewOnly3) {
    canViewOnly = true;
  }

  
  // Annotation Add Handler.
  anno.addHandler('onAnnotationCreated', function(annotation) {
    var nid = nodeId;
    var img_field = imgFieldSrcArr[annotation.src];

    if (canCreate || (canCreateOwn && nodeOwner)) {
      annotation.nid = nid;
      jQuery.ajax({
        url : baseUrl + '/img_annotator/save_action',
        success : function(result, status) {
          if (result !== false && status == 'success') {
            annotation.aid = result;
            if (canCreateOnly) {
              annotation.editable = false;
            }

            alertJsMessage(promptMsg.addSuccess, showAlerts);
          }
          else {
            anno.removeAnnotation(annotation);
            alertJsMessage(promptMsg.addFailed, showAlerts);
          }

        },
        async : true,
        type : 'POST',
        data : {
          'annotation' : annotation,
          'nid' : nid,
          'img_field' : img_field
        },
      });
    } else {
      anno.removeAnnotation(annotation);
      alertJsMessage(promptMsg.addNotAllowed, showAlerts);
    }

  });

  
  // Annotation Remove Handler.
  anno.addHandler('onAnnotationRemoved', function(annotation) {
    jQuery.ajax({
      url : baseUrl + '/img_annotator/delete_action',
      success : function(result, status) {
        if (result !== false && status == 'success') {
          alertJsMessage(promptMsg.removeSuccess, showAlerts);
        }
        else {
          alertJsMessage(promptMsg.removeFailed, showAlerts);
        }
      },
      async : true,
      type : 'POST',
      data : {'annotation' : annotation},
    });
  });
  
  
  // Annotation Update Handler.
  anno.addHandler('onAnnotationUpdated', function(annotation) {
    var nid = nodeId;
    
    jQuery.ajax({
      url : baseUrl + '/img_annotator/update_action',
      success : function(result, status) {
        if (result !== false && status == 'success') {
          alertJsMessage(promptMsg.updateSuccess, showAlerts);
        }
        else {
          alertJsMessage(promptMsg.updateFailed, showAlerts);
        }
      },
      async : true,
      type : 'POST',
      data : {'annotation' : annotation},
    });
  });

  
  // Solution: Annotorious lib issue,
  // Ambiguous annotorious-editor style top value.
  anno.addHandler('onEditorShown', function(annotation) {
    if (typeof annotation.src !== 'undefined') {
      var domainName = window.location.origin;
      var currentImg = $("img[src='" + annotation.src + "'].annotatable");
      if (currentImg.length == 0) {
        currentImg = $("img[src='" + annotation.src.substring(domainName.length) + "'].annotatable");
      }
      
      var parentTag = currentImg.closest('div.annotorious-annotationlayer');
  
      styleTopVal = parentTag.find(".annotorious-popup").css('top');
      parentTag.find(".annotorious-editor").css('top', styleTopVal);
    }

  });
  
  
  // Fetches all annotations.
  if (!canCreateOnly) {
    jQuery.ajax({
      url : baseUrl + '/img_annotator/retrieve_action',
      success : function(result) {
        if (result !== false) {
          var obj = jQuery.parseJSON(result);
          
          $.each(obj, function(key, value) {
            // Wait for two seconds, and apply existing annotations.
            setTimeout(function() {
              anno.addAnnotation(value);
              
              // TODO: this hide should be out of loop.
              // Hide interaction for image if viewOnly
              if (canEditOnly === true || canViewOnly === true) {
                anno.hideSelectionWidget(value.src);
              }
            }, 2000);
          });
        }
      },
      async : true,
      type : 'POST',
      data : {'nid' : nodeId},
    });
  }

  

  // Alert javascript messages if allowed.
  function alertJsMessage(msg, showAlerts) {
    if (showAlerts == 'js') {
      alert(msg);
    }
  }
  
  // Builds an array of all annotable image in the page, with 
  // array key as 'src' attribute and value with image field's machine name. 
  function getImgFieldSrcArr() {
    var imgFieldPrefix = "annoimg_";
    var imgFieldSrcArr = [];

    $('img.annotatable').each(function() {
      var imgSrc = this.src;
      var classArr = this.className.split(" ");

      classArr.forEach(function(item, index) {
        if (item.startsWith(imgFieldPrefix)) {
          var imgFieldName = item.substring(imgFieldPrefix.length);
          imgFieldSrcArr[imgSrc] = imgFieldName;
        }
      });
    });

    return imgFieldSrcArr;
  }
  
})(jQuery, Drupal);