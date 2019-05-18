<?php

namespace Drupal\commerce_amazon_lpa\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Amazon pay render element.
 *
 * @RenderElement("amazon_pay")
 */
class AmazonPay extends AmazonElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#html_id' => 'AmazonPaymentButton',
      '#title' => t('Amazon Payments makes shopping on our website even easier by using the payment information in your Amazon account.'),
      '#order_id' => NULL,
      '#size' => 'medium',
      '#style' => 'Gold',
      '#pre_render' => [
        [$class, 'preRender'],
        [$class, 'attachLibrary'],
      ],
    ];
  }

  /**
   * Pre-render callback.
   */
  public static function preRender($element) {
    if (empty($element['#order_id'])) {
      return $element;
    }

    $attributes = new Attribute();
    $attributes
      ->setAttribute('id', Html::getUniqueId($element['#html_id']))
      ->setAttribute('title', $element['#title'])
      ->setAttribute('data-url', Url::fromRoute('commerce_amazon_lpa.amazon_pay_checkout', [], [
        'absolute' => TRUE,
        'query' => [
          'order_id' => $element['#order_id'],
        ],
      ])->toString())
      ->setAttribute('data-amazon-button', 'PwA')
      ->setAttribute('data-size', $element['#size'])
      ->setAttribute('data-style', $element['#style']);

    $element['#markup'] = Markup::create(sprintf('<div %s></div>', $attributes));
    return $element;
  }

}
