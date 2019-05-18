<?php

namespace Drupal\node_clone;

/**
* Field handler to present a clone node link.
*
* Closely modeled after views/modules/node/views_handler_field_node_link_edit.inc
*/
class views_handler_field_node_link_clone extends views_handler_field_node_link {

  /**
   * Renders the link.
   */
  function render_link($node, $values) {

    if (!node_clone_access_cloning($node)) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/{$node->nid}/clone/" . node_clone_get_token($node->nid);
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $method = variable_get('clone_method', 'prepopulate');

    $destination = drupal_get_destination();
    if ($method == 'prepopulate') {
      $this->options['alter']['query'] = $destination;
    }
    elseif (!empty($destination['destination'])) {
      $this->options['alter']['query']['node-clone-destination'] = $destination['destination'];
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('clone');
    return $text;
  }
}
