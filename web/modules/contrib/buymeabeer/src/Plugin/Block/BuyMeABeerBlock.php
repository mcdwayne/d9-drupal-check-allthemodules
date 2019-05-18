<?php

namespace Drupal\buymeabeer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'buy_me_a_beer' Block.
 *
 * @Block(
 *   id = "buy_me_a_beer",
 *   admin_label = @Translation("Buy me a beer"),
 *   category = @Translation("custom"),
 * )
 */
class BuyMeABeerBlock extends BlockBase implements
    BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    if (!empty($config['buymeabeer_custom_button_id'])) {
     $buymeabeer_custom_button_id = $config['buymeabeer_custom_button_id'];
     $buymeabeer_link_title_text = $config['buymeabeer_link_title_text'];
     $buymeabeer_paypal_mail = $config['buymeabeer_paypal_mail'];
     $url = "https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=" . $config['buymeabeer_custom_button_id'];
    }
    else {
      $buymeabeer_paypal_mail = $config['buymeabeer_paypal_mail'];
      $buymeabeer_link_title_text = $config['buymeabeer_link_title_text'];
      $buymeabeer_custom_button_id = $config['buymeabeer_custom_button_id'];
      $item_name = "&item_name=";
      $url = "https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=" . $config['buymeabeer_paypal_mail'] .$item_name . $config['buymeabeer_link_title_text'];
      
      
    }

    return [
      '#theme' => 'buymeabeer_template',
      '#buymeabeer_link_title_text' => $buymeabeer_link_title_text,
      '#buymeabeer_paypal_mail' => $buymeabeer_paypal_mail,
      '#buymeabeer_custom_button_id' => $buymeabeer_custom_button_id,
      '#buymeabeer_link_url' => $url,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['buymeabeer_paypal_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paypal E-mail Addrees'),
      '#description' => $this->t('Enter an e-mail address that is enabled to receive paypal payments.'),
      '#size' => 60,
      '#maxlength' => 60,
      '#required' => TRUE,
      '#default_value' => isset($config['buymeabeer_paypal_mail']) ? $config['buymeabeer_paypal_mail'] : '',
    ];

    $form['buymeabeer_link_title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Title Text'),
      '#description' => $this->t('Enter the title text for the link. This is what is displayed as
      a tooltip when the cursor is moved over the link. This text is also used as the paypal
      donation identifier'),
      '#size' => 60,
      '#maxlength' => 60,
      '#value' => $this->t('Donate a beer or two via Paypal.'),
      '#default_value' => isset($config['buymeabeer_link_title_text']) ? $config['buymeabeer_link_title_text'] : '',
    ];

    $form['buymeabeer_custom_button_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Paypal Button ID (Eg.: ABCDEF123456)'),
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => $this->t('This is not required. You can fill in the email OR the button ID. The button ID gets priority'),
      '#default_value' => isset($config['buymeabeer_custom_button_id']) ? $config['buymeabeer_custom_button_id'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['buymeabeer_paypal_mail'] = $values['buymeabeer_paypal_mail'];
    $this->configuration['buymeabeer_link_title_text'] = $values['buymeabeer_link_title_text'];
    $this->configuration['buymeabeer_custom_button_id'] = $values['buymeabeer_custom_button_id'];
  }

}
