<?php

namespace Drupal\rules_scheduler\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\rules_scheduler\Entity\Task;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a Task object.
 */
class TaskConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return Task::load($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'task');
  }

}
