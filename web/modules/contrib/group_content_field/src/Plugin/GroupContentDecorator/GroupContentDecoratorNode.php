<?php
/**
 * Created by PhpStorm.
 * User: valerij
 * Date: 07.04.17
 * Time: 13:44
 */

namespace Drupal\group_content_field\Plugin\GroupContentDecorator;


use Drupal\group\Entity\GroupContent;
use Drupal\group_content_field\Plugin\GroupContentDecoratorBase;

/**
 * Class GroupContentDecoratorNode
 *
 * @GroupContentDecorator(
 *   id = "group_content_node",
 *   label = @Translation("Group node")
 * )
 */
class GroupContentDecoratorNode extends GroupContentDecoratorBase {

  /**
   * @var \Drupal\group\Plugin\GroupContentEnablerManager
   */
  protected $pluginManager;
  protected $groupType;

  public function __construct($configuration) {
    parent::__construct($configuration);
    $this->pluginManager= \Drupal::service('plugin.manager.group_content_enabler');
    $this->groupType = \Drupal::entityTypeManager()->getStorage('group_type');
  }

  /**
   * Additional plugin spec field settings.
   */
  function fieldStorageSettings() {
    $element = [];
    $plugins = $this->pluginManager->getInstalled($this->groupType->load($this->groupContentItem->getSetting('group_type')));
    $options = [];

    foreach ($plugins as $key => $plugin) {
      // Todo load all derivarives of group_node from plugin manager.
      if (strpos($key, 'group_node') !== FALSE) {
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

  public function getBuildProperties($parent_entity) {
    return [
      'type' => $this->groupContentItem->getSetting('plugin_enabler_id'),
      'entity_id' => $parent_entity->id(),
    ];
  }

}
