<?php

/**
 * Created by PhpStorm.
 * User: andy
 * Date: 15/01/2016
 * Time: 23:12
 */

namespace Drupal\subsite;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

interface SubsitePluginInterface extends ConfigurablePluginInterface, PluginFormInterface {
  public function blockPrerender($build, $node, $subsite_node);
  public function nodeViewAlter(array &$build, EntityInterface $node, EntityViewDisplayInterface $display);
  public function pageAttachmentsAlter(array &$attachments);
}