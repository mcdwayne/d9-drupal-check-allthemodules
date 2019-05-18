<?php

namespace Drupal\business_rules\Form;

use Drupal\business_rules\ActionListBuilder;
use Drupal\business_rules\ConditionListBuilder;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Condition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VariableForm.
 *
 * @package Drupal\business_rules\Form
 */
class VariableForm extends ItemForm {

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    $container = \Drupal::getContainer();

    return $container->get('plugin.manager.business_rules.variable');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['used_by_conditions']            = $this->getVariableUsedByDetailsBox('condition');
    $form['used_by_conditions']['#weight'] = 1101;

    $form['used_by_actions']            = $this->getVariableUsedByDetailsBox('action');
    $form['used_by_actions']['#weight'] = 1102;

    return $form;
  }

  /**
   * Show details box of items using this variable.
   *
   * @param string $item_type
   *   The item type: action|condition.
   *
   * @return array
   *   The render array.
   */
  public function getVariableUsedByDetailsBox($item_type) {
    /** @var \Drupal\business_rules\Entity\BusinessRulesItemBase $item */
    if ($item_type == 'condition') {
      $items       = Condition::loadMultiple();
      $entity_type = 'business_rules_condition';
      $box_title   = $this->t('Conditions using this variable');
      $storage     = $this->entityTypeManager->getStorage($entity_type);
    }
    elseif ($item_type == 'action') {
      $items       = Action::loadMultiple();
      $entity_type = 'business_rules_action';
      $box_title   = $this->t('Actions using this variable');
      $storage     = $this->entityTypeManager->getStorage($entity_type);
    }

    $used_by = [];
    $details = [];

    foreach ($items as $key => $item) {
      $variables = $item->getVariables();
      /** @var \Drupal\business_rules\VariableObject $variable */
      foreach ($variables->getVariables() as $variable) {
        if ($this->entity->id() == $variable->getId()) {
          $used_by[$key] = $item;
        }
      }
    }

    if (count($used_by)) {

      if ($item_type == 'condition') {
        $list = new ConditionListBuilder($item->getEntityType(), $storage);
      }
      elseif ($item_type == 'action') {
        $list = new ActionListBuilder($item->getEntityType(), $storage);
      }

      $details = [
        '#type'        => 'details',
        '#title'       => $box_title,
        '#collapsed'   => TRUE,
        '#collapsable' => TRUE,
      ];

      $header = $list->buildHeader();

      $rows = [];
      foreach ($used_by as $item) {
        $rows[] = $list->buildRow($item);
      }

      $details['used_by'] = [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
      ];
    }

    return $details;
  }

}
