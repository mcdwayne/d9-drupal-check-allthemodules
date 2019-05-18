<?php

namespace Drupal\contentserialize;

/**
 * Helper functions for dealing with generators and traversables.
 *
 * If autoloading wasn't an issue they wouldn't be in a class.
 */
class Traversables {

  /**
   * Split the source into chunks.
   *
   * Chunks the source into arrays with size elements. The last chunk may
   * contain less than size elements.
   *
   * @param mixed $source
   *   The source generator, traversable or array.
   * @param int $size
   *   The size of each chunk.
   * @param bool $preserve_keys
   *   When set to TRUE keys will be preserved. Default is FALSE which will
   *   reindex the chunk numerically
   *
   * @return \Generator
   */
  public static function chunk($source, $size, $preserve_keys = FALSE) {
    $batch = [];
    $i = 0;
    // Use foreach to be compatible with arrays, generators and traversables.
    foreach ($source as $key => $value) {
      $batch[$preserve_keys ? $key : $i] = $value;
      $i++;
      if ($i == $size) {
        $i = 0;
        yield $batch;
        $batch = [];
      }
    }
    // Unless the number of elements is a multiple of the batch size there will
    // be leftovers.
    if ($batch) {
      yield $batch;
    }
  }

  /**
   * Ensures the output never returns the same key twice.
   *
   * It keeps a cache of seen keys so memory usage increases with unique key
   * count.
   *
   * @param mixed $source
   *   The source generator, traversable or array.
   *
   * @return \Generator
   */
  public static function uniqueByKey($source) {
    $ids = [];
    foreach ($source as $key => $value) {
      if (empty($ids[$key])) {
        yield $key => $value;
        $ids[$key] = TRUE;
      }
    }
  }

  /**
   * Return a limited number of elements from a source.
   *
   * @param mixed $source
   *   A generator/traversable.
   * @param int $count
   *   The maximum number of elements to yield.
   * @return \Generator
   *   Up to $count elements from $source.
   */
  public static function truncate($source, $count) {
    $yielded = 0;
    foreach ($source as $key => $value) {
      if ($yielded == $count) {
        return;
      }
      yield $key => $value;
      $yielded++;
    }
  }

  /**
   * Filters elements of a generator/traversable using a callback function.
   *
   * Iterates over each value in $input passing them to the callback function.
   * If the callback function returns true, the value is yielded.
   *
   * @param $input
   *   The generator/traversable to iterate over.
   * @param callable|null $callback
   *   (optional) The callback function to use; if no callback is supplied, all
   *   elements equal (==) to FALSE will be removed.
   * @param int $flag
   *   (optional) Flag determining what arguments are sent to callback:
   *   - ARRAY_FILTER_USE_KEY: pass key as the only argument to callback instead
   *     of the value.
   *   - ARRAY_FILTER_USE_BOTH: pass both value and key as arguments to callback
   *     instead of the value.
   *   It defaults to passing just the value to the callback.
   *
   * @return \Generator
   *   Elements of $input that haven't been filtered out.
   *
   * @throws \InvalidArgumentException
   *   If an invalid flag is passed.
   */
  public static function filter($input, callable $callback = NULL, $flag = 0) {
    foreach ($input as $key => $value) {
      if ($callback) {
        switch ($flag) {
          case 0:
            $result = $callback($value);
            break;
          case ARRAY_FILTER_USE_BOTH:
            $result = $callback($value, $key);
            break;
          case ARRAY_FILTER_USE_KEY:
            $result = $callback($key);
            break;
          default:
            throw new \InvalidArgumentException("Invalid flag passed: $flag");
        }
      }
      else {
        $result = $value;
      }
      if ($result) {
        yield $key => $value;
      }
    }
  }

  /**
   * Merge multiple generators/traversables into a single source.
   *
   * It doesn't compare keys, it just appends the sources together.
   *
   * @param  ...$sources
   *   Generators/traversables to merge together.
   *
   * @return \Generator
   */
  public static function merge(...$sources) {
    foreach ($sources as $source) {
      // @todo: PHP 7.x: Use yield from.
      foreach ($source as $key => $value) {
        yield $key => $value;
      }
    }
  }

}
