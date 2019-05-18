<?php
namespace Drupal\loopit\Aggregate;

use Drupal\loopit\Iterator\AggregateIteratorInterface;
use Drupal\loopit\Iterator\AggregateFilterIterator;

/**
 * Nested array as a transform of the recursively traversed / filtered elements.
 *
 * Traversable by the recursive Agregate Iterator. The nested output is in
 * $cacheNested, use Agregate Filter Iterator for filtering.
 * Callbacks that are triggered when traversing:
 * - ::onCurrent()
 *   Triggered from the Agregate in the first time call of offsetGet(),
 *   from the current() iterator method.
 * - ::preDown()
 *   Triggered from the getChildren() iterator method, before the
 *   instanciation of the children Aggregate.
 * - ::onDown()
 *   Triggered from the children Agregate constructor, instanciated from the
 *   getChildren() iterator method
 * - ::preUp()
 *   Triggered from the valid() iterator method when there is no valid entry
 *   and when having a parent Aggrgate
 *   ::onLeaf()
 *   Triggered from the hasChildren() iterator method when there are no
 *   children
 *
 * When traversing objects, they are replaced with array of their class name and
 * hash. Before replacement they are stored in $this->context['objects'] keyed by
 * {class name, hash}.
 *
 * @todo Rename to AggregateBase ?
 */
class AggregateArray implements AggregateInterface {

  /**
   * Options to define / change the aggregate behavior.
   *
   * @var array
   */
  protected $options = [
    'class_key' => '__CLASS__',
    'hash_key' => '__HASH__',
    'array_parents_key' => '__ARRAY_PARENTS__',
    // Depth to limit the recursion. -1 for unlimited depth
    'depth' => -1,
    // The default iterator class
    'iterator_class' => AggregateFilterIterator::class,
  ];

  /**
   * "all levels" traversed information.
   *
   * @var array
   */
  protected $context = [
    // The traversed path from the root aggregate.
    'array_parents' => [],
    // The traversed depth from the root aggregate.
    'depth' => 0,
    // Flat objects references store to avoid infinite loop.
    'objects' => [],
  ];

  /**
   * Nested cache array used for ouput of the traversed elements.
   *
   * The traversed nested output can be seen as a transformation of the root
   * level aggregate.
   * TODO: Rename to $traversedNested or $traversedCache ?
   */
  protected $cacheNested = NULL;

  /**
   * "by level" traversed information
   *
   * @var array
   */
  protected $cache = [];

  /**
   * The parent level aggregate.
   *
   * @var \Drupal\loopit\Aggregate\AggregateArray
   */
  protected $parent;

  /**
   * The input data.
   *
   * @var mixed
   */
  protected $input;

  /**
   * @param array $input
   * @param array $options
   * @param \Drupal\loopit\Aggregate\AggregateArray $parent
   */
  public function __construct($input = [], $options = [], $parent = NULL) {
    $this->parent = $parent;
    // Validate the iterator_class. Use the default if not valid
    if (isset($options['iterator_class'])) {
      $iterator_class = $options['iterator_class'];
      unset($options['iterator_class']);
      $this->setIteratorClass($iterator_class);
    }
    $this->options = $options + $this->options;

    // Notify on down, Also on starting level creation
    $input = $this->onDown($input);

    $this->input = $input;
  }

  /**
   * Create Aggregate instance, optionally using aggregate_class from $options.
   *
   * @param array $input
   * @param array $options
   * @param \Drupal\loopit\Aggregate\AggregateArray $parent
   * @return \Drupal\loopit\Aggregate\AggregateArray
   */
  public static function createInstance($input = [], $options = [], $parent = NULL) {
    $aggregate = NULL;
    if (!empty($options['aggregate_class']) && in_array(AggregateInterface::class, class_implements($options['aggregate_class']))) {
      $aggregate = $options['aggregate_class'];
    }
    if (isset($aggregate)) {
      $aggreg = new $aggregate($input, $options, $parent);
    }
    else {
      $aggreg = new static ($input, $options, $parent);
    }

    return $aggreg;
  }

  /**
   * For ArrayObject similarity
   */
  public function getIteratorClass() {
    return $this->options['iterator_class'];
  }

  /**
   * Called from the constructor for iterator_class init.
   *
   * For ArrayObject similarity.
   */
  public function setIteratorClass($iterator_class) {
    // subtype/interface of Drupal\loopit\Iterator\AggregateIterator;
    if (in_array(AggregateIteratorInterface::class, class_implements($iterator_class))) {
      $this->options['iterator_class'] = $iterator_class;
    }
  }

  public function getIterator() {
    $iterator = $this->options['iterator_class'];
    return new $iterator($this);
  }

  /**
   * {@inheritDoc}
   *
   * @see ArrayAccess::offsetExists()
   */
  public function offsetExists($index) {
    return array_key_exists($index, $this->input);
  }

  /**
   * {@inheritDoc}
   *
   * @see ArrayAccess::offsetGet()
   */
  public function offsetGet($index) {
    // Use cache so that onCurrent() callback is called only once.
    if (array_key_exists($index, $this->cache)) {
      return $this->cache[$index];
    }

    $current = $this->input[$index];
    $depth_limit = $this->options['depth'] >= 0 && count($this->context['array_parents']) >= $this->options['depth'];

    // Limit before onCurrent processing
    if ($depth_limit) {
      // TODO: if object cannot be converted to string
      $current = @(string)$current;
    }

    // Here is $current processing
    $current = $this->onCurrent($current, $index);

    // Limit also after onCurrent processing, because potential new children
    // from onCurrent() callback.
    if ($depth_limit) {
      // TODO: if object cannot be converted to string
      $current = @(string)$current;
    }

    $this->cache[$index] = $current;
    return $current;
  }

  /**
   * Read only array acces.
   *
   * Use onCurrent() callbacks to change the current value in the traversed
   * nested output. For Aggregate transformations use the transform() method
   * called in self::onDown() event handler (into the next level as a new
   * aggregate instance).
   *
   * @see ArrayAccess::offsetSet()
   */
  public function offsetSet($offset, $value) {  }

  /**
   * Read only array acces.
   *
   * Use onCurrent() callbacks with AggregateFilterIterator. The element will
   * be filtered out in the traversed nested output for empty callback return.
   *
   * @see ArrayAccess::offsetUnset()
   */
  public function offsetUnset($offset) {  }

  /**
   * Getter for the $options attribute.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Getter for the $context attribute.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Getter for the array_parents key in the $context attribute.
   */
  public function getArrayParents() {
    return $this->context['array_parents'];
  }

  /**
   * Getter for the depth key in the $context attribute.
   */
  public function getDepth() {
    return $this->context['depth'];
  }

  /**
   * Getter for the $cacheNested attribute.
   */
  public function getCacheNested() {
    return $this->cacheNested;
  }

  /**
   * Getter for the $cache attribute.
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * Getter for the $parent attribute.
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Getter for the $input attribute.
   */
  public function getInput() {
    return $this->input;
  }

  /**
   * Callback for the current element.
   */
  public function onCurrent($current, $index) {

    return $this->callbackProcessValue(__FUNCTION__, $current, $index);
  }

  /**
   * Notify event handlers by using simple callback.
   *
   * For performance do not use events dispatching, just check if the event
   * handler is callable. For static callbacks pass $this as the first parameter
   *
   * @param string $function
   * @param mixed $current
   * @param string $index
   */
  public function callbackProcessValue($function, $current, $index) {

    // There are callback to be called
    if (isset($this->options[$function])) {
      foreach ($this->options[$function] as $key => $callback) {
        if (is_string($callback) && strpos($callback, '::') === 0) {
          $callback = static::class . $callback;
        }
        if (is_callable($callback)) {
          $args = [$current, $index];
          if (is_string($callback)) {
            array_unshift($args, $this);
          }
          $current = call_user_func_array($callback, $args);
        }
      }
    }
    return $current;
  }

  /**
   * Callback before to go in the children level.
   *
   * Add the current key as entry in "array parents" option
   *
   * @param string $key
   */
  public function preDown($key) {
    $this->context['array_parents'][] = $key;

    // One level deeper
    $this->context['depth']++;
  }

  /**
   * Callback when already in children level.
   *
   * The child aggregate is responsible to define how it is traversable (how it
   * exposes his elements) via the transform method.
   *
   * @param mixed $aggregate The traversable input. Can be an array or object.
   * @return mixed
   *   The same or altered input
   */
  public function onDown($aggregate) {

    // This context is by reference of the parent one. Context is share between
    // all $aggregate levels
    if (isset($this->parent)) {
      $this->context = &$this->parent->context;
    }

    $aggregate = $this->transform($aggregate);

    return $aggregate;
  }

  /**
   * Traversal transform / stop method.
   *
   * This return value will be used as input for the aggregate.
   * Empty or scalar return value can be used for "stop recursion" condition.
   *
   * Store traversed objects in $this->context['objects'] keyed by
   * {class name, hash}. When traversed twice or more then add the path
   * (array parents) when have been met for the first time.
   *
   * @param mixed $obj
   *   @return The class and hash if object, as is else.
   */
  public function transform($obj) {

    if (\is_object($obj) || $obj instanceof \__PHP_Incomplete_Class) {

      $class = \get_class($obj);
      $h = \spl_object_hash($obj);

      $aggregate = [
        $this->options['class_key'] => $class,
        $this->options['hash_key'] => $h,
      ];

      // First time met object
      if (empty($this->context['objects'][$class][$h]['obj'])) {

        $this->context['objects'][$class][$h] = [
          'obj' => $obj, //$reflexion;
          'array_parents' => $this->context['array_parents'],
          'count' => 1,
        ];
      }
      // Already traversed so add the path (array parents) when have been met
      // for the first time
      else {
        $aggregate[
          $this->options['array_parents_key']
        ]  = $this->context['objects'][$class][$h]['array_parents']
        ;
        $this->context['objects'][$class][$h]['count']++;
      }
    }
    else {
      $aggregate = $obj;
    }

    return $aggregate;
  }

  /**
   * Callback before to go back to the parent level.
   *
   * Save this level cacheNested to the parent one at the  $parent_key index.
   */
  public function preUp() {

    $parent_key = array_pop($this->context['array_parents']);
    // Do not create entry if there is no traversed element from $this->cacheNested
    if (isset($this->cacheNested)) {
      $this->parent->cacheNested[$parent_key] = $this->cacheNested;
    }

    $this->context['depth']--;

    return $parent_key;
  }

  /**
   * Callback when the current is a leaf.
   *
   * Store in nested cache the traversed elements.
   *
   * @param string $key The index of the current traversed element
   */
  public function onLeaf($current, $index) {

    $current = $this->callbackProcessValue(__FUNCTION__, $current, $index);

    $this->cacheNested[$index] = $current;

    return $current;
  }

  /**
   * Traverse without iterators and without any filter or value callbacks.
   *
   * Same functionnality than using iterator but simplified and faster
   * Only level changing related callbacks for array parents, depth and recursion marker
   *
   * @see http://php.net/manual/en/class.recursiveiteratoriterator.php#112713
   * @param mixed $aggregate
   * @return mixed The traversed elements in nested output
   */
  public function traverseFast($aggregate = NULL) {

    if (!isset($aggregate)) {
      $aggregate = $this->input;
    }

    $aggregate = $this->onDown($aggregate);

    // TODO: use $this->cacheNested instead of $output ?
    $output = [];
    // TODO: To benchmark performance using offsetGet() (which includes onCurrent() callbacks).
    // TODO: If will not use offsetGet() then get working the depth option
    $current = reset($aggregate);
    $key = key($aggregate);

    // Equivalent to: while ($iterator->valid())
    while (isset($key)) {

        // Equivalent to: if ($iterator->hasChildren())
        if (\is_array($current) || \is_object($current) || $current instanceof \__PHP_Incomplete_Class) {

          $this->preDown($key);

          // Equivalent to: $iterator->getChildren();
          $output[$key] = self::traverseFast($current);
        }
        else {
            // For getting leaf values
            // TODO: To benchmark using onleaf() callbacks
            $output[$key] = $current;
        }

        // Equivalent to: $iterator->next();
        $current = next($aggregate);
        $key = key($aggregate);
    }
    $this->preUp();

    return $output;
  }

}