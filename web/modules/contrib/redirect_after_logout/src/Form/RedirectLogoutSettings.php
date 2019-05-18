<?php

namespace Drupal\redirect_after_logout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RedirectLogoutSettings.
 *
 * @package Drupal\redirect_after_logout\Form
 *
 * @ingroup redirect_after_logout
 */
class RedirectLogoutSettings extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandler $module_handler, PathValidator $pathValidator) {
    $this->moduleHandler = $module_handler;
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('path.validator')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'redirect_after_logout_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['redirect_after_logout.settings'];
  }

  /**
   * Defines the settings form for Redirect After Logout.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redirect_after_logout.settings');

    $form['redirect_after_logout_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default user redirect destination'),
      '#description' => $this->t('%front is the front page. Tokens are allowed.', ['%front' => '<front>']),
      '#default_value' => $config->get('destination'),
      '#required' => TRUE,
    ];

    $form['redirect_after_logout_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default user redirect message, after logout'),
      '#description' => $this->t('Tokens are allowed.'),
      '#default_value' => $config->get('message'),
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      // Add the token help to a collapsed fieldset at
      // the end of the configuration page.
      $form['token_help'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Available Tokens List'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['token_help']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#global_types' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate redirect destination.
    if (($value = $form_state->getValue('redirect_after_logout_destination')) && $value[0] !== '/' && $value !== '<front>') {
      $form_state->setErrorByName('redirect_after_logout_destination', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('redirect_after_logout_destination')]));
    }
    if (!$this->pathValidator->isValid($form_state->getValue('redirect_after_logout_destination'))) {
      $form_state->setErrorByName('redirect_after_logout_destination', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('redirect_after_logout_destination')]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('redirect_after_logout.settings');
    $config->set('destination', $form_state->getValue('redirect_after_logout_destination'));
    $config->set('message', $form_state->getValue('redirect_after_logout_message'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
