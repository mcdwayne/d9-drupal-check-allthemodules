<?php

namespace Drupal\mustache;

use Drupal\Core\State\StateInterface;

/**
 * Service class for the Mustache.php render engine.
 *
 * Mustache.php engine written by
 * Copyright (c) 2010-2015 Justin Hileman.
 */
class MustachePhpEngine extends \Mustache_Engine {

  /**
   * MustachePhpEngine constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   * @param array $options
   *   See documentation of the Mustache_Engine class.
   */
  public function __construct(StateInterface $state, array $options = []) {
    if (!isset($options['cache'])) {
      $prefixes = $state->get('mustache_prefixes', []);
      if (empty($prefixes['cache_prefix'])) {
        $prefixes['cache_prefix'] = uniqid();
        $state->set('mustache_prefixes', $prefixes);
      }
      $options['cache'] = new MustachePhpCache($prefixes['cache_prefix']);
    }
    elseif (empty($options['cache'])) {
      unset($options['cache']);
    }
    parent::__construct($options);
  }

}
