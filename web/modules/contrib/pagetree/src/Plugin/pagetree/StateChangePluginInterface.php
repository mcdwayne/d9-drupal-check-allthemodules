<?php

namespace Drupal\pagetree\Plugin\pagetree;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface StateChangePluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface
{

    public function publish(ContentEntityBase &$entity, $message);

    public function unpublish(ContentEntityBase &$entity, $message);

    public function copy(ContentEntityBase &$entity, ContentEntityBase &$clone = null);

    public function generate(ContentEntityBase &$entity);

    public function delete(ContentEntityBase &$entity);

    public function hasChanges(ContentEntityBase &$entity);
}
