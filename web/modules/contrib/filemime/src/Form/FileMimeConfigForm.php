<?php

namespace Drupal\filemime\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the file MIME config form.
 */
class FileMimeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filemime_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['filemime.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#default_value' => $this->config('filemime.settings')->get('file'),
      '#description' => t('If a mime.types file is readable, it will be parsed to extract MIME type mappings. Example: <em>/etc/mime.types</em>') . '<br />' . (is_readable($this->config('filemime.settings')->get('file')) ? t('The configured mime.types file is readable.') : t('The configured mime.types file is not readable.')),
      '#title' => t('Local mime.types file path'),
      '#type' => 'textfield',
    ];
    $form['types'] = [
      '#default_value' => $this->config('filemime.settings')->get('types'),
      '#description' => t('Types provided here will override the mime.types file. Specify the mappings using the mime.types file format. Example:<br /><em>audio/mpeg mpga mpega mp2 mp3 m4a<br />audio/mpegurl m3u<br />audio/ogg oga ogg opus spx</em>'),
      '#title' => t('Custom MIME type mappings'),
      '#type' => 'textarea',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('filemime.settings')
      ->set('file', $form_state->getValue('file'))
      ->set('types', $form_state->getValue('types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
