<?php

namespace Drupal\arb_token\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for arbitrary discover of token plugins.
 *
 * @Annotation
 */
class ArbitraryToken extends Plugin {

  public $id;
  public $label;

}
