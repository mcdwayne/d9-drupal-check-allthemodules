<?php

/**
 * @file
 * Contains \Drupal\mpac\MpacAutocomplete.
 *
 * @todo Create SelectInterface for different types of autocomplete data.
 */

namespace Drupal\mpac;

use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a helper class to get mpac autocompletion results.
 */
class MpacAutocomplete {

  /**
   * The config factory to get the anonymous user name.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a MpacAutocomplete object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get matches for the autocompletion.
   *
   * @param string $type
   *   The type of data to find (i.e. "path" or "shortcut").
   * @param string $string
   *   The string to match.
   *
   * @return array
   *   An array containing the matching items.
   */
  public function getMatches($type, $string) {
    $matches = array();

    $handlers = mpac_get_selection_handlers($type);
    if ($string) {
      $limit = $this
              ->configFactory
              ->get('mpac.autocomplete')
              ->get('items.max');
      // Load results.
      foreach ($handlers as $handler) {
        $matches = array_merge($matches, $handler->getMatchingItems($string, 'CONTAINS', $limit));
      }
    }
    // Allow other modules to alter the list of matches.
    \Drupal::moduleHandler()
            ->alter('mpac_selection_matches', $matches, $type, $string);

    return array_slice($matches, 0, $limit, TRUE);
  }

}
