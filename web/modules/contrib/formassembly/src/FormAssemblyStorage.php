<?php

namespace Drupal\formassembly;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Entity storage class FormAssemblyStorage.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class FormAssemblyStorage extends SqlContentEntityStorage {

  /**
   * Sets status 0 for all faid not in the active array.
   *
   * @param array $faidEnabled
   *   The name of the entity property.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function disableInactive(array $faidEnabled) {
    $query = $this->database->select($this->baseTable, 'fa');
    $query->addField('fa', 'id');
    $query->condition('faid', $faidEnabled, 'NOT IN');
    $missingForms = $query->execute()->fetchCol();
    $toDisable = $this->loadMultiple($missingForms);
    foreach ($toDisable as $entity) {
      /** @var \Drupal\formassembly\Entity\FormAssemblyEntity $entity */
      $entity->disable()->save();
    }
  }

}
