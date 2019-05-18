<?php

namespace Drupal\content_scroller\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scroller_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_scroller.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_scroller.settings');
    $form['content_scroller_selector'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Html selector'),
      '#default_value' => $config->get('content_scroller_selector'),
      '#description' => t('Selector id\'s to apply scroll.Eg. ".content, body".'),
    );
    $form['content_scroller_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Scrollbar type'),
      '#description' => t('Scrollbar type Eg. Horizonatal Or Vertical'),
      '#options' => array(
        'x' => t('horizontal'),
        'y' => t('vertical'),
        'yx' => t('vertical and horizontal scrollbar'),
      ),
      '#default_value' => $config->get('content_scroller_type'),
    );
    $form['content_scroller_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Scrollbar theme'),
      '#description' => t('Scrollbar theme type Eg. light Or dark'),
      '#options' => array(
        'light' => t('light'),
        'dark' => t('dark'),
        'rounded' => t('rounded'),
        '3d' => t('3d'),
        'minimal' > t('minimal'),
      ),
      '#default_value' => $config->get('content_scroller_theme'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('content_scroller.settings');
    $config->set('content_scroller_selector', $form_state->getValue('content_scroller_selector'));
    $config->set('content_scroller_type', $form_state->getValue('content_scroller_type'));
    $config->set('content_scroller_theme', $form_state->getValue('content_scroller_theme'))
        ->save();

    parent::submitForm($form, $form_state);
  }

}
