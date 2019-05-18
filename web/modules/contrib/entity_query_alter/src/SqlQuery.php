<?php

namespace Drupal\entity_query_alter;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\Query\Sql\Query;

class SqlQuery extends Query implements AlterableInterface {

  /**
   * @inheritDoc
   */
  public function execute() {
    $this->alter();
    return parent::execute();
  }

  /**
   * Allow alteration of the entity query prior to execution.
   */
  protected function alter() {
    // Simulate the metadata that is set directly on the sql query.
    // @see parent::prepare()
    // These will get re-added in the above method, but that's not a huge deal.
    $this->addMetaData('entity_type', $this->entityTypeId);
    if ($this->accessCheck) {
      $this->addTag($this->entityTypeId . '_access');
    }
    $this->addTag('entity_query');
    $this->addTag('entity_query_' . $this->entityTypeId);
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
    $eventDispatcher = \Drupal::service('event_dispatcher');
    $event = new EntityQueryAlterEvent($this);
    $eventDispatcher->dispatch(EntityQueryAlterEvents::ALTER, $event);
  }

}
