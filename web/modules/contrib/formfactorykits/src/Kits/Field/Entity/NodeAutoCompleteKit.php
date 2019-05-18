<?php

namespace Drupal\formfactorykits\Kits\Field\Entity;

use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class NodeAutoCompleteKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Entity
 */
class NodeAutoCompleteKit extends EntityAutoCompleteKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'node_autocomplete';
  const TARGET_TYPE = 'node';
  const TITLE = 'Node';
  const TARGET_BUNDLES_KEY = 'target_bundles';

  /**
   * @param string $bundle
   *
   * @return static
   */
  public function setTargetBundle($bundle) {
    return $this->setTargetBundles([$bundle]);
  }

  /**
   * @param array $bundles
   *
   * @return static
   */
  public function setTargetBundles(array $bundles) {
    return $this->setSelectionSetting(self::TARGET_BUNDLES_KEY, $bundles);
  }
}
