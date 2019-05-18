<?php

namespace Drupal\feedbackify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FeedbackifySettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feedbackify_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['feedbackify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('feedbackify.settings');
    $form['feedbackify_id'] = array(
      '#type' => 'textfield',
      '#title' => (string) $this->t('Feedbackify form ID'),
      '#description' => (string) $this->t('Grab Feedbackify ID your Feedbackify account.'),
      '#default_value' => $config->get('confs.feedbackify_id'),
      '#required' => TRUE,
    );
    $form['settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 50,
    );
    // Behavior settings.
    $form['confs'] = array(
      '#type' => 'details',
      '#title' => (string) $this->t('Configurations'),
      '#group' => 'settings',
    );
    $form['confs']['feedbackify_color'] = array(
      '#type' => 'textfield',
      '#title' => (string) $this->t('Button color'),
      '#description' => (string) $this->t('Please specify a hexadecimal color value like %color,
      or leave blank for transparent.', array('%color' => '#237BAB')),
      '#default_value' => $config->get('confs.feedbackify_color'),
    );
    $form['confs']['feedbackify_position'] = array(
      '#type' => 'select',
      '#title' => (string) $this->t('Button Position'),
      '#options' => array(
        'left' => (string) $this->t('Left'),
        'right' => (string) $this->t('Right'),
      ),
      '#description' => (string) $this->t('Please specify a hexadecimal color value like %color,
        or leave blank for transparent.', array('%color' => '#237BAB')),
      '#default_value' => $config->get('confs.feedbackify_position'),
    );
    // Advanced settings.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => (string) $this->t('Visibility'),
      '#group' => 'settings',
      '#description' => (string) $this->t(''),
    );
    $form['advanced']['feedbackify_visibility'] = array(
      '#type' => 'radios',
      '#title' => (string) $this->t('Display Feedbackify button'),
      '#options' => array(
        (string) $this->t('On every page except the listed pages.'),
        (string) $this->t('On the listed pages only.'),
      ),
      '#default_value' => $config->get('advanced.feedbackify_visibility'),
    );
    $form['advanced']['feedbackify_pages'] = array(
      '#type' => 'textarea',
      '#title' => (string) $this->t('Pages'),
      '#default_value' => $config->get('advanced.feedbackify_pages'),
      '#description' => (string) $this->t("Enter one page per line as Drupal paths.
        The '*' character is a wildcard. Example paths are %blog for the blog
        page and %blog-wildcard for every personal blog. %front is the front
        page.<br/>
        Note: The script will not be added on admin pages by default.", array(
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      )),
      '#wysiwyg' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('feedbackify.settings');
    $config->set('confs.feedbackify_id', $form_state->getValue('feedbackify_id'))
      ->set('confs.feedbackify_color', $form_state->getValue('feedbackify_color'))
      ->set('confs.feedbackify_position', $form_state->getValue('feedbackify_position'))
      ->set('advanced.feedbackify_visibility', $form_state->getValue('feedbackify_visibility'))
      ->set('advanced.feedbackify_pages', $form_state->getValue('feedbackify_pages'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure a hexadecimal color value.
    if ($color = $form_state->getValue('feedbackify_color')) {
      if (!preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color)) {
        $form_state->setErrorByName('feedbackify_color', (string) t('Button color must be a hexadecimal color value like %color, or left blank for transparent.', array('%color' => '#237BAB')));
      }
    }
  }

}
