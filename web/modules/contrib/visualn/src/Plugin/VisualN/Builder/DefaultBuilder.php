<?php

namespace Drupal\visualn\Plugin\VisualN\Builder;

use Drupal\visualn\Core\BuilderBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\AdapterManager;
use Drupal\visualn\Manager\MapperManager;
use Drupal\visualn\Entity\VisualNStyle;
use Drupal\visualn\Core\DrawerInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Default Builder' VisualN builder.
 *
 * @todo: add description here
 *
 * Builder makes chain from Chain plugins (see @link chain_plugins Chain plugins @endlink topic)
 *
 * Default Builder supposes that Resource has at least one of keys filled-up: base_type or type.
 * The 'type' key is a required one, base_type may be empty.
 *
 *
 * - base_type
 * Can be used by developers to group multiple resource types into groups.
 * This allows to create adapters that handle multiple resouce types by checking 'base_type' key.
 *
 * Base types may serve different purposes with no strict limits or considerations on this subject.
 *
 * - type
 * The type uniquely identifies a resource type with its properties and structure.
 * For each resource type a Resource plugin should be implemented.
 *
 * @ingroup builder_plugins
 *
 * @VisualNBuilder(
 *  id = "visualn_default",
 *  label = @Translation("Default Builder"),
 * )
 */
class DefaultBuilder extends BuilderBase implements ContainerFactoryPluginInterface {

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * The visualn adapter manager service.
   *
   * @var \Drupal\visualn\Manager\AdapterManager
   */
  protected $VisualNAdapterManager;

  /**
   * The visualn mapper manager service.
   *
   * @var \Drupal\visualn\Manager\MapperManager
   */
  protected $VisualNMapperManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.adapter'),
      $container->get('plugin.manager.visualn.mapper')
    );
  }

  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, AdapterManager $visualn_adapter_manager, MapperManager $visualn_mapper_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    //$this->definition = $plugin_definition + $configuration;
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNAdapterManager = $visualn_adapter_manager;
    $this->visualNMapperManager = $visualn_mapper_manager;
  }


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    //$drawing_options = $this->getConfiguration();

    // @todo: add this to the VisualNPluginBase method
    $options = $this->getConfiguration();
    $output_type = $resource->getResourceType();

    // required options: style_id, html_selector
    // add optional options
    $options += [
      'drawer_config' => [],  // optional (drawer default config is considered)
    ];



/*
    // @todo: do we really need style_id here? maybe just pass drawer_plugin_id or both
    //  builder needs to know nothing about the visualn style
    $visualn_style_id = $options['style_id'];
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    if (empty($visualn_style)) {
      return;
    }

    // @todo: do we have chain_plugins_configs here? i.e. in case chain is built for the first time
    //    is chain stored anywhere (in config settings)?
    $drawer = $visualn_style->getDrawerPlugin()->setConfiguration($options['drawer_config']);
*/


    if (!empty($options['visualn_style_id'])) {
      $visualn_style_id = $options['visualn_style_id'];
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      if (empty($visualn_style)) {
        return;
      }
      $drawer = $visualn_style->getDrawerPlugin()->setConfiguration($options['drawer_config']);
    }
    elseif (!empty($options['base_drawer_id'])) {
      $drawer_plugin_id = $options['base_drawer_id'];
      $drawer = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $options['drawer_config']);
      if (empty($drawer)) {
        return;
      }
    }
    else {
      return;
    }

    $drawer->setWindowParameters($this->getWindowParameters());

    //$chain = $this->composePluginsChain($drawer, $input_type, $input_data);
    // @todo: $additional_options
    $chain = $this->composePluginsChain($drawer, $output_type, ['drawer_fields' => $options['drawer_fields']]); // $drawer, $input_type, $input_options
    //$chain = $this->composePluginsChain($drawer, $resource, $drawing_options);
    // @todo: review this interface, additional data may be used e.g. to alter chain building (vuid, drawing_options etc.)
    //$chain = $this->composePluginsChain($drawer, $resource);

    // there could be now drawer after composing chain
    if (empty($chain['drawer'])) {
      return;
    }

    // generally this should be the same drawer as passed into composerPluginsChain()
    //$drawer = $chain['drawer'][0];

    // The $build['#visualn'] array collects data from each plugin (e.g. for data_keys_structure) since
    // it can be required by mappers (e.g. basic_tree_mapper) or adapters.
    // The info is attached to the $build array (instead of using a certain variable)
    // in case it could be required in some non-standard workflow or even anywhere outside VisualN process.
/*
    $build['#visualn'] = [];
*/

    $js_use_found = FALSE;

    // Serverside drawers need data to be already there when called so drawers are called last ones
    $plugin_types = ['adapter', 'mapper', 'drawer'];

    foreach ($plugin_types as $plugin_type) {
      // generally there is one plugin of each kind
      foreach ($chain[$plugin_type] as $k => $chain_plugin) {
        // @todo: or just check if js interface is implemented
        if (!$js_use_found && method_exists($chain_plugin, 'jsId') && is_callable([$chain_plugin, 'jsId'])) {
          // initialize drawings settings storage (to make it explicit here)
          // typically plugins don't need any settings if do not use js handlers (or not?)
          $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid] = [];
          $js_use_found = TRUE;
          // @todo: maybe also attach visualn core js here
        }
        if ($plugin_type == 'adapter' && $k == 0) {
          // @todo: maybe also set data_keys since adapter may need only data keys info
          // @todo: actually drawer_fields, data_keys_structure (and possibly data_keys) should be
          //    set for every type of plugin (adapter, mapper, drawer) since any of them should be
          //    able to do remapping (if it needs to) so these properties should be set
          //    in VisualNPluginBase::defaultConfiguration() and inherited in type-specific classes
          $chain_plugin_config = [
            'drawer_fields' => $options['drawer_fields'] ?: [],
          ] + $chain_plugin->getConfiguration();
          $chain_plugin->setConfiguration($chain_plugin_config);
        }
        elseif ($plugin_type == 'mapper' && $k == 0) {
          // @todo: check if plugin configuration (or default configuration) has data_keys_structure
          //    and maybe drawer_fields keys and only set if parameters are really needed.
          //    Actually these options should be set by linker plugin based on resource and input/output types
          //    or even based on plugin type itself (so that linker could be chosen not only based on
          //    input/output types but also on specific plugins in the chain.
          $chain_plugin_config = [
            'data_keys_structure' => $drawer->dataKeysStructure(),
            'drawer_fields' => $options['drawer_fields'] ?: [],
          ] + $chain_plugin->getConfiguration();
          $chain_plugin->setConfiguration($chain_plugin_config);

          // @todo: set 'data_keys_structure' as a property for mappers and adapters default config
          //    so that it would be an assumption of the workflow
        }
        // @todo: pass Resource into arguments, what to do with the resource, should be uniquely defined by the config
        $resource = $chain_plugin->prepareBuild($build, $vuid, $resource);
      }
    }

/*
    $chain = array_merge($chain['drawer'], $chain['adapter'], $chain['mapper']);
    foreach ($chain as $chain_plugin) {

      // @todo: Implement Linker plugin. Currently the login is implemented in the cycle above.
      //    Linkers would provide data required by specific plugin and would allow users to override default workflow
      //    for specific plugins. Also they make data used by each plugin transparent.
      //    Linkers would need a way to get plugin type, so a corresponding method should be implemented in the
      //    plugins base class.
      //    An example could be "$input_options = Linker($chain_plugin, $build['#visualn'], $options);"
      //

      $resource = $chain_plugin->prepareBuild($build, $vuid, $resource);
    }
*/

    // @todo: or check if implements js interface instead
    // attach js scripts only if there is at least one drawer (or other chain plugin) implementing jsId() method
    $uses_js = $js_use_found;
    if ($uses_js) {
      // this should be generally set though technically plugins may attach libraries to sub keys
      if (!isset($build['#attached']['library'])) {
        $build['#attached']['library'] = [];
      }


      // @todo: attach these libraries and settigns to the beginning of the 'library' array


      // @todo: visualn-core.js must be attached before other visualn js scripts (drawers, mappers, adapters, builders)
      //   to init visualnData variable used by plugins js handlers to register themselves
      //   though some better way should be implemented to avoid it
      // @todo: move into base class or even into dependencies for builder js script and attach it there instead of end of method function
      // @todo: maybe pass VisualN variable to drawer scpripts as it is done for Drupal global variable

      //$build['#attached']['library'][] = 'visualn/visualn-core';
      //array_unshift($build['#attached']['library'], 'visualn/visualn-core');
      $build['#attached']['library'] = array_merge(['visualn/visualn-core'], $build['#attached']['library']);



      //$build['#attached']['drupalSettings']['visualn']['drawings'][$vuid] = [];
      // @todo: check the way it is used, add a comment
      $builder_id = 'visualnDefaultBuilder';
      $build['#attached']['drupalSettings']['visualn']['handlerItems']['builders'][$builder_id][$vuid] = $vuid;








      $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['html_selector'] = $options['html_selector'];
      // Attach visualn builder js script.
      $build['#attached']['library'][] = 'visualn/builder-default';

      // @todo: set visualn/core library as a dependency (?)
    }

    return $resource;
  }

  /**
   * @inheritdoc
   *
   * @todo: move to interface and maybe rename
   */
  protected function composePluginsChain(DrawerInterface $drawer, $input_type, array $input_options, $input_base_type = '') {
    // The arrays are used to allow multiple plugins of each type in the chain
    // though generally this isn't used and wasn't tested. In most cases
    // this seems to have no sense (at least for drawer plugins).
    $chain = ['drawer' => [$drawer], 'mapper' => [], 'adapter' => []];

    $drawer_input = $drawer->getPluginDefinition()['input'];

    $mapper_required = count(array_filter($input_options['drawer_fields']));
    // return chain if drawer doesn't need adapters or mappers
    if ($drawer_input == $input_type && !$mapper_required) {
      return $chain;
    }



    // get all adapter candidates
    $matched_adapter_groups = [];
    $adapterDefinitions = $this->visualNAdapterManager->getDefinitions();
    foreach ($adapterDefinitions as $adapter_id => $definition) {
      if ($definition['input'] == $input_type) {
        $matched_adapter_groups[] = [
          'adapters' => [$adapter_id],
          'output' => $definition['output'],
          // actually input type is not used anywhere below but keep it for consistency
          // with suggestested subchains structure
          'input' => $definition['input'],
        ];
      }
    }


    // get suggested adapter subchains
    $subchain_suggestions = [];
    // Call modules that implement the hook, and let them add items.
    // @todo: add hook description into api documentation
    \Drupal::moduleHandler()->alter('visualn_adapter_subchains', $subchain_suggestions);
    // @todo: maybe pass additional data to the hook or cached the info (maybe statically)
    //    if moduleHandler doesn't do it itself

    $adapter_subchain_candidates = [];
    foreach ($subchain_suggestions as $subchain_suggestion) {
      if ($subchain_suggestion['input'] == $input_type) {
        $adapter_subchain_candidates[] = $subchain_suggestion;
      }
    }
    // @todo: then compare as it was done for generic adapters
    //    if a subchain fits into conditions, use it instead of single adapter
    //    or even attach subchains to the adapters list and check the altogether

    $matched_adapter_groups = array_merge($matched_adapter_groups, $adapter_subchain_candidates);


    // no need to get mappers if drawer keys mapping configuration is empty
    //    and chain can be built using only adapters

    if (!$mapper_required) {
      // try to build a no-mapper chain
      foreach ($matched_adapter_groups as $adapters_group) {
        if ($adapters_group['output'] == $drawer_input) {
          foreach ($adapters_group['adapters'] as $adapter_id) {
            $chain['adapter'][] = $this->visualNAdapterManager->createInstance($adapter_id, []);
          }

          // return chain if matching adapter found
          return $chain;
        }
      }
      // Sometimes, when keys mapping itself is not needed, a mapper may still be used
      // e.g.  when it provides some features which is usually done by adapters
      // i.e. changes resource type
    }


    // get the list of adapter groups output types, intup type is already all the same

    // @todo: There may be multiple adapters (groups) with the same input and output types
    //   so priorities should be set. Only the first one is used (see array_search below).
    $adapters_subchain_lst = array_column($matched_adapter_groups, 'output');





    // get all mapper candidates
    $matched_mappers = [];
    $mapperDefinitions = $this->visualNMapperManager->getDefinitions();
    foreach ($mapperDefinitions as $mapper_id => $definition) {
      if ($definition['output'] == $drawer_input) {
        $matched_mappers[$mapper_id] = $definition['input'];
      }
    }

    // choose matching adapters and mappers for the chain
    $join_types = array_intersect($adapters_subchain_lst, $matched_mappers);


    if (!empty($join_types)) {
      // @todo: Multiple different join types are possible here that
      //   define in result multiple chains. There should be some criteria to choose
      //   an optimal chain but not just the first matched.
      $join_type = reset($join_types);
      $adapters_group_key = array_search($join_type, array_column($matched_adapter_groups, 'output'));
      // @todo: move into method ::loadGroup($group, $chain_plugin_type)
      $adapters_group = $matched_adapter_groups[$adapters_group_key];
      foreach ($adapters_group['adapters'] as $adapter_id) {
        $chain['adapter'][] = $this->visualNAdapterManager->createInstance($adapter_id, []);
      }

      $mapper_id = array_search($join_type, $matched_mappers);
      $chain['mapper'][] = $this->visualNMapperManager->createInstance($mapper_id, []);
    }
    else {
      if (!empty($matched_adapter_groups) || !empty($matched_mappers)) {
        // @todo: there is a question which one to choose
        //  here we may have two possibilities: an adapter or a mapper serves as both, adapter and mapper
        //  @todo: first check mappers since no much sense in checking adapters (see comment below)


        $result_mappers = array_keys($matched_mappers, $drawer_input);
        $result_adapters = array_keys(array_column($matched_adapter_groups, 'output'), $drawer_input);
        if (!empty($result_mappers)) {
          $mapper_id = $result_mappers[0];
          $chain['mapper'][] = $this->visualNMapperManager->createInstance($mapper_id, []);
        }
        elseif (!empty($result_adapters)) {
          // @todo: Do not rely on adapters when mapping is required since adapters don't have to
          //   do it and there is no flag that would show whether adapters do remapping or not
          //   and the result of using the chain for building drawing would become unpredictable
          //   which is wrong for the point of view of chain builder concept where
          //   it MUST return a valid chain or an empty chain.
          //
          //   Though it is ok to use mappers without adapters (see the code below)
          //   because it is seen from mapper plugins annotation.
          /*
            $adapters_group_key = reset($result_adapters);
            $adapters_group = $matched_adapter_groups[$adapters_group_key];
            foreach ($adapters_group['adapters'] as $adapter_id) {
              $chain['adapter'][] = $this->visualNAdapterManager->createInstance($adapter_id, []);
            }
          */
        }

      }
    }



    // if source output is equal to drawer input (e.g. no need in mapper or adapter)
    // else empty the chain (no drawing will be drawn)
    if (empty($chain['adapter']) && empty($chain['mapper'])) {
      // @todo: Exclude case when mapping is requried but only drawer is used
      //   since the result of the drawing building would become unpredictable,
      //   see the same notice for adapters above.
      /*
        if (empty($chain['adapter']) && empty($chain['mapper']) && $drawer_input != $input_type) {
      */
      $chain = ['drawer' => [], 'mapper' => [], 'adapter' => []];

      // @todo: check base type and try to compose chain based on it, currently it is
      //   accomplished using suggested subchains mechanism
      if (!$input_base_type) {
        $input_base_type = $this->getBaseTypeByType($input_type);
      }

      if ($input_base_type) {
      }
    }

    // @todo: cache chains

    return $chain;
  }

  // @todo:
  protected function composeSuggestedPluginsChain(DrawerInterface $drawer, $input_type, array $input_options, $input_base_type = '') {
  }

  /**
   * @inheritdoc
   *
   * @todo: move to interface and maybe rename
   */
  protected function getBaseTypeByType($input_type) {

    $input_base_type = '';


    // @todo: try to define base type by input (generally, resouce) type
    //    maybe this should use some kind of external service since refers
    //    to resources by not chains themselves.
    //    Also such a service could scan certain yml files or allow other modules
    //    to alter info about which resource types belong to which resource base types.


    return $input_base_type;
  }

}
