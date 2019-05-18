<?php

namespace Drupal\entity_usage_integrity\FormIntegrityValidation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
 * Validate entity usage integrity, when content moderation select will change.
 *
 * Add ajax event which will validate entity usage integrity on  change
 * of content moderation state on entity edit form. It displays validation
 * errors in dialog box or nothing if no validation errros.
 *
 * This works only in 'warning mode'.
 */
class ModerationStateChangeConfirmDialog implements ContainerInjectionInterface {
  use StringTranslationTrait;
  use IntegrityValidationTrait;
  use DependencySerializationTrait;

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
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct(IntegrityValidator $integrity_validator, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info, ModerationInformationInterface $moderation_information = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    // Integrity validation trait init.
    $this
      ->setIntegrityValidator($integrity_validator)
      ->setValidationContext(IntegrityValidationContext::ENTITY_SAVE)
      ->setIntegrityConfig($config_factory);
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_usage_integrity.validator'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('content_moderation.moderation_information', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * Attach ajax callback to content moderation select list field.
   *
   * When user wants to change state of content moderation, we have to
   * do integrity validation to detect any potential problems. If we are
   * working in 'warning' mode, it has to happen before click on submit button.
   * Then, user will know consequences of change of state, before he will
   * click to Submit button. It is solved like ajax callback, when user will
   * change state on content edit form.
   *
   * This is applicable only to 'warning mode'.
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
      $form['moderation_state']['widget'][0]['state']['#ajax'] = [
        'callback' => [$this, 'modalDialogAjax'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Checking references'),
        ],
      ];
    }
  }

  /**
   * Generate modal dialog with integrity errors and return as ajax response.
   *
   * Get validation results and return:
   *  - modal dialog with relation that will be broken after save with
   *    chosen moderation state, if any found,
   *  - empty response, if everything will go well, and no validation errors
   *    found for new moderation state.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response containing modal dialog with relations that will be broken
   *   if entity will be saved with selected content moderation state
   *   or empty ajax response if no errors found.
   */
  public function modalDialogAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // TODO consider to add extra check, if new moderation state will change
    //   default revision. E.g. 'draft' will not do that, and there is no
    //   sense to validate now for it. But 'archive' will change it, so
    //   we have to display warning to the user. Consider if this assumption
    //   is correct.
    //   Consider also, that now validation works like there will be no
    //   invalid results returned in cases like 'draft', so this call
    //   well... makes no sense in that case, as it always return that all
    //   is valid.

    // Get an updated entity object based upon the submitted form values.
    $entity = $this->buildEntity($form, $form_state);

    if (!$entity->getEntityType()->isRevisionable() || $entity->isDefaultRevision()) {
      $relations = $this->getValidatedUsageRelations($entity);
      if ($relations->hasRelationsWithStatus('invalid')) {
        $response->addCommand(new OpenModalDialogCommand($this->t('Warning'), $this->getMessage($relations) + [
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
            ],
          ],
        ], ['width' => '50%']));
      }
    }
    // TODO Consider to add in future something like ok checkbox to inform
    //   that validation check was done and all is good.

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntity(array &$form, FormStateInterface $form_state) {
    // As ::isApplicable() checks if form_object is instance of
    // ContentEntityFormInterface, no need for extra check.
    return $form_state->getFormObject()->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface) {
      return in_array($form_object->getOperation(), ['edit', 'default']) && !$form_object->getEntity()->isNew() && $this->moderationInformation !== NULL && $this->moderationInformation->isModeratedEntity($form_object->getEntity()) && $this->getIntegrityValidationMode() == IntegritySettingsForm::WARNING_MODE;
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
        '#markup' => '<p>' . $this->getMessageHeader($relation_type) . '</p>',
        'list' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
        '#suffix' => '<p>' . $this->getMessageFooter() . '</p>',
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
      return $this->t('The item is referencing archived items:');
    }
    else {
      return $this->t("You are about to unpublish this content but it's still being referenced from:");
    }
  }

  /**
   * Get message displayed after list of related entities.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message to display before list of related entities.
   */
  protected function getMessageFooter() {
    return $this->t('You might want to remove these references first before continuing.');
  }

}
