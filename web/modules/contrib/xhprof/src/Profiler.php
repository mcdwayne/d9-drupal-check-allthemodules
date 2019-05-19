<?php

namespace Drupal\xhprof;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\xhprof\Extension\TidewaysExtension;
use Drupal\xhprof\Extension\TidewaysXHProfExtension;
use Drupal\xhprof\Extension\UprofilerExtension;
use Drupal\xhprof\Extension\XHProfExtension;
use Drupal\xhprof\XHProfLib\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class Profiler implements ProfilerInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\xhprof\XHProfLib\Storage\StorageInterface
   */
  private $storage;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
   */
  private $requestMatcher;

  /**
   * @var string
   */
  private $runId;

  /**
   * @var bool
   */
  private $enabled = FALSE;

  /**
   * @var \Drupal\xhprof\Extension\ExtensionInterface
   */
  private $activeExtension;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\xhprof\XHProfLib\Storage\StorageInterface $storage
   * @param \Symfony\Component\HttpFoundation\RequestMatcherInterface $requestMatcher
   */
  public function __construct(ConfigFactoryInterface $configFactory, StorageInterface $storage, RequestMatcherInterface $requestMatcher) {
    $this->configFactory = $configFactory;
    $this->storage = $storage;
    $this->requestMatcher = $requestMatcher;

    $extension = $this->configFactory->get('xhprof.config')->get('extension');
    if ($extension == 'xhprof') {
      $this->activeExtension = new XHProfExtension();
    }
    elseif ($extension == 'uprofiler') {
      $this->activeExtension = new UprofilerExtension();
    }
    elseif ($extension == 'tideways') {
      $this->activeExtension = new TidewaysExtension();
    }
    elseif ($extension == 'tideways_xhprof') {
      $this->activeExtension = new TidewaysXHProfExtension();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $flags = $this->configFactory->get('xhprof.config')->get('flags');
    $excludeIndirectFunctions = $this->configFactory->get('xhprof.config')
      ->get('exclude_indirect_functions');

    $modifier = 0;
    $extensionOptions = $this->activeExtension->getOptions();
    foreach ($flags as $key => $value) {
      if ($value !== '0') {
        $extensionFlag = $extensionOptions[$key];
        $modifier += @constant($extensionFlag);
      }
    }

    $options = array();
    if ($excludeIndirectFunctions) {
      $options = [
        'ignored_functions' => [
          'call_user_func',
          'call_user_func_array',
        ],
      ];
    }

    $this->activeExtension->enable($modifier, $options);

    $this->enabled = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function shutdown($runId) {
    $xhprof_data = $this->activeExtension->disable();
    $this->enabled = FALSE;

    return $this->storage->saveRun($xhprof_data, $this->getNamespace(), $runId);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function canEnable(Request $request) {
    $config = $this->configFactory->get('xhprof.config');

    if ($this->isLoaded() && $config->get('enabled') && $this->requestMatcher->matches($request)) {
      $interval = $config->get('interval');

      if ($interval && mt_rand(1, $interval) % $interval != 0) {
        return FALSE;
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isLoaded() {
    return count($this->getExtensions()) >= 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions() {
    $extensions = array();

    if (XHProfExtension::isLoaded()) {
      $extensions['xhprof'] = 'XHProf';
    }

    if (UprofilerExtension::isLoaded()) {
      $extensions['uprofiler'] = 'UProfiler';
    }

    if (TidewaysExtension::isLoaded()) {
      $extensions['tideways'] = 'Tideways';
    }

    if (TidewaysXHProfExtension::isLoaded()) {
      $extensions['tideways_xhprof'] = 'Tideways xhprof';
    }

    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function link($run_id) {
    $link = Link::createFromRoute(t('XHProf output'), 'xhprof.run', ['run' => $run_id], ['absolute' => TRUE]);

    return $link->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    return $this->storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getRunId() {
    return $this->runId;
  }

  /**
   * {@inheritdoc}
   */
  public function createRunId() {
    if (!$this->runId) {
      $this->runId = uniqid();
    }

    return $this->runId;
  }

  /**
   * {@inheritdoc}
   */
  public function getRun($run_id) {
    return $this->getStorage()->getRun($run_id, $this->getNamespace());
  }

  /**
   * Returns the namespace for this site.
   *
   * Currently is set to the site name value.
   *
   * @return string
   *   The string generated from site name.
   */
  private function getNamespace() {
    $result = $this->configFactory->get('system.site')->get('name');
    return str_replace(['.', '/', '\\'], '-', $result);
  }

}
