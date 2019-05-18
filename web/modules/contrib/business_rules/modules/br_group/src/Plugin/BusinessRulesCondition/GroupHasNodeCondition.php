<?php

namespace Drupal\br_group\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;

/**
 * Class GroupHasNodeCondition.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "group_has_node",
 *   label = @Translation("Check if a node is in a group"),
 *   group = @Translation("Group"),
 *   description = @Translation("Check if a node is content of a group."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class GroupHasNodeCondition extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['node_id'] = [
      '#type' => 'textfield',
      '#title' => t('Node Id'),
      '#required' => TRUE,
      '#description' => t('The node id. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('node_id'),
    ];

    $settings['group_id'] = [
      '#type' => 'textfield',
      '#title' => t('Group Id'),
      '#required' => TRUE,
      '#description' => t('The group id. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('group_id'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $variables = $event->getArgument('variables');
    $group_id = $condition->getSettings('group_id');
    $group_id = $this->processVariables($group_id, $variables);
    $node_id = $condition->getSettings('node_id');
    $node_id = $this->processVariables($node_id, $variables);

    $node = Node::load($node_id);
    if (!$node instanceof Node) {
      return FALSE;
    }
    $group = Group::load($group_id);
    $type = 'group_node:' . $node->getType();
    $content = $group->getContent($type, ['entity_id' => $node_id]);
    if (count($content)) {
      $result = TRUE;
    }
    else {
      $result = FALSE;
    }

    return $result;
  }

}
