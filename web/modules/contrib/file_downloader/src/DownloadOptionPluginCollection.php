<?php

namespace Drupal\file_downloader;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of download option plugins.
 */
class DownloadOptionPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The Download option ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $downloadOptionId;

  /**
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $downloadOptionId) {
    parent::__construct($manager, $instance_id, $configuration);
    $this->downloadOptionId = $downloadOptionId;
  }

  /**
   * {@inheritdoc}
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The download option '{$this->downloadOptionId}' did not specify a plugin.");
    }

    try {
      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      throw $e;
    }
  }

}
