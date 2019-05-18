<?php

namespace Drupal\bootstrap_basic_image_gallery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\PathValidatorInterface;

/**
 * Defines a form that configures Questions and Answers settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(PathValidatorInterface $path_validator) {
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_basic_image_gallery_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bootstrap_basic_image_gallery.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current settings.
    $settings = $this->config('bootstrap_basic_image_gallery.settings');

    $form['prevent_load_bootstrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent loading bootstrap scripts'),
      '#default_value' => $settings->get('prevent_load_bootstrap'),
      '#description' => $this->t('Uncheck this box to stop the module from loading Bootstrap files. This can be useful if you are using a theme that already loads the Bootstrap files. If this is occurring, when clicking the gallery image, the image appears on screen and then quickly disappears. This occurs because the modal script is loaded twice. Checking this box will disable the Bootstrap Image Gallery scripts and use the theme instead.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the updated settings.
    $this->config('bootstrap_basic_image_gallery.settings')
      ->set('prevent_load_bootstrap', $values['prevent_load_bootstrap'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
