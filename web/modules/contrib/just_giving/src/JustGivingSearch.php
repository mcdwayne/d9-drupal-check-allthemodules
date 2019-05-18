<?php

namespace Drupal\just_giving;


/**
 * Class JustGivingSearch.
 */
class JustGivingSearch implements JustGivingSearchInterface {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingClient;

  /**
   * JustGivingCountries constructor.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * @param string $search_text
   *  Input text to search charities.
   * @param integer $max_items
   *  Max number of items returned.
   *
   * @return mixed
   */
  public function charitySearch($search_text, $max_items = 20) {

    if ($this->justGivingClient->jgLoad() == FALSE) {
      return NULL;
    }
    else {
      return $this->justGivingClient->jgLoad()->Search->CharitySearch($search_text, $max_items, 1);
    }
  }

}
