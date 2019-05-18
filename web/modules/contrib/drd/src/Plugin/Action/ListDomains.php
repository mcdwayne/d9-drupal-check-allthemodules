<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'ListDomains' action.
 *
 * @Action(
 *  id = "drd_action_list_domains",
 *  label = @Translation("List Domains"),
 *  type = "drd",
 * )
 */
class ListDomains extends ListEntities {

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    $rows = [];

    /** @var \Drupal\drd\Entity\DomainInterface $domain */
    foreach (parent::prepareSelection()->domains() as $domain) {
      $rows[] = [
        'domain-id' => $domain->id(),
        'domain-label' => $domain->label(),
        'domain' => $domain->getDomainName(),
        'core-id' => $domain->getCore()->id(),
        'core-label' => $domain->getCore()->label(),
        'host-id' => $domain->getCore()->getHost()->id(),
        'host-label' => $domain->getCore()->getHost()->label(),
      ];
    }
    return $rows;
  }

}
