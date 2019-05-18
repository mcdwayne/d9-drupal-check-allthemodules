<?php

namespace Drupal\shopify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the Shopify price formatter.
 *
 * @FieldFormatter(
 *   id = "shopify_price",
 *   label = @Translation("Shopify Price"),
 *   field_types = {
 *     "decimal",
 *   }
 * )
 */
class ShopifyPriceFormatter extends NumericFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $info = shopify_shop_info();
    return [
      'link' => [
        '#prefix' => '<br/>',
        '#type' => 'link',
        '#title' => t('Change currency format on Shopify'),
        '#url' => Url::fromUri('https://' . $info->domain . '/admin/settings/general'),
        '#suffix' => t('<br/>Under "Currency" settings click "Change formatting" and modify the "HTML without currency" setting.'),
        '#attributes' => ['target' => '_blank'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = 'Format: ' . shopify_shop_info('money_format');
    $summary[] = 'Preview: ' . $this->numberFormat(1234.1234567890);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function numberFormat($number) {
    $number = number_format($number, 2);
    return shopify_currency_format($number);
  }

}
