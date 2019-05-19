<?php

namespace Drupal\stripe_checkout\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'stripe_checkout_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "stripe_checkout_formatter",
 *   label = @Translation("Stripe checkout"),
 *   field_types = {
 *     "stripe_checkout"
 *   }
 * )
 */
class StripeCheckoutFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_text' => 'Purchase - $@price',
      'company_name' => 'Company name',
      'free_text' => 'Access for free',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'link_text' => [
        '#type' => 'textfield',
        '#title' => 'Link text',
        '#description' => 'The text that will be displayed for the purchase button.',
        '#default value' => $this->getSetting('link_text'),
        '#required' => FALSE,
      ],
      'free_text' => [
        '#type' => 'textfield',
        '#title' => 'Free text',
        '#description' => 'The text that will be displayed for the purchase button if the node is free.',
        '#default value' => $this->getSetting('free_text'),
        '#required' => FALSE,
      ],
      'company_name' => [
        '#type' => 'textfield',
        '#title' => 'Company name',
        '#description' => 'The company name displayed in the pop-up widget.',
        '#default value' => $this->getSetting('company_name'),
        '#required' => FALSE,
      ],
      'description' => [
        '#type' => 'textarea',
        '#title' => 'Description',
        '#description' => ' The company description displayed in the pop-up widget.',
        '#default value' => $this->getSetting('description'),
        '#required' => FALSE,
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('free_text');
    $summary[] = $this->getSetting('link_text');
    $summary[] = $this->getSetting('company_name');
    $summary[] = $this->getSetting('description');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $pub_key = \Drupal::service('stripe_api.stripe_api')->getPubKey();
    $current_path = Url::fromRoute('<current>')->getInternalPath();
    $default_settings = $this::defaultSettings();

    foreach ($items as $delta => $item) {
      // @todo add cacheability metadata for api key and entity.
      $price = $this->viewValue($item);
      $is_free = (int) $price == 0;
      if ($is_free) {
        $link_text = $this->t($this->getSetting('free_text'));
      }
      else {
        $link_text = $this->t($this->getSetting('link_text'), ['@price' => $price]);
      }
      if (!(string) $link_text) {
        $link_text = $this->t($default_settings['link_text'], ['@price' => $price]);
      }

      if (empty($pub_key) ||
        \Drupal::currentUser()->isAuthenticated() && $is_free) {
        // @todo Log an error.
        $elements[$delta] = [
          '#markup' => '',
        ];
        continue;
      }

      $elements[$delta] = [
        '#theme' => 'stripe_checkout',
        '#data' => [
          // Price is specified in cents.
          'amount' => $price * 100,
          'name' => $this->getSetting('company_name'),
          'description' => $this->getSetting('description'),
          'key' => $pub_key,
          // @todo Make configurable.
          'zip_code' => 'true',
          'locale' => 'auto',
          // @todo Make configurable.
          'image' => 'https://stripe.com/img/documentation/checkout/marketplace.png',
          'email' => \Drupal::currentUser()->getEmail(),
          'label' => $link_text,
        ],
        '#is_free' => $is_free,
        '#price' => $price,
        '#entity_id' => $item->getEntity()->id(),
        '#field_name' => $item->getFieldDefinition()->getName(),
        '#logged_in' => \Drupal::currentUser()->isAuthenticated(),
        "#anon_url" => Url::fromRoute('user.register', [], [
          'query' => [
            'destination' => $current_path,
            'stripe_checkout_click' => !$is_free,
          ],
        ]),
        '#action' => Url::fromRoute('stripe_checkout.stripe_charge_controller_charge'),
        '#attached' => [
          'library' => [
            'stripe_checkout/checkout',
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
