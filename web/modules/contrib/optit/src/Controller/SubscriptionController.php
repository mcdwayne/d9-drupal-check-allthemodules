<?php

namespace Drupal\optit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\optit\Optit\Subscription;
use Drupal\optit\Optit\Optit;

/**
 * Provides the subscriptions page.
 */
class SubscriptionController extends ControllerBase {

  /**
   * Returns the list of available interests.
   */
  public function listPage($keyword_id, $interest_id = NULL) {

    $optit = Optit::create();

    // Decide page
    $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

    // Run query against the API.
    if (!$interest_id) {
      $subscriptions = $optit->setPage($page)
        ->subscriptionsGet($keyword_id);
    }
    else {
      $subscriptions = $optit->setPage($page)
        ->interestGetSubscriptions($interest_id);
    }

    $build = [];

    if (count($subscriptions) == 0) {
      $build['empty'] = [
        '#prefix' => '<div class="empty-page">',
        '#markup' => $this->t('Your subscription list is empty.'),
        '#suffix' => '</div>',
      ];

      return $build;
    }

    // Start building vars for theme_table.
    $header = [
      $this->t('Member ID'),
      $this->t('Phone'),
      $this->t('Subscription type'),
      $this->t('Signup date'),
      $this->t('Created at'),
      $this->t('Actions')
    ];

    $rows = [];

    // Iterate through received keywords and fill in table rows.
    /** @var Subscription $subscription */
    foreach ($subscriptions as $subscription) {

      // Prepare links for actions column of the list.
      $actions = [];

      if (!$interest_id) {
        $actions[] = array(
          'title' => t('Unsubscribe'),
          'url' => Url::fromRoute('optit.structure_keywords_subscriptions_unsubscribe', [
            'keyword_id' => $keyword_id,
            'phone' => $subscription->get('phone'),
          ])
        );
        $actions[] = array(
          'title' => t('Send message'),
          'url' => Url::fromRoute('optit.structure_keywords_subscriptions_sms_phone', [
            'keyword_id' => $keyword_id,
            'phone' => $subscription->get('phone'),
          ])
        );
      }
      else {
        $actions[] = array(
          'title' => t('Unsubscribe'),
          'url' => Url::fromRoute('optit.structure_members_interest_unsubscribe', [
            'phone' => $subscription->get('phone'),
            'interest_id' => $interest_id,
          ])
        );
      }

      $vars['rows'][] = [];

      $rows[] = [
        $subscription->get('member_id'),
        $subscription->get('phone'),
        $subscription->get('type'),
        optit_time_convert($subscription->get('signup_date')),
        optit_time_convert($subscription->get('created_at')),
        _optit_actions($actions),
      ];
    }

    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

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


  function listByInterestPage() {

  }
}
