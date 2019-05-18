<?php

namespace Drupal\loopit\Aggregate;

use Drupal\Component\Utility\NestedArray;
/**
 * @todo comments
 */
class AggregatePlugin extends AggregateArray {

  public static function getClasses($leaf_match) {

    if (!isset($leaf_match)) {
      $leaf_match = ['*class' => '*', '*provider' => '*', '*deriver' => '*'];
    }

    AggregateFilter::$context['system.module.files'] = \Drupal::state()->get('system.module.files');

    $options = [];
    $options['onCurrent'][] = AggregateFilter::class . '::onCurrentSubsetArrayParents';
    $options['subset_array_parents'] = [
        '\*services/plugin.manager.*/\*definitions' => $leaf_match,
        '\*services/plugin.manager.*/definitions' => $leaf_match,
        'services/plugin.manager.*/definitions' => $leaf_match,
    ];
    $aggreg = self::createInstance(static::getClassesInfo(), $options);
    $aggreg->setIteratorClass('Drupal\loopit\Iterator\AggregateFilterIterator');
    // Init context variables
    $aggreg->context += array_fill_keys(['plugin_id_centric', 'plugin_class_centric', 'plugin_handler_shared', 'plugin_handler_unique'], []);
    // onLeafClasses uses $ths->context so add this option once we have the instance
    $aggreg->options['onLeaf'][] = [$aggreg, 'onLeafClasses'];

    $iter = $aggreg->getIterator();
    // TODO: To test with ::CHILD_FIRST, ::LEAVES_ONLY
    foreach (new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {}
    ksort($aggreg->context['plugin_id_centric']);

    return $aggreg;
  }

  public static function getClassesInfo() {

    $cid = 'loopit:aggregate:plugin_definitions';
    $cache = \Drupal::cache()->get($cid);
    if ($cache) {
      $aggregate_classes = $cache->data;
    }
    else {

      $options = [];
      $aggregate_services = \Drupal::getContainer();
      $aggregate_services_casted = AggregateObject::castFast($aggregate_services);
      $aggregate_services_casted['services'] = array_diff_key($aggregate_services_casted['*serviceDefinitions'], $aggregate_services_casted['*services']);

      $options = [];
      $options['onCurrent'][] = AggregateService::class . '::onCurrentServiceUnserialize';
      $options['onCurrent'][] = AggregateFilter::class . '::onCurrentSubsetArrayParents';
      $options['subset_array_parents'] = [
        '\*services/plugin.manager.*/\*definitions' => NULL,
        '\*services/plugin.manager.*/definitions' => NULL,
        'services/plugin.manager.*/definitions' => NULL,
      ];
      $aggreg = self::createInstance($aggregate_services_casted, $options);
      $aggreg->setIteratorClass('Drupal\loopit\Iterator\AggregateFilterIterator');
      // Keep the root level of the input available for all levels
      $aggreg->context['aggregate_services_casted'] = $aggregate_services_casted;
      $aggreg->options['onCurrent'][] = [$aggreg, 'onCurrentFromArrayParents'];

      $iter = $aggreg->getIterator();
      // TODO: To test with ::CHILD_FIRST, ::LEAVES_ONLY
      foreach (new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {}

      $aggregate_classes = $aggreg->getCacheNested();
      \Drupal::cache()->set($cid, $aggregate_classes);

    }

    return $aggregate_classes;
  }

  /**
   * Get the plugin definition values of the plugin manager services.
   *
   * @param AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public function onCurrentFromArrayParents($current, $index) {
    $defs = NULL;

    $aggregate = $this;

    $defs_to_cast = [];
    // TODO: Move to options
    $defs_service_errors = [
      'plugin.manager.ctools.relationship',
    ];
    $classes_parents_additionals = [
      'plugin.manager.tmgmt.source' => [
        ['ui']
      ]
    ];

    if ($current && is_array($current)) {
      $parents = $aggregate->getArrayParents();
      if ($parents === ['*services'] || $parents === ['services']) {
        if (isset($current['__ARRAY_PARENTS__'])) {
          $parents_to_get = $current['__ARRAY_PARENTS__'];

          $to_add = NestedArray::getValue($this->context['aggregate_services_casted'], $parents_to_get);
          if (isset($to_add['*definitions'])) {
            $current['*definitions'] = $to_add['*definitions'];
          }
        }

        if (!isset($current['*definitions']) && !in_array($index, $defs_service_errors)) {
          $defs = \Drupal::service($index)->getDefinitions();
        }
      }
    }

    if ($defs) {

      foreach ($defs as $plugin => $def) {

        // Cast takes four times slower. Use it some times to check if there
        // is new info to gather
        if (!isset($defs_to_cast) || in_array($index, $defs_to_cast)) {
          $def = $current['definitions'][$plugin] = AggregateObject::castFast($def);
          $def_cast = TRUE;
        }
        else {
          $def_cast = FALSE;
          // Some time $def is an object.
          // ex. plugin.manager.core.layout
          if (is_object($def)) {
            $def_obj = $def;
            $def = ['__CLASS__' => get_class($def_obj)];
            if (method_exists($def_obj, 'getProvider')) {
              $def['provider'] = $def_obj->getProvider();
            }
            if (method_exists($def_obj, 'getDeriver')) {
              $def['deriver'] = $def_obj->getDeriver();
            }
            if (method_exists($def_obj, 'getClass')) {
              $def['class'] = $def_obj->getClass();
            }
          }

          // Get class, deriver, provider info
          foreach ($def as $def_name => $def_value) {
            // Mandatory
            if (in_array($def_name, ['class'])) {
              $current['definitions'][$plugin][$def_name] = $def[$def_name];
            }
            // Optional
            if (in_array($def_name, ['deriver', 'provider', '__CLASS__'])) {
              if (isset($def[$def_name])) {
                $current['definitions'][$plugin][$def_name] = $def[$def_name];
              }
            }

            // Gather additional plugin classes with properties that ends with
            // "_class"
            $ends_with = '_class';
            $ends_with_count = strlen($ends_with);
            if(substr($def_name, -$ends_with_count) === $ends_with) {
              $current['definitions'][$plugin][$def_name] = $def[$def_name];
            }
          }

          // TODO: use option like $classes_parents_additionals:
          //  'ui' => 'ui/class'
          //  so:
          //  context/*/__CLASS__ => context/*/class
          if (/*$index == 'plugin.manager.condition' && */ isset($def['context'])) {
           foreach ($def['context'] as $context_name => $context_value) {
             $current['definitions'][$plugin]['context'][$context_name]['__CLASS__'] = get_class($context_value);
           }
          }
        }

        // Look for additional classes to gather
        if (isset($classes_parents_additionals[$index])) {
          foreach ($classes_parents_additionals[$index] as $classes_parents_additional) {
            $additional_class = NestedArray::getValue($def, $classes_parents_additional);
            NestedArray::setValue($current['definitions'][$plugin], $classes_parents_additional, ['class' => $additional_class]);
          }
        }

        // Try to guess provider from classname
        if (!isset($def['provider']) && !isset($def['*provider'])) {

          $def_class = NULL;
          if(isset($def['class'])) {
            $def_class = $def['class'];
          }
          else if(isset($def['*class'])) {
            $def_class = $def['*class'];
          }

          $dr_ns_len = 7;
          // Is a namespace class name
          if (strpos($def_class, 'Drupal\\') === 0) {
            //$string = substr($string, $dr_ns_len);
            if (($pos = strpos($def_class, '\\', $dr_ns_len)) !== FALSE) {
              $dr_ns = substr($def_class, $dr_ns_len, $pos-$dr_ns_len);
              $def['provider'] = in_array($dr_ns, ['Core', 'Component']) ? 'core' : $dr_ns;
              $current['definitions'][$plugin]['provider'] = $def['provider'];
            }
          }

          // TODO: log that case
          if (!isset($def['provider'])) {
            dpm($index . ' $plugin: ' . $plugin);
            dpm($def);
          }
        }

        // Sort to can compare casted to not casted.
        ksort($current['definitions'][$plugin]);
        // Put __CLASS__ key at the beginning because of protected "*" when sorting of casted.
        if ($def_cast && isset($current['definitions'][$plugin]['__CLASS__'])) {
          unset($current['definitions'][$plugin]['__CLASS__']);
          $current['definitions'][$plugin] = ['__CLASS__' => $def['__CLASS__']] + $current['definitions'][$plugin];
        }
      }
    }

    return $current;
  }

  /**
   * Do on leaf transformation for plugin classes.
   *
   * @param Drupal\loopit\Aggregate\AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public function onLeafClasses($current, $index) {

    $aggregate = $this;

    // Nothong to do for "__HASH__" key
    if ($index == '__HASH__') {
      return $current;
    }
    $parents = $aggregate->getArrayParents();

    // Some cleaning for __CLASS__ when markup
    if ($index == '__CLASS__' && $current == 'Drupal\Core\StringTranslation\TranslatableMarkup') {
      return $current;
    }

    $parents[] = $index;

    // Nothong to do if there is "__ARRAY_PARENTS__" parent
    if (in_array('__ARRAY_PARENTS__', $parents)) {
      return $current;
    }

    // The first parent is the "service" key: drop it.
    array_shift($parents);
    if (!$parents) {
      return $current;
    }

    $current = ltrim($current, '\\');

    // The next parent is the "service id".
    $service_id = array_shift($parents);

    // Drop the next parent if "definitions" key.
    $def_key = reset($parents);
    if (in_array($def_key, ['definitions', '*definitions'])) {
      array_shift($parents);
    }
    // The plugin id
    $plugin_id = array_shift($parents);

    if ($parents) {
      // Concat remaining parents as a $plugin_handler_id
      $plugin_handler_id = array_reduce($parents, function($carry, $item) {
        // Drop .__CLASS__ for pretty
        if ($carry && $item == '__CLASS__') {
          return $carry;
        }
        return $carry . ($carry? '.' : '') . trim($item, '*');
      });

      $this->context['plugin_id_centric'][$service_id][$plugin_id][$plugin_handler_id] = $current;
    }
    // Here $plugin_id is __CLASS__
    else {
      $plugin_handler_id = NULL;
      $this->context['plugin_id_centric'][$service_id][$plugin_id] = $current;
    }

    // TODO: Needs some refactoring
    if (!in_array($index, ['provider'])) {

      $is_deriver = strpos($plugin_id, ':') !== FALSE;
      $parents_value = $plugin_handler_id ? $service_id . '.' . $plugin_id : $service_id;

      // Plugin manager and plugin type classes are generally unique by manager
      // and by plugin type
      if (
          // The manager class for the plugin type
          ($plugin_manager_reuse = !$plugin_handler_id) // && $index == '__CLASS__')
          // The plugin type implementation class. For deriver shoud not be unique
          // TODO: deriver can also be unique:
          // ex. Drupal\node\Plugin\EntityReferenceSelection\NodeSelection
          || !$is_deriver && $index == 'class' && $plugin_handler_id // $parents_out == [$plugin_id]
        ) {

        $handler_id = 'plugin_' . ($plugin_manager_reuse ? 'manager' : 'type');

        // Track when a plugin manager or a plugin type class is reused
        if (isset($this->context['plugin_class_centric'][$current]['id'])) {
          if (!is_array($this->context['plugin_class_centric'][$current]['id'])) {
            $this->context['plugin_class_centric'][$current]['id'] =
             $this->context['plugin_handler_shared'][$handler_id][$current] =
              [
                $this->context['plugin_class_centric'][$current]['id'] => $this->context['plugin_class_centric'][$current]['id']
              ];
             // Drop from unique
             unset($this->context['plugin_handler_unique'][$current]);
          }
          $this->context['plugin_class_centric'][$current]['id'][$parents_value] = $parents_value;
          $this->context['plugin_handler_shared'][$handler_id][$current][$parents_value] = $parents_value;
        }
        else {
          $this->context['plugin_class_centric'][$current]['id'] = $parents_value;
          $this->context['plugin_handler_unique'][$current] = $parents_value;
        }
      }
      else {
        //$value = reset($parents_tmp);
        $parents_value .= '.' . $plugin_handler_id;
        $ends_with = '.' . $index;
        $ends_with_count = strlen($ends_with);
        if(substr($parents_value, -$ends_with_count) === $ends_with) {
          $parents_value = substr($parents_value, 0, -strlen($ends_with));
        }

        $class_type_key = $index == '__CLASS__' ? 'class' : $index;
        $this->context['plugin_handler_shared']['plugin_' . $class_type_key][$current][$parents_value] = $parents_value;
        $this->context['plugin_class_centric'][$current][$class_type_key . '_for'][$parents_value] = $parents_value;

      }
    }

    return $current;
  }
}