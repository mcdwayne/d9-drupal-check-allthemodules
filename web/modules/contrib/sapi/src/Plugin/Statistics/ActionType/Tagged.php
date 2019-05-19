<?php

namespace Drupal\sapi\Plugin\Statistics\ActionType;

use Drupal\sapi\ActionTypeBase;
use Drupal\sapi\ActionTypeInterface;

/**
 * @ActionType (
 *   id = "tagged",
 *   label = "Tagged action item"
 * )
 *
 * A taggeable action type, where a handler can compare tags to determine if
 * it should process.  This action type holds no data other than the tag.
 *
 * To use this, pass in a tag when using the manager to create an instance,
 * and then in your handler code, confirm that the plugin has the required
 * tag.
 *
 */
class Tagged extends ActionTypeBase implements ActionTypeInterface {

  /**
   * Store the action tag
   *
   * @protected string[] $tags
   */
  protected $tags;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($configuration['tags'])) {
      $this->tags = $configuration['tags'];
    }
    else if (isset($configuration['tag'])) {
      $this->tags = [ $configuration['tag'] ];
    }
    else {
      $this->tags = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function describe() {
    return '['.__class__.'] I am tagged with: '. implode(',',$this->tags);
  }

  /**
   * Return a boolean if the passed tag has been assigned to this plugin
   *
   * @param string $tag
   *   A string tag to try to match to the plugin tags
   * @return boolean
   *   true if the tag parameter was found in the tag array
   */
  function hasTag($tag) {
    return in_array($tag, $this->tags);
  }

}