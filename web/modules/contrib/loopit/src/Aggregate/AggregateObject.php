<?php

namespace Drupal\loopit\Aggregate;



/**
 * Cast of traversed objects into array.
 *
 */
class AggregateObject extends AggregateArray {

  /**
   * Just for adding same AggregateObject related options
   */
  public function __construct($input = [], $options = [], $parent = NULL) {

    $options = [
      'private_prefix' => '-',
      'protected_prefix' => '*',
      'public_prefix' => '+',
    ] + $options;

    $this->options['keys'] = [$this->options['class_key'], $this->options['hash_key'], $this->options['array_parents_key']];

    parent::__construct($input, $options, $parent);
  }

  public function transform($aggregate) {
    $aggregate = parent::transform($aggregate);
    // The first time met object has 'class_key' but not 'array_parents_key'
    if (isset($aggregate[$this->options['class_key']]) && !isset($aggregate[$this->options['array_parents_key']])) {
      // Get the object from $this->context['objects']
      $obj = $this->context['objects'][$aggregate[$this->options['class_key']]][$aggregate[$this->options['hash_key']]]['obj'];
      $aggregate += $this->castObject($obj);
    }
    return $aggregate;
  }

  /**
   * The "cast into array" method.
   */
  public function castObject($obj) {
    $aggregate = (array) $obj;

    $class = $class = \get_class($obj);
    $parents_and_self = [$class];
    $parent = $obj;
    while ($parent = get_parent_class($parent)) {
      $parents_and_self[] = $parent;
    }

    // Change properties keys to have private, protected and public
    // prefixes.
    $keys = array_keys($aggregate);
    foreach ($keys as $i => $key) {
      // Put "*" at the beginning if present.
      if (($strpos = strpos($key, '*')) !== FALSE) {
        // TODO: #major: can drop trim on $ref = trim(substr($ref, 1));
        $keys[$i] = $this->options['protected_prefix'] . trim(substr($key, $strpos+1));
        continue;
      }
      // Track private properties from parents and self
      $privates = [];
      foreach ($parents_and_self as $parent) {
        if (($strpos = strpos($key, $parent)) !== FALSE) {
          $keys[$i] = $this->options['private_prefix'] . trim(substr($key, $strpos + strlen($parent)));
          $privates[] = $keys[$i];
        }
      }
      // Remaining properties are public
      if (!in_array($keys[$i], $privates) && !in_array($keys[$i], $this->options['keys'])) {
        $keys[$i] = $this->options['public_prefix'] . $keys[$i];
      }
    }
    $aggregate = array_combine($keys, $aggregate);

    return $aggregate;
  }

  public static function castFast($variable) {
    $aggreg = self::createInstance($variable);
    $output = $aggreg->traverseFast();
    return $output;
  }
}