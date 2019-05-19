<?php

/**
 * @file
 * Contains \Drupal\smart_login\Form\SettingsForm.
 */

namespace Drupal\smart_login\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure settings for Smart Login.
 */
class SmartLoginSettingsForm extends ConfigFormBase {
  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $pathValidator, AliasManagerInterface $alias_manager, ThemeHandlerInterface $theme_handler) {
    parent::__construct($config_factory);

    $this->pathValidator = $pathValidator;
    $this->aliasManager = $alias_manager;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('path.alias_manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $moduleConfig = $this->config('smart_login.settings');
    $urlPrefix = $this->url('<none>', [], ['absolute' => TRUE]);

    $theme_options = [
      'theme:default' => t('Default theme'),
      'theme:admin' => t('Default admin theme'),
    ];
    $themes = $this->themeHandler->rebuildThemeData();

    foreach ($themes as $theme_id => $theme) {
      $theme_options[$theme_id] = $theme->info['name'];
    }

    $form['admin'] = [
      '#type' => 'fieldset',
      '#title' => t('Admin Settings'),
      '#collapsible' => FALSE,
    ];

    $form['admin']['admin_theme'] = [
      '#type' => 'select',
      '#title' => t('Theme'),
      '#description' => t('The theme to be used when user goes to admin/login.'),
      '#options' => $theme_options,
      '#default_value' => $moduleConfig->get('admin.theme'),
    ];

    $form['admin']['admin_destination'] = [
      '#type' => 'textfield',
      '#title' => t('Login destination'),
      '#default_value' => $moduleConfig->get('admin.destination'),
      '#size' => 40,
      '#description' => t('The destination after user logins if there is no defined destination in url.'),
      '#field_prefix' => $urlPrefix,
    ];

    $form['admin']['admin_loggedin_redirect'] = [
      '#type' => 'textfield',
      '#title' => t('Logged in redirect'),
      '#default_value' => $moduleConfig->get('admin.loggedin_redirect'),
      '#size' => 40,
      '#description' => t('This page is displayed when a logged-in user goes to admin/login.'),
      '#field_prefix' => $urlPrefix,
    ];

    $form['front'] = [
      '#type' => 'fieldset',
      '#title' => t('Frontend Settings'),
      '#collapsible' => FALSE,
    ];

    $form['front']['front_destination'] = [
      '#type' => 'textfield',
      '#title' => t('Login destination'),
      '#default_value' => $moduleConfig->get('front.destination'),
      '#size' => 40,
      '#description' => t('The destination after user logins if there is no defined destination in url.'),
      '#field_prefix' => $urlPrefix,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('admin_destination')) {
      $adminDestination = $form_state->getValue('admin_destination');
      $form_state->setValue('admin_destination', $this->aliasManager->getPathByAlias($adminDestination));

      if (!$this->pathValidator->isValid($adminDestination)) {
        $form_state->setErrorByName('admin_destination', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $adminDestination]));
      }
    }


    if ($form_state->hasValue('admin_loggedin_redirect')) {
      $adminLoginRedirect = $form_state->getValue('admin_loggedin_redirect');
      $form_state->setValue('admin_loggedin_redirect', $this->aliasManager->getPathByAlias($adminLoginRedirect));

      if (!$this->pathValidator->isValid($adminLoginRedirect)) {
        $form_state->setErrorByName('admin_loggedin_redirect', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $adminLoginRedirect]));
      }
    }

    if ($form_state->hasValue('front_destination')) {
      $frontDestination = $form_state->getValue('front_destination');
      $form_state->setValue('front_destination', $this->aliasManager->getPathByAlias($frontDestination));

      if (!$this->pathValidator->isValid($frontDestination)) {
        $form_state->setErrorByName('front_destination', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $frontDestination]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('smart_login.settings')
      ->set('admin.theme', $form_state->getValue('admin_theme'))
      ->set('admin.destination', $form_state->getValue('admin_destination'))
      ->set('admin.loggedin_redirect', $form_state->getValue('admin_loggedin_redirect'))
      ->set('front.destination', $form_state->getValue('front_destination'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smart_login.settings'];
  }
}
