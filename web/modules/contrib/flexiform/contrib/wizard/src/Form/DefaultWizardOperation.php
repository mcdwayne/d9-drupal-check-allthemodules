<?php

namespace Drupal\flexiform_wizard\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default class for flexiform wizard operations.
 */
class DefaultWizardOperation extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The wizard configuration.
   *
   * @var \Drupal\flexiform_wizard\Entity\Wizard
   */
  protected $wizardConfig = NULL;

  /**
   * The form step.
   *
   * @var string
   */
  protected $step = '';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Construct a new DefaultWizardOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the operation form display.
   *
   * @return \Drupal\flexiform\FlexiformEntityFormDisplay
   *   The form display.
   */
  public function getFormDisplay() {
    $id = 'flexiform_wizard.' . $this->wizardConfig->id() . '.' . $this->step;
    $display = $this->entityTypeManager->getStorage('entity_form_display')->load($id);
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $this->getFormDisplay()->buildAdvancedForm($cached_values['entities'], $form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
  }

}
