<?php

namespace Drupal\active_cache\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Cache\Cache;

/**
 * Defines a Active cache item annotation object.
 *
 * @see \Drupal\active_cache\Plugin\ActiveCacheManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActiveCache extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The cache bin for this active cache plugin.
   *
   * @var string
   */
  public $cache_bin;

  /**
   * @var string[]
   */
  public $cache_tags;

  /**
   * @var string[]
   */
  public $cache_contexts;

  /**
   * After this many seconds pass the cache will be invalidated.
   *
   * @var int
   */
  public $max_age;

  /**
   * The cache id that will be used to save the cache data.
   * Leave null to use the default cache id.
   *
   * @var null|string
   */
  public $cache_id;

  /**
   * {@inheritdoc}
   */
  public function __construct($values) {
    $values += [
      'cache_bin' => 'default',
      'cache_contexts' => [],
      'cache_tags' => [],
      'max_age' => Cache::PERMANENT
    ];

    if (isset($values['id'])) {
      $values += [
        'cache_id' => implode(':', ['active_cache', $values['id']])
      ];
    }

    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($values[\'cache_tags\'])', 'cache_tags must be a valid array of strings');
    assert('is_string($values[\'cache_bin\'])', 'cache_bin must be a string.');
    assert('is_string($values[\'id\'])', 'id must be a string.');
    assert('is_int($values[\'max_age\'])', 'max_age must be an integer.');
    assert('is_string($values[\'cache_id\'])', 'cache_id must be a null or string.');
    parent::__construct($values);
  }
}
