<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SaveEntityVariableAction.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "save_entity_variable",
 *   label = @Translation("Save entity variable"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Save a entity stored in a variable."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = FALSE,
 * )
 */
class SaveEntityVariableAction extends BusinessRulesActionPlugin {

  /**
   * Delete all expirable key value pairs.
   */
  public function __destruct() {
    $key_value = $this->util->getKeyValueExpirable('save_entity_variable');
    $key_value->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings = [];

    if (!$item->isNew()) {
      $settings['variable'] = [
        '#type'          => 'select',
        '#title'         => t('Entity variable'),
        '#required'      => TRUE,
        '#description'   => t('Entity variable to be saved. Remember to create actions to fill the entity variable fields and execute them before save the entity.'),
        '#options'       => $this->getAvailableEmptyVariables($item),
        '#default_value' => empty($item->getSettings('variable')) ? '' : $item->getSettings('variable'),
      ];
    }

    return $settings;
  }

  /**
   * Get the available empty variables for the context.
   *
   * @param \Drupal\business_rules\Entity\Action $item
   *   The action.
   *
   * @return array
   *   Array of available variables.
   */
  protected function getAvailableEmptyVariables(Action $item) {
    $variables = Variable::loadMultiple();
    $output    = [];

    /** @var \Drupal\business_rules\Entity\Variable $variable */
    foreach ($variables as $variable) {
      if ($item->getTargetEntityType() == $variable->getTargetEntityType() &&
        $item->getTargetBundle() == $variable->getTargetBundle() &&
        $variable->getType() == 'entity_empty_variable'
      ) {
        $output[$variable->id()] = $variable->label() . ' [' . $variable->id() . ']';
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    /** @var \Drupal\business_rules\VariablesSet $variables */
    /** @var \Drupal\Core\Entity\Entity $entity */
    $variables = $event->getArgument('variables');
    $key_value = $this->util->getKeyValueExpirable('save_entity_variable');
    if ($variables->count()) {
      $variable = $variables->getVariable($action->getSettings('variable'));
      $entity = $variable ? $variable->getValue() : FALSE;

      if ($entity instanceof Entity) {

        // Prevent infinite calls regarding the dispatched entity events such as
        // save / presave, etc.
        $uuid = $entity->uuid->value;
        $saved_uuid = $key_value->get($uuid);

        if ($entity->uuid->getValue() !== $saved_uuid) {
          $key_value->set($uuid, $uuid);
          $entity->save();

          $result = [
            '#type' => 'markup',
            '#markup' => t('Entity: %entity on variable: %variable saved.', [
              '%entity' => $entity->getEntityTypeId(),
              '%variable' => $action->getSettings('variable'),
            ]),
          ];

          return $result;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet = parent::getVariables($item);
    $variable    = new VariableObject($item->getSettings('variable'));
    $variableSet->append($variable);

    return $variableSet;
  }

}
