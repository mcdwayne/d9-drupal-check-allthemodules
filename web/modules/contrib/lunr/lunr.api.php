<?php

/**
 * @file
 * Hooks provided by the Lunr module.
 */

use Drupal\lunr\LunrSearchInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters a Lunr search page.
 *
 * @param array $build
 *   The Lunr search page build.
 * @param \Drupal\lunr\LunrSearchInterface $lunr_search
 *   The Lunr search entity.
 */
function hook_lunr_search_page_alter(array &$build, LunrSearchInterface $lunr_search) {
  // Add a custom input to the search page.
  if ($lunr_search->id() === 'my_page') {
    $build['form']['color'] = [
      '#type' => 'select',
      '#title' => t('Color'),
      '#options' => [
        '' => t('Any'),
        'red' => t('Red'),
        'blue' => t('Blue'),
      ],
      '#attributes' => [
        // See README.txt for more information on facet/field searches.
        'data-lunr-search-field' => 'color',
      ],
    ];
  }
}

/**
 * @} End of "addtogroup hooks".
 */
