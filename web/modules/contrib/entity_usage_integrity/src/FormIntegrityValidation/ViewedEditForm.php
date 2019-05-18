<?php

namespace Drupal\entity_usage_integrity\FormIntegrityValidation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections;
use Drupal\entity_usage_integrity\IntegrityValidationContext;
use Drupal\entity_usage_integrity\IntegrityValidationTrait;
use Drupal\entity_usage_integrity\IntegrityValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provide integrity validation on viewed content entity edit form.
 *
 * This alter displays warnings, when entity edit form is opened and there
 * are invalid relations. User can fix them based on displayed information.
 */
final class ViewedEditForm implements ContainerInjectionInterface {
  use StringTranslationTrait;
  use IntegrityValidationTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Create EntityUsageIntegrityForm object.
   *
   * @param \Drupal\entity_usage_integrity\IntegrityValidator $integrity_validator
   *   The entity usage integrity validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct(IntegrityValidator $integrity_validator, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, RequestStack $request) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->request = $request->getCurrentRequest();
    // Integrity validation trait init.
    $this
      ->setIntegrityValidator($integrity_validator)
      ->setValidationContext(IntegrityValidationContext::EDIT_FORM_VIEW);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_usage_integrity.validator'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('request_stack')
    );
  }

  /**
   * Display warning on entity edit form for entity usage invalid relations.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if ($this->isApplicable($form_state)) {
      $entity = $form_state->getFormObject()->getEntity();
      if (!$entity->getEntityType()->isRevisionable() || $this->isDefaultRevision($entity)) {
        $relations = $this->getValidatedUsageRelations($entity);
        if ($relations->hasRelationsWithStatus('invalid')) {
          $messages = $this->getMessage($relations);
          foreach ($messages as $message) {
            $this->messenger->addWarning($message);
          }
        }
      }
    }
  }

  /**
   * Check if current entity edit form refers to default entity revision.
   *
   * As $entity->isDefaultRevision() may return FALSE, if entity edit form
   * is opened and default revision is displayed, we have to do extra check
   * to verify if form refers to current revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if current entity form refers to default entity revision,
   *   FALSE otherwise.
   */
  protected function isDefaultRevision(EntityInterface $entity) {
    $default_revision = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id());
    return $default_revision->getRevisionId() == $entity->getRevisionId();
  }

  /**
   * Check if a given generic form is applicable to be altered.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form object.
   *
   * @return bool
   *   TRUE if alteration applies, FALSE otherwise.
   */
  protected function isApplicable(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface) {
      return in_array($form_object->getOperation(), ['edit', 'default']) && $this->request->getMethod() === "GET" && !$form_state->getFormObject()->getEntity()->isNew();
    }
    return FALSE;
  }

  /**
   * Get message to display.
   *
   * @param \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections $relation_collections
   *   Entity usage relation collections.
   *
   * @return array
   *   A render array.
   */
  protected function getMessage(RelationCollections $relation_collections) {
    $element = [];

    foreach (['source', 'target'] as $relation_type) {
      $items = $relation_collections
        ->getRelationCollectionWithStatus('invalid')
        ->getRelatedEntitiesElement($relation_type);

      if (empty($items)) {
        continue;
      }

      $element[] = [
        '#markup' => $this->getMessageHeader($relation_type),
        'list' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ];
    }

    return $element;
  }

  /**
   * Get message displayed before list of related entities.
   *
   * @param string $relation_type
   *   Describes if current entity is 'source' or 'target' of relation.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message to display before list of related entities.
   */
  protected function getMessageHeader($relation_type) {
    if ($relation_type == 'source') {
      return $this->t('The item has references to archived items:');
    }
    else {
      return $this->t('The item is archived, but it is referenced by published items:');
    }
  }

}
