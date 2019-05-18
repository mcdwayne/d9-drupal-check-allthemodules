<?php
/**
 * @file
 * Area handler for PM add content link.
 */

/**
 * Area handler for PM add content link.
 */
class pm_handler_area_addcontent extends views_handler_area {

  /**
   * Get this field's label.
   */
  public function option_definition() {
    $options = parent::option_definition();

    $options['node_type'] = array(
      'default' => NULL,
    );
    $options['redirect'] = array(
      'default' => 1,
      'bool' => TRUE,
    );
    return $options;
  }

  /**
   * Define options form.
   */
  public function options_form(&$form, &$form_state) {
    $form['node_type'] = array(
      '#type' => 'select',
      '#title' => t('Content type'),
      '#options' => node_type_get_names(),
      '#default_value' => $this->options['node_type'],
    );
    $form['redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect'),
      '#description' => t('If checked, the user will be redirected back to this view after saving the new content.'),
      '#default_value' => $this->options['redirect'],
    );
  }

  /**
   * Render the area.
   */
  public function render($empty = FALSE) {
    // Only show a link if the user has permissions for the destination.
    if (node_access('create', $this->options['node_type']) === TRUE) {
      $node_type_name = node_type_get_name($this->options['node_type']);

      $link_icon = pm_icon('application_add', '');
      $link_text = t('Add @node_type_name', array('@node_type_name' => $node_type_name));
      $link_path = 'node/add/' . $this->options['node_type'];

      $link_options = array(
        'html' => TRUE,
      );

      if ($this->options['redirect'] == TRUE) {
        $link_options['query'] = array('destination' => current_path());
      }

      return l($link_icon . $link_text, $link_path, $link_options);
    }
    else {
      return '';
    }
  }

}
