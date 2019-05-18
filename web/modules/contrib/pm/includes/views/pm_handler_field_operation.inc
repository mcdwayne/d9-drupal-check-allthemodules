<?php
/**
 * @file
 * Field handler for PM operation (edit and delete) links.
 */

/**
 * Field handler for PM operation (edit and delete) links.
 */
class pm_handler_field_operation extends views_handler_field_node_link {
  /**
   * Defines views field options.
   */
  public function option_definition() {
    $options = parent::option_definition();
    $options['display_icons'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Defines views field option form.
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['display_icons'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display icon links'),
      '#description' => t('Display a icon or a text link for edit, delete and view node links.'),
      '#default_value' => $this->options['display_icons'],
    );
  }

  /**
   * Renders field to show icon.
   */
  public function render($values) {
    if ($node = $this->get_value($values)) {
      $id = $node->nid;

      if ($this->options['display_icons']) {
        $value = "";
        $value .= pm_icon_edit_node($node, $_GET);
        if (!empty($value)) {
          $value .= '&nbsp;';
        }
        $value .= pm_icon_delete_node($node, $_GET);

        return $value;
      }
      else {
        $links = array();
        if ($link = $this->create_link_definition(t('edit'), "node/$id/edit")) {
          $links[] = $link;
        }
        if ($link = $this->create_link_definition(t('delete'), "node/$id/delete")) {
          $links[] = $link;
        }
        if ($link = $this->create_link_definition(t('view'), "node/$id")) {
          $links[] = $link;
        }
        $vars = array(
          'title' => t('Quick Links'),
          'links' => $links,
        );
        return theme('links', $vars);
      }
    }
  }

  /**
   * Check if path is valid and create links. Returns false otherwise.
   */
  public function create_link_definition($title, $path) {
    if (drupal_valid_path($path)) {
      return array(
        'title' => $title,
        'href' => $path,
      );
    }
    return FALSE;
  }

}
