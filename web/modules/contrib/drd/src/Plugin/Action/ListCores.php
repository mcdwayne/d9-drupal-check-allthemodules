<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'ListCores' action.
 *
 * @Action(
 *  id = "drd_action_list_cores",
 *  label = @Translation("List Cores"),
 *  type = "drd",
 * )
 */
class ListCores extends ListEntities {

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    $rows = [];

    /** @var \Drupal\drd\Entity\CoreInterface $core */
    foreach (parent::prepareSelection()->cores() as $core) {
      $rows[] = [
        'core-id' => $core->id(),
        'core-label' => $core->label(),
        'host-id' => $core->getHost()->id(),
        'host-label' => $core->getHost()->label(),
      ];
    }
    return $rows;
  }

}
