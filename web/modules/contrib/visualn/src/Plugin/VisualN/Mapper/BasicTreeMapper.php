<?php

namespace Drupal\visualn\Plugin\VisualN\Mapper;

use Drupal\visualn\Core\MapperWithJsBase;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Basic Tree Mapper' VisualN mapper.
 *
 * @todo: the mapper may be later removed
 *
 * @ingroup mapper_plugins
 *
 * @VisualNMapper(
 *  id = "visualn_basic_tree",
 *  label = @Translation("Basic Tree Mapper"),
 *  input = "generic_js_data_array",
 *  output = "visualn_basic_tree_input",
 * )
 */
class BasicTreeMapper extends MapperWithJsBase {

  // used to build mapper plugins chain
  // @todo: find better terms here instead of input and output keys
  //    because this leads to misunderstanding
  // @todo: maybe this should return arrays (optional) to support multiple
  //    input and output format types (e.g. visualn_generic_input and visualn_generic_output for "output" key), or provide groups of formats structures somewhere else
  //    @note: visualn_generic_input and visualn_generic_output where replaced by generic_js_data_array type
  // @todo: e.g. visualn_plain -> visualn_plain with/without keys remapping/renaming

  /**
   * {@inheritdoc}
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // mapper specific js settings
    $dataKeysMap = $this->configuration['drawer_fields'];  // here need both keys and values for remapping values
    $dataKeysStructure = $this->configuration['data_keys_structure'];

    // process data keys structure to attach a cleaner settings tree to js
    $this->prepareJSKeysStructure($dataKeysStructure);

    // @todo: exclude this settings for views
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysMap'] = $dataKeysMap;
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysStructure'] = $dataKeysStructure;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/basic-tree-mapper';

    return $resource;
  }

  /**
   * Prepare keys structure to attach to js settings.
   */
  protected function prepareJSKeysStructure(array &$dataKeysStructure) {
    foreach($dataKeysStructure as $k => $v) {
      if (is_array($v)) {
        if (!isset($v['mapping'])) {
          $dataKeysStructure[$k]['mapping'] = $k;
        }
        if (!isset($v['typeFunc'])) {
          $dataKeysStructure[$k]['typeFunc'] = '';
        }
        if (!isset($v['structure'])) {
          $dataKeysStructure[$k]['structure'] = [];
        }
        else {
          $this->prepareJSKeysStructure($dataKeysStructure[$k]['structure']);
        }
      }
      else {
        $dataKeysStructure[$k] = [
          'mapping' => $v,
          'structure' => [],
          'typeFunc' => '',
        ];
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnBasicTreeMapper';
  }

}
