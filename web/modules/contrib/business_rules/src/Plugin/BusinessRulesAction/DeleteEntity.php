<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DeleteEntity.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "delete_entity",
 *   label = @Translation("Delete an entity"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Delete an entity."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class DeleteEntity extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['value'] = [
      '#type'          => 'textfield',
      '#title'         => t('Value'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('value'),
      '#description'   => t('The value to be compared against the field.
        <br>All entities which the field equals to this value will be deleted.'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    // Get settings.
    $entity_type = $action->getTargetEntityType();
    $bundle      = $action->getTargetBundle();
    $field       = $action->getSettings('field');
    $value       = $action->getSettings('value');
    $value       = $this->processVariables($value, $event->getArgument('variables'));

    // Load entities ids to delete.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::getContainer()->get('entity_type.manager')->getStorage($entity_type)->getQuery()
      ->condition($field, $value);
    $ids = $query->execute();

    // Delete entities.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $entityManager */
    $entityManager = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities      = $entityManager->loadMultiple($ids);
    foreach ($entities as $key => $entity) {
      if ($entity->bundle() !=  $bundle) {
        unset($entities[$key]);
      }
    }
    $entityManager->delete($entities);

    $result = [
      '#type'   => 'markup',
      '#markup' => t('Entity: %entity, Bundle: %bundle, Id(s): (%id) has been deleted.', [
        '%entity' => $entity_type,
        '%bundle' => $bundle,
        '%id'     => implode(',', $ids),
      ]),
    ];

    return $result;
  }

}
