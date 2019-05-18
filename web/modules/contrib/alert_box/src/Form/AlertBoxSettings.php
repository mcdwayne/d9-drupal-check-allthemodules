<?php

namespace Drupal\alert_box\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;

class AlertBoxSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alert_box_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('alert_box.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['alert_box.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  	$alert_default = \Drupal::config('alert_box.settings');
  	
  	$form['enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Alert Box Status'),
      '#default_value' => $alert_default->get('enabled') ? 1 : 0,
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('Enabled'),
      ],
      '#description' => $this->t('When set to "Disabled", no message will display. When set to "Enabled", all pages on the site will display the alert message that is specified below. The alert message will only show if the "Alert Box" block is in a visible region of the site\'s theme as set on @blocks_link.', [
        '@blocks_link' => Link::createFromRoute($this->t('the block admin page'), 'block.admin_display')->toString()
        ]),
    ];

    $form['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Alert Box Message'),
      '#default_value' => $alert_default->get('message.value'),
      '#format' => $alert_default->get('message.format'),
      '#description' => $this->t('Message to show visitors when the alert box is enabled.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
