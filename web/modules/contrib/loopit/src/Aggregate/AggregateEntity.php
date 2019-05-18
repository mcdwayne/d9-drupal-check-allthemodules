<?php

namespace Drupal\loopit\Aggregate;


/**
 * @todo comments
 */
class AggregateEntity extends AggregateArray {

  /**
   * @todo replace $subset_array_parents input with $pattern (like in AggregatePlugin)
   *
   * @param array $subset_array_parents
   * @return \Drupal\loopit\Aggregate\AggregateArray
   */
  public static function getClasses($subset_array_parents = NULL) {

    if (!isset($subset_array_parents)) {
      $subset_array_parents = [
        '*/\*handlers' => NULL,
        '*/\*class' => NULL,
        '*/\*provider' => NULL,
      ];
    }

    // Cast the input
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $types = $entity_type_repository->getEntityTypeLabels();
    foreach ($types as $type => $label) {
      $types[$type] = \Drupal::entityTypeManager()->getDefinition($type);
    }
    $types_casted = AggregateObject::castFast($types);


    // Some init values
    AggregateFilter::$context['system.module.files'] = \Drupal::state()->get('system.module.files');
    $options = [];
    // Callback options
    $options['onCurrent'][] = AggregateFilter::class . '::onCurrentSubsetArrayParents';
    // Filtering options
    $options['subset_array_parents'] = $subset_array_parents;

    $aggreg = self::createInstance($types_casted, $options);
    // Init context variables
    $aggreg->context += array_fill_keys(['entity_id_centric', 'entity_class_centric', 'entity_handler_centric'], []);
    // onLeafClasses uses $ths->context so add this option once we have the instance
    $aggreg->options['onLeaf'][] = [$aggreg, 'onLeafClasses'];

    $iter = $aggreg->getIterator();
    foreach (new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {}

    ksort($aggreg->context['entity_id_centric']);
    // Useful for shared handler class accros multiple entities. Ex.: the generic 'handlers.access' 'Drupal\Core\Entity\EntityAccessControlHandler'
    foreach ($aggreg->context['entity_handler_centric'] as $handler_id => & $handlers) {
      uasort($handlers, function($a, $b) {
        return count($b) - count($a);
      });
    }

    return $aggreg;
  }

  /**
   * Do on leaf transformation for entity classes.
   *
   * @param Drupal\loopit\Aggregate\AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public function onLeafClasses($current, $index) {

    $aggregate = $this;

    // Nothong to do for __HASH__ key
    if ($index == '__HASH__') {
      return $current;
    }

    $parents = $aggregate->getArrayParents();
    $parents[] = $index == '__CLASS__' ? 'group_class' : $index;

    // The first parent is the entity id.
    $entity_id = array_shift($parents);

    // Concat remaining parents as a $handlers_id
    $handlers_id = array_reduce($parents, function($carry, $item) {
      return $carry . ($carry? '.' : '') . trim($item, '*');
    });

    $current = ltrim($current, '\\');

    // Store by handler and class except for provider key
    if ($handlers_id != 'provider') {

      $this->context['entity_handler_centric'][$handlers_id][$current][] = $entity_id;
      $this->context['entity_class_centric'][$current][$handlers_id][] = $entity_id;
    }

    // Store by entity id in two dimensions
    $this->context['entity_id_centric'][$entity_id][$handlers_id] = $current;

    return $current;
  }
}