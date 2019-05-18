<?php

/**
 * @file
 * Contains \Drupal\fortytwo_admin_toolbar\Form\FortytwoAdminToolbarForm.
 */

namespace Drupal\fortytwo_admin_toolbar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class FortytwoAdminToolbarForm extends ConfigFormBase {

  /**
   * Constructor for FortytwoAdminToolbarForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   * The unique string identifying the form.
   */
  public function getFormId() {
    return 'fortytwo_admin_toolbar_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   * An array of configuration object names that are editable if called in
   * conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.fortytwo_admin_toolbar'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   * An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * The current state of the form.
   *
   * @return array
   * The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config.fortytwo_admin_toolbar');

    // Logo settings for theme override.
    $form['fortytwo_admin_toolbar']['color_profile'] = array(
      '#type'          => 'select',
      '#title'         => t('Color profiles.'),
      '#default_value' => (!empty($config->get('color_profile'))) ? $config->get('color_profile') : 'beeblebrox',
      '#options'       => array(
        'beeblebrox' => t('Beeblebrox'),
        'marvin'     => t('Marvin'),
      ),
      '#description' => t('Use this setting to setup a specific color in the toolbar.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   * An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('config.fortytwo_admin_toolbar');
    $config->set('color_profile', $form_state->getValue('color_profile'))
           ->save();

    parent::submitForm($form, $form_state);
  }
}
