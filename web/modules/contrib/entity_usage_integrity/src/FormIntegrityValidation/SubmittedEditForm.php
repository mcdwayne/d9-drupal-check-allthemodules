<?php

namespace Drupal\entity_usage_integrity\FormIntegrityValidation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\entity_usage_integrity\Form\IntegritySettingsForm;
use Drupal\entity_usage_integrity\IntegrityValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide integrity validation on submitted content entity edit form.
 *
 * This alter displays warnings or errors, when entity edit form is saved
 * and there are invalid relations.
 *
 * If 'block' mode is selected, saving entity with broken usage relations
 * is forbidden. If 'warning' mode is selected, saving entity with broken
 * usage relations is allowed, but warnings will be displayed.
 *
 * @see IntegritySettingsForm::buildForm()
 */
final class SubmittedEditForm extends SubmittedFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(IntegrityValidator $integrity_validator, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($integrity_validator, $entity_type_manager, $messenger, $config_factory);
    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler')
    );
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
      return in_array($form_object->getOperation(), ['edit', 'default']) && !$form_object->getEntity()->isNew() && $this->getIntegrityValidationMode() == IntegritySettingsForm::BLOCK_MODE;
    }
    return FALSE;
  }

}
