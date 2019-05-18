<?php

namespace Drupal\commerce_amazon_lpa\Element;

use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Amazon Login render element.
 *
 * @RenderElement("amazon_login")
 */
class AmazonLogin extends AmazonElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#html_id' => 'AmazonLoginButton',
      '#title' => t('Login with Amazon'),
      '#size' => 'medium',
      '#style' => 'Gold',
      '#pre_render' => [
        [$class, 'preRender'],
        [$class, 'attachLibrary'],
      ],
    ];
  }

  /**
   * Element pre-render callback.
   */
  public static function preRender($element) {
    $attributes = new Attribute();
    $attributes
      ->setAttribute('id', $element['#html_id'])
      ->setAttribute('title', t('Login with Amazon'))
      ->setAttribute('data-url', Url::fromRoute('commerce_amazon_lpa.login_with_amazon', [], ['absolute' => TRUE])->toString())
      ->setAttribute('data-amazon-button', 'LwA')
      ->setAttribute('data-size', $element['#size'])
      ->setAttribute('data-style', $element['#style']);

    $element['#markup'] = Markup::create(sprintf('<div %s></div>', $attributes));
    return $element;
  }

}
