<?php

namespace Drupal\flexiform\FormEntity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Interface for form entity plugins.
 */
interface FlexiformFormEntityInterface extends ContextAwarePluginInterface {

  /**
   * Get the context.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The form entity context.
   */
  public function getFormEntityContext();

  /**
   * Get the context definition.
   */
  public function getFormEntityContextDefinition();

  /**
   * Get the label for this plugin.
   */
  public function getLabel();

  /**
   * Get the entity type.
   */
  public function getEntityType();

  /**
   * Get the bundle.
   */
  public function getBundle();

  /**
   * Prepare a configuration form.
   */
  public function configurationForm(array $form, FormStateInterface $form_state);

  /**
   * Validate the configuration form.
   */
  public function configurationFormValidate(array $form, FormStateInterface $form_state);

  /**
   * Submit the configuration form.
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state);

}
