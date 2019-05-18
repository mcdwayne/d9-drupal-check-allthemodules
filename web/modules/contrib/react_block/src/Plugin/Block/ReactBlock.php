<?php

namespace Drupal\react_block\Plugin\Block;

use Drupal\pdb\Plugin\Block\PdbBlock;

/**
 * Exposes a React component as a block.
 *
 * @Block(
 *   id = "react_block",
 *   admin_label = @Translation("React Block"),
 *   deriver = "\Drupal\react_block\Plugin\Derivative\ReactBlockDeriver"
 * )
 */
class ReactBlock extends PdbBlock {

  /**
   * {@inheritdoc}
   *
   * @TODO: find a better way to manage unique componentID for both the id of
   *   the element as well as the drupalSettings config storage.
   */
  public function build() {
    $info = $this->getComponentInfo();
    $machine_name = $info['machine_name'];

    $build = parent::build();
    $build['#allowed_tags'] = [$machine_name];
    $build['#allowed_tags'] = ['div'];
    $build['#markup'] = '<div class="' . $machine_name . '" id="' . $machine_name . '"></div>';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function attachSettings(array $component) {
    $machine_name = $component['machine_name'];

    $attached = [];
    $attached['drupalSettings']['react_block'][$machine_name] = [];

    return $attached;
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibraries(array $component) {
    $parent_libraries = parent::attachLibraries($component);

    $framework_libraries = [
      'react_block/react',
      'react_block/react-dom',
      'react_block/redux',
      'react_block/react-redux',
    ];

    $libraries = [
      'library' => array_merge($parent_libraries, $framework_libraries),
    ];

    return $libraries;
  }

}
