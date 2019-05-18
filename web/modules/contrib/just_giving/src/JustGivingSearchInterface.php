<?php

namespace Drupal\just_giving;

/**
 * Interface JustGivingSearchInterface.
 */
interface JustGivingSearchInterface {

  public function charitySearch($search_text, $max_items);

}
