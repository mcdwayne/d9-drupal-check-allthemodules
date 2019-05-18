<?php

namespace Drupal\paragraphs_type_help\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * Modifies the 'Add paragraphs_type_help' local action.
 */
class ParagraphsTypeHelpAddLocalAction extends LocalActionDefault {
  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);

    // Adds a destination on paragraphs_type_help listing.
    if ($route_match->getRouteName() == 'entity.paragraphs_type_help.collection') {
      $options['query']['destination'] = $this->url('<current>');
    }
    return $options;
  }

}
