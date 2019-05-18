<?php

namespace Drupal\loopit\Aggregate;

use Drupal\Component\Utility\NestedArray;

/**
 * @todo comments
 */
class AggregateService extends AggregateArray {

  /**
   * Unserialize not instanciated services.
   *
   * @param AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public static function onCurrentServiceUnserialize(AggregateArray $aggregate, $current, $index) {
    if ($current && $aggregate->getArrayParents() === ['services']) {
      $unserialize = unserialize($current);
      $current = ['__CLASS__'=> $unserialize['class']];
    }

    return $current;
  }

  public static function getClasses($leaf_match) {

    if (!isset($leaf_match)) {
      $leaf_match = ['*class' => '*', '*Class' => '*'];
    }

    AggregateFilter::$context['system.module.files'] = \Drupal::state()->get('system.module.files');

    $options = [];
    $options['onCurrent'][] = AggregateFilter::class . '::onCurrentSubsetArrayParents';
    $options['subset_array_parents'] = [
      '\*services' => $leaf_match,
      'services' => $leaf_match,
    ];;
    $aggreg = self::createInstance(static::getClassesInfo(), $options);
    $aggreg->setIteratorClass('Drupal\loopit\Iterator\AggregateFilterIterator');
    // Init context variables
    $aggreg->context += array_fill_keys(['service_id_references', 'service_class_centric', 'service_only_references'], []);
    // onLeafClasses uses $ths->context so add this option once we have the instance
    $aggreg->options['onLeaf'][] = [$aggreg, 'onLeafClasses'];

    $iter = $aggreg->getIterator();
    // TODO: To test with ::CHILD_FIRST, ::LEAVES_ONLY
    foreach (new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {}
    ksort($aggreg->context['service_id_centric']);
    ksort($aggreg->context['service_id_references']);

    $aggreg->context['service_only_references'] = [];
    foreach ($aggreg->context['service_id_references'] as $service_id => & $service_classes) {
      // Replace intsance references by service id
      if (isset($service_classes['instance_references'])) {
        foreach ($service_classes['instance_references'] as $reference_name => $reference) {
          if (isset($aggreg->context['service_class_centric'][$reference]['id'])) {
            unset($service_classes['instance_references'][$reference_name]);
            $service_classes['service_references'][$reference_name] = $aggreg->context['service_class_centric'][$reference]['id'];
          }
        }
        // Drop if have become empty
        if (!$service_classes['instance_references']) {
          unset($service_classes['instance_references']);
        }
      }

      // Get referenced by
      if (isset($service_classes['class'])) {
        $class = $service_classes['class']; //AggregateFilter::$context['service_class_centric'][$service_id]['class'];
        if (isset($aggreg->context['service_class_centric'][$class]['id'])) {
          if (isset($aggreg->context['service_class_centric'][$class]['referenced_in_services'])) {
            $service_classes['referenced_in_services'] = $aggreg->context['service_class_centric'][$class]['referenced_in_services'];
          }
        }
      }
      // It means that it is filtered, so not show here
      else {
        $aggreg->context['service_only_references'][$service_id] = $service_classes;
        unset($aggreg->context['service_id_references'][$service_id]);
      }

    }

    return $aggreg;
  }

  public static function getClassesInfo() {

    $cid = 'loopit:aggregate:service_definitions';
    $cache = \Drupal::cache()->get($cid);
    if ($cache) {
      $aggregate_classes = $cache->data;
    }
    else {
      $aggregate_services = \Drupal::getContainer();
      $aggregate_services_casted = AggregateObject::castFast($aggregate_services);
      $aggregate_services_casted['services'] = array_diff_key($aggregate_services_casted['*serviceDefinitions'], $aggregate_services_casted['*services']);

      $options = [];
      $options['depth'] = 3;
      $options['onCurrent'][] = '::onCurrentServiceUnserialize';
      $options['onCurrent'][] = AggregateFilter::class . '::onCurrentSubsetArrayParents';
      $options['subset_array_parents'] = [
        '\*services' => NULL,
        'services' => NULL,
      ];
      $aggreg = self::createInstance($aggregate_services_casted, $options);
      $aggreg->setIteratorClass('Drupal\loopit\Iterator\AggregateFilterIterator');
      // Keep the root level of the input available for all levels
      $aggreg->context['aggregate_services_casted'] = $aggregate_services_casted;
      // Init empty aggregate object for cast, needed for ::onCurrentFromArrayParents
      $aggreg->context['aggregate_object_empty'] = AggregateObject::createInstance();
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
   * Get the first level property values of services.
   *
   * @param AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public function onCurrentFromArrayParents($current, $index) {
    $aggregate = $this;

    // TODO: Move to options
    $service_errors = [];

    if ($current && is_array($current)) {
      $parents = $aggregate->getArrayParents();
      $options = $aggregate->getOptions();
      if ($parents === ['*services'] && isset($current[$options['array_parents_key']])) {
        // Get infos from "array_parents"
        $parents_to_get = $current[$options['array_parents_key']];
        $to_add = NestedArray::getValue($this->context['aggregate_services_casted'], $parents_to_get);
        foreach ($to_add as $key => $value) {
          // For array $value get only reserved keys (__CLASS__, ...)
          if (is_array($value)) {
            if (isset($value[$options['class_key']])) {
              $current[$key][$options['class_key']] = $value[$options['class_key']];
            }
            if (isset($value[$options['hash_key']])) {
              $current[$key][$options['hash_key']] = $value[$options['hash_key']];
            }
            if (isset($value[$options['array_parents_key']])) {
              $current[$key][$options['array_parents_key']] = $value[$options['array_parents_key']];
            }
          }
          else {
            $current[$key] = $value;
          }
        }
      }
      // For unserialized services cast the instance (only the 1st level)
      elseif ($parents === ['services'] && !in_array($index, $service_errors)) {
        $service = \Drupal::service($index);
        if (is_object($service)) {
          $to_add = $aggregate->context['aggregate_object_empty']->castObject($service);
          // Only class names.
          foreach ($to_add as $key => $value) {
            if (is_object($value)) {
              $current[$key] = ['__CLASS__' => get_class($value)];
            }
            // Cast to string just to have the property name
            else {
              $current[$key] = is_array($value) ? 'Array' : (string)$value;
            }
          }
        }
      }
    }

    return $current;
  }

  /**
   * Do on leaf transformation for service classes.
   *
   * @param Drupal\loopit\Aggregate\AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public function onLeafClasses($current, $index) {

    $aggregate = $this;

    $parents = $aggregate->getArrayParents();
    $parents[] = $index;

    if ($index == '__HASH__') {
      return $current;
    }
    if (in_array('__ARRAY_PARENTS__', $parents)) {
      return $current;
    }

    // The first parent is the "service" key: drop it.
    array_shift($parents);
    if (!$parents) {
      return $current;
    }

    // The next parent is the "service id".
    $service_id = array_shift($parents);
    // Concat remaining parents as a $service_property
    $service_property = array_reduce($parents, function($carry, $item) {
      return $carry . ($carry? '.' : '') . trim($item, '*');
    });

    // Some classes have leading "\"
    $current = ltrim($current, '\\');

    // Store by service id in two dimensions
    $this->context['service_id_centric'][$service_id][$service_property] = $current;

    // The service class
    if ($service_property == '__CLASS__') {

      // As "id" key for storing by class
      $this->context['service_class_centric'][$current]['id'] = $service_id;

      // As "class" key for storing by service id
      $this->context['service_id_references'][$service_id]['class'] = $current;
    }
    // Classes for other service properties.
    else {
      // Means that an object is referenced
      $ends_with = '.__CLASS__';
      $ends_with_count = strlen($ends_with);
      if(substr($service_property, -$ends_with_count) === $ends_with) {
        // It is an instance
        $in_services = 'referenced_in_services';
        $references_key = 'instance_references';
        $service_property = substr($service_property, 0, -$ends_with_count);
      }
      // Means that it is a class name, not object
      else {
        $in_services = 'classname_in_services';
        $references_key = 'classname_attributes';

      }

      // TODO: Some comments needed.
      $this->context['service_class_centric'][$current][$in_services][$service_id] = $service_property;
      $this->context['service_id_references'][$service_id][$references_key][$service_property] = $current;
    }

    return $current;
  }


}