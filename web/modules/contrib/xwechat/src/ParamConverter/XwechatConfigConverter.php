<?php

/**
 * @file
 * Contains \Drupal\xwechat\ParamConverter\XwechatConfigConverter.
 */

namespace Drupal\xwechat\ParamConverter;

use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for a node entity in preview.
 */
class XwechatConfigConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return db_select('xwechat_config', 'c')
            ->fields('c')
            ->condition('wid', $value)
            ->execute()
            ->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'xwechat_config') {
      return TRUE;
    }
    return FALSE;
  }

}
