<?php

namespace Drupal\pagetree\Plugin\pagetree;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface StatePluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface
{
    public function annotate(&$entries);
}
