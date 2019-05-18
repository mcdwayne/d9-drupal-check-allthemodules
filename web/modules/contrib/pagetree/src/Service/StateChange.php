<?php

namespace Drupal\pagetree\Service;

use Drupal\Core\Entity\ContentEntityBase;

class StateChange
{

    /*
     * Publish a page and all rows, components and contents within.
     *
     * Returns a tree array of all published node and revision ids.
     *
     * @return The published node and revision ids.
     */
    public function publish(ContentEntityBase &$entity, $message)
    {
        $handlers = \Drupal::service('plugin.manager.pagetree_handler')->getHandlers();
        foreach ($handlers as $handler) {
            $handler->publish($entity, $message);
        }
    }

    /*
     * Publish a page and all rows, components and contents within.
     *
     * Returns a tree array of all published node and revision ids.
     *
     * @return The published node and revision ids.
     */
    public function unpublish(ContentEntityBase &$entity, $message)
    {
        $handlers = \Drupal::service('plugin.manager.pagetree_handler')->getHandlers();
        foreach ($handlers as $handler) {
            $handler->unpublish($entity, $message);
        }
    }

    /*
     * Copy a page and all rows, components and contents within.
     *
     * Returns the cloned page.
     *
     * @return The published node and revision ids.
     */
    public function copy(ContentEntityBase &$entity, $message)
    {
        $clone = null;
        $handlers = \Drupal::service('plugin.manager.pagetree_handler')->getHandlers();
        foreach ($handlers as $handler) {
            $clone = $handler->copy($entity, $clone);
        }
        return $clone;
    }
}
