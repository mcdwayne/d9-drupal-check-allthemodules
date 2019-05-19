<?php

namespace Drupal\token_or;

use Drupal\token\Token as OriginalToken;
use Drupal\Core\Render\BubbleableMetadata;

class Token extends OriginalToken {

  /**
   * {@inheritdoc}
   */
  public function replace($text, array $data = [], array $options = [], BubbleableMetadata $bubbleable_metadata = NULL) {
    $this->moduleHandler->alter('tokens_pre', $text, $data, $options);
    return parent::replace($text, $data, $options, $bubbleable_metadata);
  }

}