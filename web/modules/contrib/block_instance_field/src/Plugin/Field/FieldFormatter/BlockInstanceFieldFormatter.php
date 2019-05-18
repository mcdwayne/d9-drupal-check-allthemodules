<?php

namespace Drupal\block_instance_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'basic_string' formatter.
 *
 * @FieldFormatter(
 *   id = "block_instance_field",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "block_instance_field"
 *   }
 * )
 */
class BlockInstanceFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $block_manager = \Drupal::service('plugin.manager.block');

    foreach ($items as $delta => $item) {
      $config = [];
      $plugin = $block_manager->createInstance($item->target_id, $config);

      if ($item->configuration) {
        $configuration = json_decode($item->configuration, TRUE);
        $plugin->setConfiguration($configuration);
      }

      $plugin_id = $plugin->getPluginId();
      $base_id = $plugin->getBaseId();
      $derivative_id = $plugin->getDerivativeId();
      $configuration = $plugin->getConfiguration();

      $content = $plugin->build();

      if (isset($content['#type']) && $content['#type'] == 'form' || isset($content['right']['form']) || isset($content['form'])) {
        $route_object = \Drupal::service('current_route_match')->getRouteObject();
        $defaults = $route_object->getDefaults();

        if (isset($defaults['_entity_form']) || isset($defaults['_form'])) {
          $content = [
            'title' => [
              '#markup' => isset($configuration['label']) && $configuration['label'] ? '<h2>' . $configuration['label'] . '</h2>' : '',
            ],
            'sub_title' => [
              '#markup' => isset($configuration['block_sub_title']) && $configuration['block_sub_title'] ? '<h3>' . $configuration['block_sub_title'] . '</h3>' : '',
            ],
            '#markup' => '<p>Formulieren zijn niet beschikbaar in voorbeeldweergave.</p>',
          ];

          $configuration['label'] = '';
        }
      }

      $elements[$delta] = [
        '#theme' => 'block',
        '#cache' => FALSE,
        '#attributes' => [],
        '#weight' => $delta,
        '#configuration' => $configuration,
        '#plugin_id' => $plugin_id,
        '#base_plugin_id' => $base_id,
        '#derivative_plugin_id' => $derivative_id,
        'content' => $content,
      ];
    }

    return $elements;
  }

}
