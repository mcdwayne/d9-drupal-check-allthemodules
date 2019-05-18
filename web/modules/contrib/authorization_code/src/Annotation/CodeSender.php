<?php

namespace Drupal\authorization_code\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Plugin Namespace: Plugin\CodeSender.
 *
 * For a working example, see
 * \Drupal\authorization_code\Plugin\CodeSender\DrupalMail.
 *
 * @see \Drupal\authorization_code\CodeSenderInterface
 * @see plugin_api
 *
 * @Annotation
 */
class CodeSender extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin title.
   *
   * @var string
   */
  public $title;

}
