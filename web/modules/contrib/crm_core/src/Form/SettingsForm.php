<?php

namespace Drupal\crm_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure general settings.
 */
class SettingsForm extends ConfigFormBase {

  protected $themeHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Theme manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandlerInterface $theme_handler) {
    $this->setConfigFactory($config_factory);
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['crm_core.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_core_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('crm_core.settings');

    $form['theme'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CRM Core Theme'),
      '#weight' => 1,
    ];
    $themes = ['' => $this->t('Default')];
    foreach ($this->themeHandler->listInfo() as $theme) {
      if ($theme->status == 1) {
        $themes[$theme->getName()] = $this->themeHandler->getName($theme->getName());
      }
    }

    $form['theme']['crm_core_custom_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Use a custom theme for the CRM UI'),
      '#description' => $this->t('When checked, all pages under the crm-core path will be displayed using this theme.'),
      '#default_value' => $config->get('custom_theme'),
      '#options' => $themes,
      '#weight' => 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()
      ->getEditable('crm_core.settings');

    $config
      ->set('custom_theme', $form_state->getValue('crm_core_custom_theme'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
