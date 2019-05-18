<?php

namespace Drupal\br_group\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;

/**
 * Class AddNodeToGroupAction.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "add_node_to_group",
 *   label = @Translation("Group: Add Node entity to a group"),
 *   group = @Translation("Group"),
 *   description = @Translation("Add an Node entity to a group on Group module."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class AddNodeToGroupAction extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['node_id'] = [
      '#type' => 'textfield',
      '#title' => t('Node Id'),
      '#required' => TRUE,
      '#description' => t('The node id to be added to the group. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('node_id'),
    ];

    $settings['group_id'] = [
      '#type' => 'textfield',
      '#title' => t('Group Id'),
      '#required' => TRUE,
      '#description' => t('The group id to add the node. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('group_id'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $variables = $event->getArgument('variables');
    $group_id = $action->getSettings('group_id');
    $group_id = $this->processVariables($group_id, $variables);
    $node_id = $action->getSettings('node_id');
    $node_id = $this->processVariables($node_id, $variables);

    $node = Node::load($node_id);
    $group = Group::load($group_id);
    if ($node instanceof Node) {
      $type = 'group_node:' . $node->getType();
      $current_node = $group->getContent($type, ['entity_id' => $node_id]);
      if (!count($current_node)) {
        $group->addContent($node, $type);

        $result = [
          '#type' => 'markup',
          '#markup' => t('Node %node has been added to group %group.', [
            '%node' => $node->label(),
            '%group' => $group->label(),
          ]),
        ];
      }
      else {
        $result = [
          '#type' => 'markup',
          '#markup' => t('Node %node is already on group %group.', [
            '%node' => $node->label(),
            '%group' => $group->label(),
          ]),
        ];
      }
    }
    else {
      $result = [
        '#type' => 'markup',
        '#markup' => t('Node id %node could not be added to group %group.', [
          '%node' => $node_id,
          '%group' => $group->label(),
        ]),
      ];
    }

    return $result;
  }

}
