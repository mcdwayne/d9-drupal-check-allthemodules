<?php

namespace Drupal\shrinktheweb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ShrinkTheWebDonateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shrinktheweb_donate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $form['#action'] = 'https://www.paypal.com/cgi-bin/webscr';

    $form['shrinktheweb_donate_cmd'] = array(
      '#type' => 'hidden',
      '#name' => 'cmd',
      '#value' => '_s-xclick'
    );

    $form['shrinktheweb_donate_encrypted'] = array(
      '#type' => 'hidden',
      '#name' => 'encrypted',
      '#value' => '-----BEGIN PKCS7-----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-----END PKCS7-----'
    );

    $form['shrinktheweb_donate_submit'] = array(
      '#type' => 'image_button',
      '#src' => 'https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif',
      '#name' => 'submit',
      '#attributes' => array(
        'class' => array('donate-form-submit'),
        'border' => '0'
      ),
      '#alt' => t('PayPal - The safer, easier way to pay online!')
    );

    $form['shrinktheweb_donate_img'] = array(
      '#markup' => '<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">'
    );

    $form['#attributes'] = array(
      'target' => '_blank',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}