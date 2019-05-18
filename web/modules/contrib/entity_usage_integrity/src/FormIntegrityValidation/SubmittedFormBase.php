<?php

namespace Drupal\entity_usage_integrity\FormIntegrityValidation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections;
use Drupal\entity_usage_integrity\Form\IntegritySettingsForm;
use Drupal\entity_usage_integrity\IntegrityValidationContext;
use Drupal\entity_usage_integrity\IntegrityValidationTrait;
use Drupal\entity_usage_integrity\IntegrityValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base to provide integrity validation on submitted content entity edit form.
 *
 * @see IntegritySettingsForm::buildForm()
 */
abstract class SubmittedFormBase implements ContainerInjectionInterface {
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
   * Create SubmittedFormBase object.
   *
   * @param \Drupal\entity_usage_integrity\IntegrityValidator $integrity_validator
   *   The entity usage integrity validator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(IntegrityValidator $integrity_validator, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    // Integrity validation trait init.
    $this
      ->setIntegrityValidator($integrity_validator)
      ->setValidationContext(IntegrityValidationContext::ENTITY_SAVE)
      ->setIntegrityConfig($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_usage_integrity.validator'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  /**
   * Display warning on entity edit form for entity usage invalid relations.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    if ($this->isApplicable($form_state)) {
      // Get an updated entity object based upon the submitted form values.
      $entity = $this->buildEntity($form, $form_state);

      if (!$entity->getEntityType()->isRevisionable() || $entity->isDefaultRevision()) {
        $relations = $this->getValidatedUsageRelations($entity);

        if ($relations->hasRelationsWithStatus('invalid')) {
          $messages = $this->getMessage($relations);
          if ($this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE) {
            foreach ($messages as $key => $message) {
              $form_state->setErrorByName('entity_usage_integrity_' . $key, $message);
            }
          }
          else {
            foreach ($messages as $message) {
              $this->messenger->addWarning($message);
            }
          }
        }
      }
    }
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
      if ($this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE) {
        return $this->t('This item is trying to reference not allowed archived items:');
      }
      else {
        return $this->t('The item is referencing archived items:');
      }
    }
    else {
      if ($this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE) {
        return $this->t('The item cannot be archived. It is referenced by published items:');
      }
      else {
        return $this->t('The item has been archived, but it is referenced by published items:');
      }
    }
  }

  /**
   * Builds an updated entity object based upon the submitted form values.
   *
   * For building the updated entity object the form's entity is cloned and
   * the submitted form values are copied to entity properties. The form's
   * entity remains unchanged.
   *
   * To check, if new relations will be valid, we have to apply form values
   * to current entity first and then we will be able validate integrity.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The current entity with new values from form.
   */
  abstract protected function buildEntity(array &$form, FormStateInterface $form_state);

  /**
   * Check if a given generic form is applicable to be altered.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form object.
   *
   * @return bool
   *   TRUE if alteration applies, FALSE otherwise.
   */
  abstract protected function isApplicable(FormStateInterface $form_state);

}
