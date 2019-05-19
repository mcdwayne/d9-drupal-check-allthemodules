<?php

// @todo: rename to DefaultJsMapper

namespace Drupal\visualn\Plugin\VisualN\Mapper;

use Drupal\visualn\Core\MapperWithJsBase;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Default Mapper' VisualN mapper.
 *
 * @ingroup mapper_plugins
 *
 * @VisualNMapper(
 *  id = "visualn_default",
 *  label = @Translation("Default Mapper"),
 *  input =  "generic_js_data_array",
 *  output =  "generic_js_data_array",
 * )
 */
class DefaultMapper extends MapperWithJsBase {

  /**
   * {@inheritdoc}
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // mapper specific js settings
    $dataKeysMap = $this->configuration['drawer_fields'];  // here need both keys and values for remapping values
    // @todo: exclude this settings for views
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysMap'] = $dataKeysMap;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/default-mapper';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnDefaultMapper';
  }

}
