<?php

namespace Drupal\optit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;

/**
 * Provides the subscriptions page.
 */
class MemberController extends ControllerBase {

  /**
   * Returns the list of available interests.
   */
  public function listPage() {

    $optit = Optit::create();

    // Decide page
    $page = (isset($_GET['page']) ? $_GET['page'] + 1 : 1);

    // Run query against the API.
    $entities = $optit->setPage($page)->membersGet();

    $build = [];

    if (count($entities) == 0) {
      $build['empty'] = [
        '#prefix' => '<div class="empty-page">',
        '#markup' => $this->t('Your member list is empty.'),
        '#suffix' => '</div>',
      ];

      return $build;
    }

    // Start building vars for theme_table.
    $header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Carrier'),
      $this->t('Phone'),
      $this->t('Created at'),
      $this->t('Status'),
      $this->t('Actions')
    ];

    $rows = [];

    // Iterate through received interests and fill in table rows.
    foreach ($entities as $entity) {
      // Prepare links for actions column of the list.
      $actions = array();
      $actions[] = array(
        'title' => $this->t('Unsubscribe all'),
        'url' => Url::fromRoute('optit.structure_members_unsubscribe', [
          'phone' => $entity->get('phone'),
        ]),
      );

      $rows[] = [
        $entity->get('id'),
        $entity->get('first_name') . ' ' . $entity->get('last_name'),
        $entity->get('carrier_name'),
        $entity->get('phone'),
        optit_time_convert($entity->get('created_at')),
        $entity->get('status'),
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
}
