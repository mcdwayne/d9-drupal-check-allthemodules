<?php

namespace Drupal\hooks;

use Doctrine\Common\Inflector\Inflector;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandler as BaseModuleHandler;
use Drupal\hooks\Event\HookEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModuleHandler extends BaseModuleHandler {

  /**
   * @var \Drupal\hooks\HookInterface[]
   */
  protected $hookObjects = [];

  /**
   * An array of event names.
   *
   * @var array
   */
  protected $hookEvents = [];

  /**
   * Keep track of whether we need to write to the cache.
   *
   * @var bool
   */
  protected $hookCacheNeedsWriting = FALSE;

  /**
   * @var null|array
   *   The hooks cache that tracks functions to class implementations.
   */
  protected $hookCache = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($root, array $module_list = array(), CacheBackendInterface $cache_backend, EventDispatcherInterface $dispatcher) {
    parent::__construct($root, $module_list, $cache_backend);
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    parent::alter($type, $data, $context1, $context2);

    $this->explicitHooks($type, $data, $context1, $context2);
//    $this->implicitHooks($type, $data, $context1, $context2);
  }

  /**
   * This approach uses the event system which means you have to explicitly
   * register hook implementations.
   */
  protected function explicitHooks($type, &$data, &$context1 = NULL, &$context2 = NULL) {

    if (is_array($type)) {
      $cid = implode(',', $type);
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array
      // with one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }
    else {
      $cid = $type;
    }

    if (!isset($this->hookEvents[$cid])) {
      $this->hookEvents[$cid][] = $type;

      if (isset($extra_types)) {
        foreach ($extra_types as $extra_type) {
          $this->hookEvents[$cid][] = $extra_type;
        }
      }
    }

    // @TODO, should the event object be shared between the hooks when we have
    // $extra_types?
    $event = new HookEvent($type, $data, $context1, $context2);
    foreach ($this->hookEvents[$cid] as $hook_name) {
      $this->dispatcher->dispatch('hooks.' . $hook_name, $event);
      // Set the data back from the listeners.
      $data = $event->getData();
      $context1 = $event->getContext1();
      $context2 = $event->getContext2();
    }
  }

  /**
   * This approach relies on a magic hook class.
   */
  protected function implicitHooks($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    if (is_array($type)) {
      $cid = implode(',', $type);
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array
      // with one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }
    else {
      $cid = $type;
    }

    if (!isset($this->hookObjects[$cid])) {
      $this->hookObjects[$cid] = $this->getHookObjects($type);

      if (isset($extra_types)) {
        foreach ($extra_types as $extra_type) {
          $this->hookObjects[$cid] = array_merge($this->hookObjects[$cid], $this->getHookObjects($extra_type));
        }
      }
    }

    foreach ($this->hookObjects[$cid] as $hook_object) {
      $hook_object->alter($data, $context1, $context2);
    }
  }

  /**
   * Get an a array of the corresponding hook objects for this hook.
   *
   * @param $hook_name
   *   The name of the hook that is being fired.
   *
   * @return \Drupal\hooks\HookInterface[]
   *   An array of instantiated hook objects.
   */
  protected function getHookObjects($hook_name) {
    // If we've not loaded the hookCache yet, load it now.
    if (!isset($this->hookCache)) {
      $this->hookCache = [];
      if ($cache = $this->cacheBackend->get('hooks')) {
        $this->hookCache = $cache->data;
      }
    }

    // The hook doesn't currently exist in the hook cache, gather up the
    // implementations.
    if (!isset($this->hookCache[$hook_name])) {
      $this->hookCache[$hook_name] = [];
      $class_name = Inflector::classify($hook_name);
      foreach ($this->getModuleList() as $module_name => $info) {
        $class = 'Drupal\\' . $module_name . '\\Hooks\\' . $class_name;

        if (class_exists($class)) {
          $this->hookCache[$hook_name][] = $class;
          $this->hookCacheNeedsWriting = TRUE;
        }
      }
    }

    // @TODO, should we cache the hook objects per request or recreate them
    // each time?
    // Turn the cached classes into objects.
    $hook_objects = [];
    foreach ($this->hookCache[$hook_name] as $class) {
      // The class exists so we create it either using create() for DI or
      // by manually instantiating the object.
      if (in_array('Drupal\Core\DependencyInjection\ContainerInjectionInterface', class_implements($class))) {
        $hook_objects[] = $class::create(\Drupal::getContainer());
      }
      else {
        $hook_objects[] = new $class();
      }
    }
    return $hook_objects;
  }

  /**
   * {@inheritdoc}
   */
  public function writeCache() {
    parent::writeCache();
    if ($this->hookCacheNeedsWriting) {
      $this->cacheBackend->set('hooks', $this->hookCache);
      $this->hookCacheNeedsWriting = FALSE;
    }
  }

}
