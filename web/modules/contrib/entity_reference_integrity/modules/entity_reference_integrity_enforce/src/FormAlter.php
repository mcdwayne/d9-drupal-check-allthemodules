<?php

namespace Drupal\entity_reference_integrity_enforce;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_reference_integrity\EntityReferenceDependencyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter entity delete forms to provide some warning deletes will fail.
 */
class FormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The dependency manager.
   *
   * @var \Drupal\entity_reference_integrity\EntityReferenceDependencyManagerInterface
   */
  protected $dependencyManager;

  /**
   * The entity type IDs protection is enabled for.
   *
   * @var array
   */
  protected $enabledEntityTypeIds;

  /**
   * Create a DeleteFormAlter object.
   */
  public function __construct(EntityReferenceDependencyManagerInterface $calculator, $enabled_entity_type_ids) {
    $this->dependencyManager = $calculator;
    $this->enabledEntityTypeIds = $enabled_entity_type_ids;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_reference_integrity.dependency_manager'),
      $container->get('config.factory')->get('entity_reference_integrity_enforce.settings')->get('enabled_entity_type_ids')
    );
  }

  /**
   * Implements hook_form_alter().
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    if (!$this->isDeleteForm($form_object)) {
      return;
    }

    $entity = $form_object->getEntity();

    if (in_array($entity->getEntityTypeId(), $this->enabledEntityTypeIds, TRUE) && $this->dependencyManager->hasDependents($entity)) {
      $referencing_entities = $this->dependencyManager->getDependentEntities($entity);

      unset($form['actions']['submit']);
      unset($form['description']);

      $form['referencing_entities_list'] = [
        '#weight' => -10,
        'explanation' => [
          '#prefix' => '<div  class="messages messages--error">',
          '#markup' => $this->t('You can not delete this as it is being referenced by another entity.'),
          '#suffix' => '</div>',
        ],
        'entities' => $this->buildReferencingEntitiesList($referencing_entities),
        '#suffix' => '<br/>',
      ];
    }
  }

  /**
   * Build a UI for listing the referencing entities.
   *
   * @param array $referencing_entities
   *   An array of referencing entities.
   *
   * @return array
   *   A renderable array of referencing entities.
   */
  protected function buildReferencingEntitiesList(array $referencing_entities) {
    $build = [];
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    foreach ($referencing_entities as $entity_type_id => $entities) {
      $build[$entity_type_id]['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => reset($entities)->getEntityType()->getLabel(),
      ];
      $build[$entity_type_id]['list'] = [
        '#theme' => 'item_list',
        '#items' => [],
      ];
      foreach ($entities as $entity) {
        $build[$entity_type_id]['list']['#items'][] = $entity->hasLinkTemplate('canonical') ? $entity->toLink() : $entity->label();
      }
    }
    return $build;
  }

  /**
   * Check if a given generic form is applicable to be altered.
   *
   * @param mixed $form_object
   *   The form object.
   *
   * @return bool
   *   If alteration applies.
   */
  protected function isDeleteForm($form_object) {
    return $form_object instanceof EntityFormInterface && $form_object->getOperation() === 'delete';
  }

}
