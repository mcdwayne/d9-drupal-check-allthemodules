<?php

/**
 * @file
 * Contains \Drupal\maestro_template_builder\MaestroEngineSettingsForm
 */

namespace Drupal\maestro_template_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this site.
 */
class MaestroTemplateBuilderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maestro_template_builder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'maestro_template_builder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('maestro_template_builder.settings');

    $form['maestro_template_builder_admin_settings']['#prefix'] = $this->t('Changes to these settings require a Drupal cache clear to take effect.');
    
    $form['maestro_template_builder_local_library'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Use Raphael as a local library?"),
      '#default_value' => $config->get('maestro_template_builder_local_library'),
      '#description' => $this->t('When checked, the template builder will look locally in /libraries/raphael for raphael.js.  Unchecked will use the remote library location. '),
    );
    
    
    $default = $config->get('maestro_template_builder_remote_library_location');
    $form['maestro_template_builder_remote_library_location'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URI used to pull the Raphael JS library.'),
      '#default_value' => isset($default) ? $default : '//cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.js',
      '#description' => $this->t('Defaults to //cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.js'),
      '#required' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maestro_template_builder.settings')
      ->set('maestro_template_builder_local_library', $form_state->getValue('maestro_template_builder_local_library'))
      ->save();
    
    $this->config('maestro_template_builder.settings')
      ->set('maestro_template_builder_remote_library_location', $form_state->getValue('maestro_template_builder_remote_library_location'))
      ->save();  

    parent::submitForm($form, $form_state);
  }

}