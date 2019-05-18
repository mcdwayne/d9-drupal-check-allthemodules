<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PublishContent.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "publish_entity",
 *   label = @Translation("Publish an entity"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Publish an entity."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class PublishContent extends BusinessRulesActionPlugin {

  /**
   * Delete all expirable key value pairs.
   */
  public function __destruct() {
    $key_value = \Drupal::keyValueExpirable('business_rules.publish_entity');
    $key_value->deleteAll();
  }

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
        <br>All entities which the field equals to this value will be published.'),
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

    // Load entities ids to Publish.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query_service */
    /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
    $query_service = \Drupal::getContainer()->get('entity.query');
    $query         = $query_service->get($entity_type);
    $query->condition('type', $bundle);
    $query->condition($field, $value);
    $ids = $query->execute();

    // Publish entities.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $entityManager */
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entityManager = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities      = $entityManager->loadMultiple($ids);
    $key_value     = \Drupal::keyValueExpirable('business_rules.publish_entity');
    foreach ($entities as $entity) {
      $unpublished_id = $key_value->get($entity->id());
      // Prevent infinite calls regarding the dispatched entity events such as
      // save / presave, etc.
      if ($unpublished_id != $entity->id()) {
        $key_value->set($entity->id(), $entity->id());
        $entity->status->setValue(1);
        $entity->save();
      }
    }

    $result = [
      '#type'   => 'markup',
      '#markup' => t('Publish entities with ids %ids.', ['%ids' => implode(',', $ids)]),
    ];

    return $result;
  }

}
