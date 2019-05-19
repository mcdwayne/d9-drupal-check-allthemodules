<?php

namespace Drupal\views_add_button_group\Plugin\views_add_button;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\views_add_button\ViewsAddButtonInterface;

/**
 * Integrates Views Add Button with group entities.
 *
 * @ViewsAddButton(
 *   id = "views_add_button_group",
 *   label = @Translation("ViewsAddButtonGroup"),
 *   target_entity = "group"
 * )
 */
class ViewsAddButtonGroup extends PluginBase implements ViewsAddButtonInterface {

  /**
   * Plugin description.
   *
   * @return string
   *   A string description.
   */
  public function description() {
    return $this->t('Views Add Button URL Generator for Group entities');
  }

  /**
   * Generate the Add Button Url.
   *
   * @param string $entity_type
   *   Entity id as a machine name.
   * @param string $bundle
   *   The bundle string.
   * @param array $options
   *   Array of options to be used when building the Url, and Link.
   * @param string $context
   *   Entity context string, a comma-separated list of values.
   *
   * @return \Drupal\Core\Url
   *   The Url to use in the Add Button link.
   */
  public static function generateUrl($entity_type, $bundle, array $options, $context = '') {

    // Create URL from the data above.
    $url = Url::fromRoute('entity.group.add_form', ['group_type' => $bundle], $options);

    return $url;
  }

}
