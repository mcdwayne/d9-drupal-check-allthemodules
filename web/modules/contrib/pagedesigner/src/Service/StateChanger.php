<?php

namespace Drupal\pagedesigner\Service;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\pagedesigner\PagedesignerService;

class StateChanger extends PagedesignerService
{
    /**
     * Publish an entity
     *
     * @param ContentEntityBase $entity
     * @return void
     */
    public function publish(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }
        $entity = $this->_getContainer($entity);
        $this->_output = $this->getHandler($entity)->publish($entity);
        return $this;
    }

    /**
     * Unpublish an entity
     *
     * @param ContentEntityBase $entity
     * @return void
     */
    public function unpublish(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }
        $entity = $this->_getContainer($entity);
        $this->_output = $this->getHandler($entity)->unpublish($entity);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function copy(ContentEntityBase $entity, ContentEntityBase $clone)
    {
        if ($entity == null) {
            return $this;
        }
        $entity = $this->_getContainer($entity);
        $this->_output = $this->getHandler($entity)->copy($entity, $clone);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }
        $this->_output = $this->_getContainer($entity)->id();
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(ContentEntityBase $entity = null)
    {
        if ($entity == null) {
            return $this;
        }
        $entity = $this->_getContainer($entity);
        $this->_output = $this->getHandler($entity)->delete($entity);
        return $this;
    }
}
