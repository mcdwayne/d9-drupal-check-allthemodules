<?php

namespace Drupal\xhprof\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\xhprof\ProfilerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class RunConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\xhprof\ProfilerInterface
   */
  private $profiler;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @param \Drupal\xhprof\ProfilerInterface $profiler
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ProfilerInterface $profiler, ConfigFactoryInterface $config_factory) {
    $this->profiler = $profiler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      return $this->profiler->getRun($value);
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] === 'xhprof:run_id') {
      return TRUE;
    }
    return FALSE;
  }
}
