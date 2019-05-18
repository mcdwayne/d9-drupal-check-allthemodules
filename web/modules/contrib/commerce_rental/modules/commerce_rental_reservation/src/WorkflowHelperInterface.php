<?php

namespace Drupal\commerce_rental_reservation;

use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;

interface WorkflowHelperInterface {

  /**
   * @param \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface $state
   *
   * @return array|null
   */
  public function getStateDefinition(StateItemInterface $state);

  /**
   * @param \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface $state
   *
   * @return int|null
   */
  public function getStatePriority(StateItemInterface $state);

  /**
   * @param \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface $state
   *
   * @return boolean|null
   */
  public function isStateBlocking(StateItemInterface $state);


}