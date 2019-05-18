<?php

namespace Drupal\commerce_payone\PluginForm\CreditCard;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\commerce_payone\Plugin\Commerce\PaymentGateway\PayoneCreditCard;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class PaymentMethodAddForm extends BasePaymentMethodAddForm {
  use StringTranslationTrait;
  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payone\Plugin\Commerce\PaymentGateway\CreditCardInterface $plugin */
    $plugin = $payment->getPaymentGateway()->getPlugin();
    $plugin_configuration = $plugin->getConfiguration();

    $data = [
      'request' => 'creditcardcheck',
      'responsetype' => 'JSON',
      'mode' => $plugin_configuration['mode'],
      'mid' => $plugin_configuration['merchant_id'],
      'aid' => $plugin_configuration['sub_account_id'],
      'portalid' => $plugin_configuration['portal_id'],
      'encoding' => 'UTF-8',
      'storecarddata' => 'yes',
    ];
    $data['hash'] = $this->generateHash($data, $plugin_configuration['key']);
    $credit_card_types = CreditCard::getTypes();
    $payoneCreditcardMap = PayoneCreditCard::creditCardMap();
    // Limit allowed cards, if configured.
    if (!empty($plugin_configuration['allowed_cards'])) {
      $allowed_types = array_flip($plugin_configuration['allowed_cards']);
      $credit_card_types = array_intersect_key($credit_card_types, $allowed_types);
    }
    $allowed_map = [];
    foreach ($payoneCreditcardMap as $payoneKey => $cardKey) {
      if (isset($allowed_types[$cardKey])) {
        $allowed_map[$payoneKey] = $cardKey;
      }
    }
    $element['#attached']['library'][] = 'commerce_payone/form';
    $element['#attached']['drupalSettings']['commercePayone'] = [
      'request' => $data,
      'allowed_cards' => array_keys($allowed_map),
      'allowed_cards_map' => $allowed_map,
    ];
    $element['#attributes']['class'][] = 'payone-form';
    $element['#attributes']['class'][] = 'credit-card-form';
    $element['#id'] = 'payone-form';

    $element['card_types'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['commerce_payment/payment_method_icons'],
      ],
    ];
    foreach ($credit_card_types as $credit_card_type) {
      $element['card_types'][$credit_card_type->getId()] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'id' => $credit_card_type->getId(),
          'class' => [
            'payment-method-icon',
            'payment-method-icon--' . $credit_card_type->getId(),
          ],
          'style' => '',
        ],
      ];
    }

    // Hidden elements.
    $element['pseudocardpan'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['pseudocardpan'],
      ],
    ];

    $element['truncatedcardpan'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['truncatedcardpan'],
      ],
    ];

    $element['cardexpiredate'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['cardexpiredateResponse'],
      ],
    ];

    $element['cardtype'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['cardtypeResponse'],
      ],
    ];

    $element['cardpaninputlabel'] = [
      '#type' => 'label',
      '#title' => $this->t('Card Number'),
      '#for' => 'cardpanInput',
    ];

    $element['cardpanplaceholder'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['inputIframe'],
        'id' => 'cardpan',
      ],
    ];

    $element['cvcinputlabel'] = [
      '#type' => 'label',
      '#title' => $this->t('CVC'),
      '#for' => 'cvcInput',
    ];

    $element['cvcplaceholder'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['inputIframe'],
        'id' => 'cardcvc2',
      ],
    ];

    $element['expireinputlabel'] = [
      '#type' => 'label',
      '#title' => $this->t('Expire date (mm/yyyy)'),
      '#for' => 'expireInput',
    ];

    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'expireInput',
        'class' => ['inputIframe', 'credit-card-form__expiration'],
      ],
    ];

    $element['expiration']['month'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'id' => 'cardexpiremonth',
      ],
    ];

    $element['expiration']['divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['credit-card-form__divider'],
      ],
    ];

    $element['expiration']['year'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'id' => 'cardexpireyear',
      ],
    ];

    $element['error'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'id' => 'error',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    if (empty($values['pseudocardpan'])) {
      $form_state->setError($element, $this->t('Card data could not be retrieved.'));
    }
    if (empty($values['cardtype'])) {
      $form_state->setError($element, $this->t('Card type could not be retrieved.'));
    }
    if (empty($values['truncatedcardpan'])) {
      $form_state->setError($element, $this->t('Card data could not be retrieved.'));
    }
    if (empty($values['cardexpiredate'])) {
      $form_state->setError($element, $this->t('Card expire date could not be retrieved.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

  /**
   * Calculates the hash value required in Client API requests.
   *
   * @param array $data
   * @param string $securitykey
   * @return string
   */
  protected function generateHash(array $data, $securitykey) {
    // Sort by keys.
    ksort($data);

    // Hash code.
    $hashstr = '';
    foreach ($data as $key => $value) {
      $hashstr .= $data[$key];
    }
    $hashstr .= $securitykey;
    $hash = md5($hashstr);

    return $hash;
  }
}
