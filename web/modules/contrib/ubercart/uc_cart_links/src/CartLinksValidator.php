<?php

namespace Drupal\uc_cart_links;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Utility functions for dealing with Cart Links.
 */
class CartLinksValidator implements CartLinksValidatorInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidSyntax($link, $debug = FALSE) {
    $result = preg_match('~/cart/add/(e-)?(p[1-9][0-9]*(_q[1-9][0-9]*)*(_a[1-9][0-9]*o[a-zA-Z0-9%]+)*(_s)?)+(-m[0-9]+|-i[a-zA-Z0-9]+)*(\?destination=/[a-zA-Z0-9/%]+)?~', $link, $matches);

    if ($debug) {
      $this->messenger->addMessage('Cart Link = ' . $link);
      $this->messenger->addMessage('Regular expression matches = <pre>' . print_r($matches) . '</pre>');
    }

    return (bool) $result;
  }

}
