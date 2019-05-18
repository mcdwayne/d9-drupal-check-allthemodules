<?php

namespace Drupal\pagedesigner\Plugin\pagedesigner;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface AssetPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface
{

    /**
     * Processes the node.
     *
     * @param ContentEntityBase $entity
     * @param array $children
     * @return void
     */
    public function get($filter = []);

}
