<?php

namespace Drupal\pdb_ember\Plugin\Block;

use Drupal\pdb\Plugin\Block\PdbBlock;

/**
 * Exposes an Ember component as a block.
 *
 * @Block(
 *   id = "ember_component",
 *   admin_label = @Translation("Ember component"),
 *   deriver = "\Drupal\pdb_ember\Plugin\Derivative\EmberBlockDeriver"
 * )
 */
class EmberBlock extends PdbBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $info = $this->getComponentInfo();
    $machine_name = $info['machine_name'];

    $build = parent::build();
    $build['#allowed_tags'] = [$machine_name];
    $build['#markup'] = '<' . $machine_name . ' id="instance-id-' . $machine_name . '">Test</' . $machine_name . '>';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function attachFramework(array $component) {
    $attached = array();
    $attached['drupalSettings']['ember']['global_injectables'] = array();

    return $attached;
  }

  /**
   * {@inheritdoc}
   */
  public function attachSettings(array $component) {
    $machine_name = $component['machine_name'];
    $uuid = $this->configuration['uuid'];

    $attached = array();
    $attached['drupalSettings']['ember']['components']['instance-id-' . $uuid] = array(
      'uri' => '/' . $component['path'],
      'element' => $machine_name,
    );
    $attached['drupalSettings']['apps'][$machine_name]['uri'] = '/' . $component['path'];

    return $attached;
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibraries(array $component) {
    return array(
      'library' => array(
        'pdb_ember/ember',
        'pdb_ember/app',
      ),
    );
  }

}
