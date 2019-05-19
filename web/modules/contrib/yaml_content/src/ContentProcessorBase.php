<?php

namespace Drupal\yaml_content;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * A base implementation of a ContentProcessor plugin.
 *
 * Custom ContentProcessor plugins should extend this class.
 *
 * @see \Drupal\yaml_content\ContentProcessorInterface
 */
abstract class ContentProcessorBase extends ContextAwarePluginBase implements ContentProcessorInterface {

}
