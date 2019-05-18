<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeMainConfiguration.
 */
class KonamicodeMainConfiguration extends ConfigFormBase {

  /**
   * An array with all the callable classes.
   *
   * @var array
   */
  protected $classesList;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'konamicode.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'konamicode_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('konamicode.configuration');

    $form['konamicode_info'] = [
      '#markup' => $this->t('Set up certain actions to be taken when specific codes are entered. One example is to spam the user with images when the Konami Code is entered. Select which actions you would like to have occur, what key codes they require, as well as the configuration options for each action.'),
    ];

    $form['konamicode_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Allows you to completely disable the Konami Code Module disregarding the separate Action statuses.'),
      '#default_value' => $config->get('konamicode_enabled'),
    ];

    // TODO: Add field to only allow execution on specific urls. Maybe a negate
    // option or include/exclude field.
    // Vertical tabs for all the actions.
    $form['konamicode_action_settings'] = [
      '#type' => 'vertical_tabs',
      // Change to image spam.
      '#default_tab' => 'konamicode_action_redirect',
    ];

    // Loop over all the actions and invoke the build function to build the
    // actual form.
    $actions = $this->getAllActions();
    foreach ($actions as $action) {
      $form += $action->buildForm($form, $form_state);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Loop over all the actions and invoke the validateForm function.
    $actions = $this->getAllActions();
    foreach ($actions as $action) {
      $action->validateForm($form, $form_state);
    }

    // Make sure to call the parent.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set('konamicode_enabled', $form_state->getValue('konamicode_enabled'))
      ->save();

    // Loop over all the actions and trigger the submitForm function.
    $actions = $this->getAllActions();
    foreach ($actions as $action) {
      $action->submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Function that will maintain and return all the actions.
   *
   * @return array
   *   An array of Action classes that extend the Action Base class.
   */
  public function getAllActions() {
    return [
      new KonamicodeActionRedirectConfiguration($this->configFactory),
      new KonamicodeActionAlertConfiguration($this->configFactory),
      new KonamicodeActionRaptorizeConfiguration($this->configFactory),
      new KonamicodeActionFlipTextConfiguration($this->configFactory),
      new KonamicodeActionImageSpawnConfiguration($this->configFactory),
      new KonamicodeActionReplaceImagesConfiguration($this->configFactory),
      new KonamicodeActionSnowfallConfiguration($this->configFactory),
      new KonamicodeActionAsteroidsConfiguration($this->configFactory),
    ];
  }

}
