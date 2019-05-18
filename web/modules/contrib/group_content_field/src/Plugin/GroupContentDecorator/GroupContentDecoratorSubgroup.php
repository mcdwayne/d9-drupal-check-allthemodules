<?php

namespace Drupal\group_content_field\Plugin\GroupContentDecorator;


use Drupal\Core\Annotation\Translation;
use Drupal\group_content_field\Annotation\GroupContentDecorator;

/**
 * Class GroupContentDecoratorNode
 *
 * @GroupContentDecorator(
 *   id = "group_content_subgroup",
 *   label = @Translation("Subgroup")
 * )
 */
class GroupContentDecoratorSubgroup extends GroupContentDecoratorNode {
  /**
   * @inheritdoc
   */
  function fieldStorageSettings() {
    $plugins = $this->pluginManager->getInstalled($this->groupType->load($this->groupContentItem->getSetting('group_type')));
    $element = [];
    $options = [];

    foreach ($plugins as $key => $plugin) {
      // Todo load all derivarives of subgroup from plugin manager.
      if (strpos($key, 'subgroup') !== FALSE) {
        $options[$plugin->getContentTypeConfigId()] = $plugin->getLabel();
      }
    }

    $element['plugin_enabler_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin enabler'),
      '#options' => $options,
      '#default_value' => $this->groupContentItem->getSetting('plugin_enabler_id'),
      '#required' => TRUE,
    ];

    return $element;
  }

}
