<?php

namespace Drupal\apitools;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for ResponseObject plugins.
 */
interface ResponseObjectInterface extends PluginInspectionInterface {

    /**
     * Set the current base entity.
     *
     * @param EntityInterface $entity
     * @return $this
     */
    public function setEntity(EntityInterface $entity);

    /**
     * Get the current base entity if set.
     *
     * @return EntityInterface;
     */
    public function getEntity();
}
