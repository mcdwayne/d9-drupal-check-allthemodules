<?php
/**
 * @file
 * Implements \Drupal\forena\GeneralConfigForm
 */

namespace Drupal\forena\Form;


use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\ReportManager;

/**
 * Provides General Configuration form
 */
class GeneralConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forena_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $skins = ReportManager::instance()->skins();
    $config = $this->config('forena.settings');

    /*@TODO: Fix Input format selection */
    //$form['forena_input_format'] = forena_filter_element($config->get('forena_input_format'));
    $form['default_skin'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Skin'),
      '#options' => $skins,
      '#description' => t('Specify the default skin to be used.   New skins can be created by creating .skin.yml files in your reports directory.'
        . ' Skins are basically css and javascript libraries added to your report.'),
      '#default_value' => $config->get('default_skin'),
    ];


    $formats = filter_formats();
    $options = ['none' => $this->t('None')];
    foreach ($formats as $format) {
      $options[$format->id()] = $format->label();
    }

    $form['input_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text Format'),
      '#description' => $this->t('Process reports using Text Formats. This can be overridden at the skin or report level.'),
      '#options' => $options,
      '#default_value' => $config->get('input_format'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['forena.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('forena.settings')
      ->set('default_skin', $values['default_skin'])
      ->set('input_format', $values['input_format'])
      ->save();

    // Call Configuration form to save changes.
    parent::submitForm($form, $form_state);
  }
}