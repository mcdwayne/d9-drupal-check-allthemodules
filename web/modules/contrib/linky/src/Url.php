<?php

namespace Drupal\linky;

use Drupal\Core\Url as CoreUrl;

/**
 * Extends core Url return correct internal path if requested.
 */
class Url extends CoreUrl {

  /**
   * {@inheritdoc}
   */
  public function getInternalPath() {
    /** @var \Drupal\Core\Url $internalCanonical */
    $internalCanonical = $this->options['linky_entity_canonical'];
    return $internalCanonical->getInternalPath();
  }

  /**
   * {@inheritdoc}
   */
  protected static function fromInternalUri(array $uri_parts, array $options) {
    // Sometimes this method calls pathValidator service, which returns a core
    // url. We coerce it into becoming this class again.
    $uri = parent::fromInternalUri($uri_parts, $options);
    if (get_class($uri) === CoreUrl::class) {
      $new = static::fromUri($uri->toUriString());
      $new->setOptions($uri->getOptions());
      return $new;
    }
    return $uri;
  }

}
