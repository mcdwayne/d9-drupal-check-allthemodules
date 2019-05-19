<?php

namespace Drupal\transcript\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transcript_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'transcript.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = \Drupal::config('transcript.settings');
    $form['transcript_video_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Youtube video id'),
      '#default_value' => $config->get('transcript_video_id'),
      '#size' => 20,
      '#maxlength' => 50,
      '#description' => $this->t('Please provide your youtube video id alone (Not url)'),
      '#required' => TRUE,
    );
    $form['transcript_lang_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Youtube language code'),
      '#default_value' => $config->get('transcript_lang_code'),
      '#size' => 20,
      '#maxlength' => 10,
      '#description' => $this->t('Please provide your transcript language code. Ex: en or ta or fr-CA (You can find the language code from the Youtube site url)'),
      '#required' => TRUE,
    );
    $form['transcript_video_auto_play'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Youtube video auto play'),
      '#default_value' => $config->get('transcript_video_auto_play'),
      '#return_value' => 1,
    );
    $form['transcript_iframe_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Width of Iframe'),
      '#default_value' => $config->get('transcript_iframe_width'),
      '#size' => 20,
    );
    $form['transcript_iframe_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height of Iframe'),
      '#default_value' => $config->get('transcript_iframe_height'),
      '#size' => 20,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = \Drupal::service('config.factory')->getEditable('transcript.settings');
    $config->set('transcript_video_id', $values['transcript_video_id'])
           ->set('transcript_lang_code', $values['transcript_lang_code'])
           ->set('transcript_video_auto_play', $values['transcript_video_auto_play'])
           ->set('transcript_iframe_width', $values['transcript_iframe_width'])
           ->set('transcript_iframe_height', $values['transcript_iframe_height'])
           ->save();
  }

}
