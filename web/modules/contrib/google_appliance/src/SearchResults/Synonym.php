<?php

namespace Drupal\google_appliance\SearchResults;

use Drupal\google_appliance\Routing\SearchViewRoute;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a value object for a synonym.
 */
class Synonym {

  /**
   * Description.
   *
   * @var string
   */
  protected $description;

  /**
   * URL.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs a new Synonym object.
   *
   * @param string $description
   *   Synonym description.
   * @param string $url
   *   Synonym URL.
   */
  public function __construct($description, $url) {
    $this->description = $description;
    $this->url = $url;
  }

  /**
   * Gets value of description.
   *
   * @return string
   *   Value of description
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets value of url.
   *
   * @return string
   *   Value of url
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Gets synonym as link.
   *
   * @return \Drupal\Core\Link
   *   Link object.
   */
  public function getLink() {
    return Link::fromTextAndUrl($this->description, Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $this->url,
    ]));
  }

}
