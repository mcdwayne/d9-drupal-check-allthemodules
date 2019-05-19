<?php
namespace Drupal\x_originating_ip;

class XOriginatingIpSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'x_originating_ip_settings';
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('x_originating_ip.settings');

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
    $form = array();
  
    $headers = _x_originating_ip_headers();
  
    $form['x_originating_ip_header'] = array(
      '#type' => 'radios',
      '#title' => t('Email origin header'),
      '#default_value' => variable_get('x_originating_ip_header', 'X-Originating-IP'),
      '#options' => $headers,
      '#element_validate' => array('_x_originating_ip_valid_header'),
      '#description' => t('Though Microsoft made the X-Originating-IP header popular with Hotmail, various development how-to documents have proposed alternative headers listed here.'),
    );
  
    return parent::buildForm($form, $form_state);
  }
}
