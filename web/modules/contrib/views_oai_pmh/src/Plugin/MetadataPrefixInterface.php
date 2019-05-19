<?php

namespace Drupal\views_oai_pmh\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for OAI-PMH Metadata Prefix plugins.
 */
interface MetadataPrefixInterface extends PluginInspectionInterface {

  /**
   *
   */
  public function getElements(): array;

  /**
   *
   */
  public function getRootNodeName(): string;

  /**
   *
   */
  public function getRootNodeAttributes(): array;

  /**
   *
   */
  public function getSchema(): string;

  /**
   *
   */
  public function getNamespace(): string;

}
