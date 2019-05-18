<?php

/**
 * @file
 * Builds placeholder replacement tokens for saved searches.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\search_api\SearchApiException;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function search_api_saved_searches_token_info() {
  $types['search-api-saved-search'] = [
    'name' => t('Search API saved search'),
    'description' => t('Tokens related to a saved search.'),
    'needs-data' => 'search_api_saved_search',
  ];
  $types['search-api-saved-search-results'] = [
    'name' => t('Search API saved search results'),
    'description' => t('Tokens related to a saved search result set.'),
    'needs-data' => 'search_api_results',
  ];

  $tokens['user']['search-api-saved-searches-url'] = [
    'name' => t('Search API Saved Searches configuration URL'),
    'description' => t('The URL under which a user can configure her saved searches.'),
    'type' => 'url',
  ];

  $tokens['search-api-saved-search']['id'] = [
    'name' => t('ID'),
    'description' => t('The ID of the saved search.'),
  ];
  $tokens['search-api-saved-search']['label'] = [
    'name' => t('Label'),
    'description' => t('The label for the saved search.'),
  ];
  $tokens['search-api-saved-search']['owner'] = [
    'name' => t('Owner'),
    'description' => t('The owner of the saved search.'),
    'type' => 'user',
  ];
  $tokens['search-api-saved-search']['created'] = [
    'name' => t('Date created'),
    'description' => t('The date on which the saved search was created.'),
    'type' => 'date',
  ];
  $tokens['search-api-saved-search']['view-url'] = [
    'name' => t('View URL'),
    'description' => t('The URL of the page displaying this search, if any.'),
    'type' => 'url',
  ];
  $tokens['search-api-saved-search']['activate-url'] = [
    'name' => t('Activate URL'),
    'description' => t('The URL where the saved search can be activated (if necessary).'),
    'type' => 'url',
  ];
  $tokens['search-api-saved-search']['edit-url'] = [
    'name' => t('Edit URL'),
    'description' => t('The URL for editing the saved search.'),
    'type' => 'url',
  ];
  $tokens['search-api-saved-search']['delete-url'] = [
    'name' => t('Delete URL'),
    'description' => t('The URL for deleting the saved search.'),
    'type' => 'url',
  ];

  $tokens['search-api-saved-search-results']['count'] = [
    'name' => t('Result count'),
    'description' => t('The number of results.'),
  ];
  $tokens['search-api-saved-search-results']['links'] = [
    'name' => t('Links'),
    'description' => t('Plain-text links to the search results.'),
  ];

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function search_api_saved_searches_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()
      ->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  $replacements = [];

  if ($type === 'user' && !empty($data['user'])) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $data['user'];

    if (isset($tokens['search-api-saved-searches-url'])) {
      $original = $tokens['search-api-saved-searches-url'];
      $replacements[$original] = '';
      try {
        $params = [
          'user' => $user->id(),
        ];
        $url = Url::fromRoute('view.saved_searches.page', $params, $url_options);
        $replacements[$original] = $url;
      }
      catch (\Exception $e) {
        // This will happen when the user deleted the view, or when Views isn't
        // installed – none of which is an error.
      }
    }
  }

  if ($type === 'search-api-saved-search' && !empty($data['search_api_saved_search'])) {
    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    $search = $data['search_api_saved_search'];

    foreach ($tokens as $name => $original) {
      try {
        switch ($name) {
          case 'id':
            $replacements[$original] = $search->id();
            break;

          case 'label':
            $replacements[$original] = $search->label();
            break;

          // Default values for the chained tokens handled below.
          case 'owner':
            $account = $search->getOwner() ?: User::load(0);
            $replacements[$original] = $account->label();
            $bubbleable_metadata->addCacheableDependency($account);
            break;

          // Default values for the chained tokens handled below.
          case 'created':
            $created = $search->get('created')->value;
            $replacements[$original] = \Drupal::getContainer()
              ->get('date.formatter')
              ->format($created, 'medium', '', NULL, $langcode);
            break;

          case 'view-url':
            $url = $search->toUrl('canonical', $url_options);
            $replacements[$original] = $url->toString();
            break;

          case 'activate-url':
            $url = $search->toUrl('activate', $url_options);
            $replacements[$original] = $url->toString();
            break;

          case 'edit-url':
            $url = $search->toUrl('edit-form', $url_options);
            $replacements[$original] = $url->toString();
            break;

          case 'delete-url':
            $url = $search->toUrl('delete-form', $url_options);
            $replacements[$original] = $url->toString();
            break;
        }
      }
      catch (\Exception $e) {
        watchdog_exception('search_api_saved_searches', $e, '%type while generating replacement tokens: @message in %function (line %line of %file).');
      }
    }

    $author_tokens = $token_service->findWithPrefix($tokens, 'owner');
    if ($author_tokens) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $search->getOwner()], $options, $bubbleable_metadata);
    }

    $created_tokens = $token_service->findWithPrefix($tokens, 'created');
    if ($created_tokens) {
      $created = $search->get('created')->value;
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $created], $options, $bubbleable_metadata);
    }
  }

  if ($type === 'search-api-saved-search-results' && !empty($data['search_api_results'])) {
    /** @var \Drupal\search_api\Query\ResultSetInterface $results */
    $results = $data['search_api_results'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'count':
          $replacements[$original] = $results->getResultCount();
          break;

        case 'links':
          $links = [];
          foreach ($results->getResultItems() as $item) {
            try {
              $datasource = $item->getDatasource();
              $object = $item->getOriginalObject();
              $label = $datasource->getItemLabel($object) ?: $item->getId();
              $url = $datasource->getItemUrl($object);
              if ($url) {
                $url->setAbsolute(TRUE);
                $label .= "\n  " . $url->toString();
              }
              $links[] = "- $label";
            }
            catch (SearchApiException $e) {
              watchdog_exception('search_api_saved_searches', $e, '%type while generating replacement tokens: @message in %function (line %line of %file).');
            }
          }
          $replacements[$original] = implode("\n", $links);
          break;
      }
    }
  }

  return $replacements;
}
