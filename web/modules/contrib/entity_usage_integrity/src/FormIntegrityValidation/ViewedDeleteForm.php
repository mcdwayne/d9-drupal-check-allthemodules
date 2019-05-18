<?php

namespace Drupal\entity_usage_integrity\FormIntegrityValidation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
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
 * Provide integrity validation on viewed content entity delete form.
 *
 * If 'block' mode is selected, saving entity with broken usage relations
 * is forbidden. Submit button is disabled and valid integrity relations
 * are displayed. If 'warning' mode is selected, warnings are displayed,
 * but user can delete the entity.
 *
 * @see IntegritySettingsForm::buildForm()
 */
final class ViewedDeleteForm implements ContainerInjectionInterface {
  use StringTranslationTrait;
  use IntegrityValidationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Create ViewedDeleteForm object.
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
      ->setValidationContext(IntegrityValidationContext::DELETE_FORM_VIEW)
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
   * Prevent entity deletion, if there are any active entity usage sources.
   *
   * Check if given entity has any active entity usage relations. If yes,
   * disable delete button on the form and display message to the user
   * about sources related to given entity.
   *
   * This only prevent delete operation, when fired via delete form.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see EntityDelete::validate()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if ($this->isApplicable($form_state)) {
      $target_entity = $form_state->getFormObject()->getEntity();
      // On delete form, we would like to display errors and block delete
      // operation by validating default revision entity usage relations.
      if ($target_entity->getEntityType()->isRevisionable() && !$target_entity->isDefaultRevision()) {
        $target_entity = $this->entityTypeManager->getStorage($target_entity->getEntityTypeId())->load($target_entity->id());
      }

      $relations = $this->getValidatedUsageRelations($target_entity);

      if ($relations->hasRelationsWithStatus('valid')) {
        if ($this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE) {
          $form['actions']['submit']['#disabled'] = TRUE;
          $this->messenger->addError($this->getMessage($relations));
        }
        else {
          $this->messenger->addWarning($this->getMessage($relations));
        }
      }
    }
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
  protected static function isApplicable(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface) {
      return $form_object->getOperation() === 'delete';
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
    $items = $relation_collections
      ->getRelationCollectionWithStatus('valid')
      ->getRelatedEntitiesElement('target');

    if (empty($items)) {
      return [];
    }

    if ($this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE) {
      $message_header = $this->t("You are about to delete this content but it's still being referenced from:");
    }
    else {
      $message_header = $this->t("You are about to delete this content but it's still being referenced from:");
    }

    return [
      '#markup' => $message_header,
      'list' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
      '#suffix' => '<p>' . $this->getMessageFooter() . '</p>',
    ];
  }

  /**
   * Get message displayed after list of related entities.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message to display before list of related entities.
   */
  protected function getMessageFooter() {
    if ($this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE) {
      return $this->t('You have to remove these references first before continuing.');
    }
    else {
      return $this->t('You might want to remove these references first before continuing.');
    }
  }

}
