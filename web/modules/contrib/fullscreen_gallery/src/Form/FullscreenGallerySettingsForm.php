<?php

namespace Drupal\fullscreen_gallery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures fullscreen gallery settings.
 */
class FullscreenGallerySettingsForm extends ConfigFormBase {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a Fullscreen Gallery settings form object.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('theme.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fullscreen_gallery_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fullscreen_gallery.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fullscreen_gallery.settings');

    // Right sidebar width value.
    $form['fullscreen_gallery_rs_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width of the right side bar'),
      '#default_value' => $config->get('fullscreen_gallery_rs_width'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => FALSE,
    ];

    // Right sidebar width type (px or %).
    $form['fullscreen_gallery_rs_width_type'] = [
      '#type' => 'select',
      '#title' => '',
      '#default_value' => $config->get('fullscreen_gallery_rs_width_type'),
      '#description' => $this->t('Right sidebar appears if there is any content on <em>Full screen gallery right sidebar</em> region, and a valid sidebar width is given.'),
      '#options' => ['px' => $this->t('pixels'), 'pe' => $this->t('percent')],
    ];

    // Checkbox for disabling title display.
    $form['fullscreen_gallery_disable_titles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable image titles'),
      '#default_value' => $config->get('fullscreen_gallery_disable_titles'),
      '#description' => $this->t("Hide image titles in fullscreen gallery."),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate given right sidebar width: it must be a numeric value.
    $value = $form_state->getValue('fullscreen_gallery_rs_width');
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value < 0)) {
      $form_state->setErrorByName('fullscreen_gallery_rs_width', $this->t('field must be a valid integer.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save submitted values into fullscreen gallery settings.
    $values = $form_state->getValues();
    $this->config('fullscreen_gallery.settings')
      ->set('fullscreen_gallery_rs_width', $values['fullscreen_gallery_rs_width'])
      ->set('fullscreen_gallery_rs_width_type', $values['fullscreen_gallery_rs_width_type'])
      ->set('fullscreen_gallery_disable_titles', $values['fullscreen_gallery_disable_titles'])
      ->save();

    // Clear cache to force Drupal recognize the new Fullscreen Gallery region
    // if it is unknown yet.
    $regions = system_region_list($this->themeManager->getActiveTheme()->getName());
    if (!isset($regions['fullscreen_gallery_right'])) {
      drupal_flush_all_caches();
    }
    parent::submitForm($form, $form_state);
  }

}
