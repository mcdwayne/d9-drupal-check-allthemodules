<?php

/**
 * @file
 * Contains \Drupal\content_callback_examples\Plugin\ContentCallback\Alter.
 */

namespace Drupal\content_callback_examples\Plugin\ContentCallback;

use Drupal\content_callback\Plugin\ContentCallback\PluginBase;

/**
 * Alternative implementation of the default views content callback.
 *
 * See also content_callback_examples_content_callback_info_alter in the module
 * file.
 */
class Alter extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Fetch the definition
    $definition = $this->getPluginDefinition();
    $view_name = $definition['view_name'];
    $view_display = $definition['view_display'];

    $view = views_get_view($view_name);
    $view->setDisplay($view_display);

    if ($this->hasOption('limit')) {
      $limit = $this->getOption('limit');
      if (!empty($limit)) {
        $view->setItemsPerPage($limit);
      }
    }

    return $view->preview();
  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm(array &$form, array $saved_options) {
    $form['limit'] = array(
      '#type' => 'textfield',
      '#title' => 'Items per page',
      '#default_value' => isset($saved_options['limit']) ? $saved_options['limit'] : '',
    );
  }

}
