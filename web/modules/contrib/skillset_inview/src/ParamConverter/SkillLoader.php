<?php

namespace Drupal\skillset_inview\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Class SkillLoader.
 *
 * @package Drupal\skillset_inview\ParamConverter
 */
class SkillLoader implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($id, $definition, $name, array $defaults) {
    $item = db_query("SELECT id, weight, name, percent FROM {skillset_inview} WHERE id = :id", array(':id' => $id))->fetchObject();
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'skillset_inview');
  }

}
