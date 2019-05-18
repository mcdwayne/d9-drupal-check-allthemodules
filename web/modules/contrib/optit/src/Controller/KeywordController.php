<?php

namespace Drupal\optit\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;
use Optit\Keyword;

/**
 * Provides the keywords page.
 */
class KeywordController extends ControllerBase {

  /**
   * Returns the list of available keywords.
   */
  public function listPage() {

    $optit = Optit::create();

    // Decide page
    $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

    // Run query against the API.
    $keywords = $optit->setPage($page)
      ->keywordsGet();


    $build = [];

    if (count($keywords) == 0) {
      $build['empty'] = [
        '#prefix' => '<div class="empty-page">',
        '#markup' => $this->t('Your keyword list is empty.'),
        '#suffix' => '</div>',
      ];
      return $build;
    }

    // Start building vars for theme_table.
    $header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Type'),
      $this->t('Short code'),
      $this->t('Status'),
      $this->t('Subscription count'),
      $this->t('Actions')
    ];

    $rows = [];

    // Iterate through received keywords and fill in table rows.
    /** @var Keyword $keyword */
    foreach ($keywords as $keyword) {

      // Prepare links for actions column of the list.
      $actions = [];
      $actions[] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('optit.structure_keywords_edit', [
          'keyword_id' => $keyword->get('id')
        ])
      ];
      $actions[] = [
        'title' => $this->t('View subscriptions'),
        'url' => Url::fromRoute('optit.structure_keywords_subscriptions', [
          'keyword_id' => $keyword->get('id')
        ])
      ];
      $actions[] = [
        'title' => $this->t('View interests'),
        'url' => Url::fromRoute('optit.structure_keywords_interests', [
          'keyword_id' => $keyword->get('id')
        ])
      ];
      $actions[] = [
        'title' => $this->t('Send SMS'),
        'url' => Url::fromRoute('optit.structure_keywords_subscriptions_sms', [
          'keyword_id' => $keyword->get('id')
        ])
      ];
      $actions[] = [
        'title' => $this->t('Send MMS'),
        'url' => Url::fromRoute('optit.structure_keywords_subscriptions_mms', [
          'keyword_id' => $keyword->get('id')
        ])
      ];

      $rows[] = array(
        $keyword->get('id'),
        $keyword->get('keyword_name'),
        $keyword->get('keyword_type'),
        $keyword->get('short_code'),
        $keyword->get('status'),
        $keyword->get('mobile_subscription_count'),
        _optit_actions($actions),
      );
    }

    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    // Initialize the pager
    pager_default_initialize($optit->totalPages, 1);
    $build['pager'] = [
      '#theme' => 'pager',
      '#route_name' => \Drupal::service('current_route_match')->getRouteName(),
      '#quantity' => $optit->totalPages,
      '#element' => 0,
      '#parameters' => [],
      '#tags' => [],
    ];

    return $build;
  }
}
