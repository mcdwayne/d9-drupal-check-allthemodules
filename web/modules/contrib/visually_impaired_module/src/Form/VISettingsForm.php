<?php

namespace Drupal\visually_impaired_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class VISettingsForm extends ConfigFormBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a VISettingsForm object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visually_impaired_module_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'visually_impaired_module.visually_impaired_module.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('visually_impaired_module.visually_impaired_module.settings');
    $themes = [];
    $options = $this->themeHandler->listInfo();
    foreach ($options as $name => $attr) {
      if ($attr->status) {
        $themes[$name] = $attr->info['name'];
      }
    }
    $form['default_visually_impaired_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Visually Impaired Theme'),
      '#options' => $themes,
      '#default_value' => $config->get('visually_impaired_theme'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('visually_impaired_module.visually_impaired_module.settings')
      ->set('visually_impaired_theme', $values['default_visually_impaired_theme'])
      ->save();
  }

}
