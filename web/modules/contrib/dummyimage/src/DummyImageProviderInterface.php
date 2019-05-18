<?php
/**
 * @file
 * Contains \Drupal\dummyimage\DummyImageProviderInterface
 */

namespace Drupal\dummyimage;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface DummyImageProviderInterface extends PluginInspectionInterface {

  public function getName();

  public function getOptions();

  public function getUrl($width, $height);

}