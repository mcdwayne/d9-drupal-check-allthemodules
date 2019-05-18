<?php

/**
 * @file
 * Contains \Drupal\frameprevention\Form\FramepreventionAdminSettings.
 */

namespace Drupal\frameprevention\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class FramepreventionAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'frameprevention_admin_settings';
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('frameprevention.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }


  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $owasp_url = Url::fromUri('http://w2spconf.com/2010/papers/p27.pdf');
    $owasp_link = \Drupal::l(t('Stanford Web Security Group'), $owasp_url);

    $form['frameprevention_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable CSS and Javascript-based frame-breaker'),
      '#default_value' => \Drupal::config('frameprevention.settings')->get('frameprevention_enabled'),
      '#description' => t('Based on recommended frame-breaking code from the !link. Having Javascript enabled on client web browsers will become a requirement when this is enabled, otherwise no content will display.', array('!link' => $owasp_link)),
    );
    $form['frameprevention_x_frame_options'] = array(
      '#title' => t('X-Frame-Options HTTP response header'),
      '#type' => 'select',
      '#options' => array(
        '' => t('disabled'),
        'DENY' => 'DENY',
        'SAMEORIGIN' => 'SAMEORIGIN',
      ),
      '#description' => t('DENY prevents any domain from framing the content. SAMEORIGIN allows the current site to frame the content.'),
      '#default_value' => \Drupal::config('frameprevention.settings')->get('frameprevention_x_frame_options'),
    );
    $form['frameprevention_pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages to ignore'),
      '#default_value' => \Drupal::config('frameprevention.settings')->get('frameprevention_pages'),
      '#cols' => 30,
      '#rows' => 5,
      '#description' => t('List of pages where the module is <b>disabled</b>. Use the <em>*</em> character for wildcard, each entry in a new line.'),
    );

    return parent::buildForm($form, $form_state);
  }
}
