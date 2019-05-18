(function ($, _, JSON) {

  "use strict";

  // Map the metadata to data- attributes.
  function setMetadata(commentNode, element) {
    var metadata = JSON.parse(commentNode.textContent);
    for (var key in metadata) {
      var value = metadata[key];
      if (value instanceof Array) {
        if (value.length === 0) {
          continue;
        }
        value = value.join(' ');
      }
      element.setAttribute('data-renderviz-' + key, value);
    }
  }

  // Map the pre-bubbling metadata to data- attributes.
  function setPrebubblingMetadata(commentNode, element) {
    var prebubblingMetadata = JSON.parse(commentNode.textContent);
    for (var key in prebubblingMetadata) {
      var value = prebubblingMetadata[key];
      if (value instanceof Array) {
        if (value.length === 0) {
          continue;
        }
        value = value.join(' ');
      }
      element.setAttribute('data-renderviz-prebubbling-' + key, value);
    }
  }

  function visualize(queryMetadataType, queryMetadataValue) {
    // One-time initialization.
    $('html').once('renderviz').addClass('renderviz');
    // Reset.
    $('.renderviz-trace-layer').removeClass('renderviz-trace-layer');
    $('.renderviz-trace-layer-root').removeClass('renderviz-trace-layer-root');
    $('.renderviz-trace-focus').removeClass('renderviz-trace-focus');
    // Apply the new query.
    var result = $('[data-renderviz-' + queryMetadataType + '~="' + queryMetadataValue + '"]');
    console.log('' + result.length + ' matches found.');
    console.log(result);
    result.addClass('renderviz-trace-layer');
    $('[data-renderviz-prebubbling-' + queryMetadataType + '~="' + queryMetadataValue + '"]').addClass('renderviz-trace-layer-root');
    window.rendervizLastType = queryMetadataType;
    window.rendervizLastValue = queryMetadataValue;
  }

  function focus(index) {
    // Reset.
    $('.renderviz-trace-focus').removeClass('renderviz-trace-focus');
    // Apply the new focus.
    var el = $('[data-renderviz-' + window.rendervizLastType + '~="' + window.rendervizLastValue+ '"]')[index];
    console.log(el);
    $(el).addClass('renderviz-trace-focus');
  }

  Drupal.behaviors.renderviz = {
    attach: function (context) {
      // Transplant the data from the HTML comments onto the parent element.
      var comments = $(context).comments(true);
      for (var i = 0; i < comments.length; i++) {
        if (comments[i].textContent === 'RENDERER_START') {
          var element = comments[i].nextElementSibling;
          if (element) {
            // Mark the element for renderviz treatment.
            element.setAttribute('data-renderviz-element', true);
            setMetadata(comments[i+1], element);
            setPrebubblingMetadata(comments[i+2], element);
          }
          // @todo improve this; might need some complex merging logic.
          // If we have renderer metadata, but it's not for an Element node,
          // then it is for a Text node. In that case, set the pre-bubbling
          // metadata of the Text node on the parent Element node.
          // e.g. TimestampFormatter — the node timestamp
          else {
            element = comments[i].parentElement;
            setPrebubblingMetadata(comments[i+2], element);
          }
        }
      }

      $('body').once('renderviz-init').each(function() {
        var contexts = [], tags = [];
        $('[data-renderviz-contexts]').each(function (index, element) {
          contexts = _.union(contexts, element.attributes['data-renderviz-contexts'].value.split(' '));
        });
        $('[data-renderviz-tags]').each(function (index, element) {
          tags = _.union(tags, element.attributes['data-renderviz-tags'].value.split(' '));
        });
        console.log('' + $('[data-renderviz-element]').length + ' unique rendered elements on the page.', "\nContexts:", contexts, "\nTags:", tags);
        console.log("To use:\n- Querying: `renderviz(metadataType, metadataValue)`, e.g. `renderviz('contexts', 'timezone')`.\n- Focusing: `rendervizFocus(index)`, e.g. `rendervizFocus(0)` to focus on the first element of the last query.");
        window.renderviz = visualize;
        window.rendervizFocus = focus;
      });
    }
  };

})(jQuery, _, window.JSON);
