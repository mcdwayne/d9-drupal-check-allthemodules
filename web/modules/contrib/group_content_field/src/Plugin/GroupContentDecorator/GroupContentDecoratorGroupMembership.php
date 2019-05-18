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
 *   id = "group_content_group_membership",
 *   label = @Translation("Group membership")
 * )
 */
class GroupContentDecoratorGroupMembership extends GroupContentDecoratorBase {
  protected $groupRole;

  public function __construct($configuration) {
    parent::__construct($configuration);
    $this->groupRole = \Drupal::entityTypeManager()->getStorage('group_role');
  }

  public function getBuildProperties($parent_entity) {
    return [
      'type' => $this->groupContentItem->getSetting('group_type') . '-group_membership',
      'entity_id' => $parent_entity->id(),
      'group_roles' => $this->groupContentItem->getSetting('group_roles'),
    ];
  }

  /**
   * Additional plugin spec field settings.
   */
  function fieldStorageSettings() {
    $element = [];
    $group_roles = $this->groupRole->loadMultiple();
    $options = [];

    foreach ($group_roles as $group_role) {
      $options[$group_role->id()] = ucfirst($group_role->getGroupTypeId()) . ': ' . $group_role->label();
    }

    $element['group_roles'] = [
      '#type' => 'radios',
      '#title' => $this->t('Group type'),
      '#options' => $options,
      '#default_value' => $this->groupContentItem->getSetting('group_roles'),
      '#required' => TRUE,
    ];

    return $element;
  }
}
