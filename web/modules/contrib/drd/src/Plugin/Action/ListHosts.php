<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'ListHosts' action.
 *
 * @Action(
 *  id = "drd_action_list_hosts",
 *  label = @Translation("List Hosts"),
 *  type = "drd",
 * )
 */
class ListHosts extends ListEntities {

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    $rows = [];

    /** @var \Drupal\drd\Entity\HostInterface $host */
    foreach (parent::prepareSelection()->hosts() as $host) {
      $rows[] = [
        'host-id' => $host->id(),
        'host-label' => $host->label(),
      ];
    }
    return $rows;
  }

}
