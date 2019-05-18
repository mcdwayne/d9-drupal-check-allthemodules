<?php

/**
 * @file
 * Contains \Drupal\content_callback\Plugin\ContentCallback\PluginBase.php.
 */

namespace Drupal\content_callback\Plugin\ContentCallback;

use Drupal\content_callback\Plugin\ContentCallbackInterface;
use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for all the content callback plugins.
 */
abstract class PluginBase extends ComponentPluginBase implements ContentCallbackInterface {

  /**
   * Entity calling the content callback
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Options for this callback
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function render(array $options = array(), EntityInterface $entity = NULL) {
    $this->entity = $entity;
    $this->options = $options;

    $account = \Drupal::currentUser();
    if ($this->access($account)->isAllowed()) {
      return $this->build();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function optionsForm(array &$form, array $saved_options) {
    // Add nothing by default
  }

  /**
   * Checks if the plugin has this option.
   *
   * @param string $option
   *   The option that needs to be checked.
   *
   * @return bool
   *   TRUE if the plugin has this option set, FALSE otherwise.
   */
  protected function hasOption($option) {
    $definition = $this->getPluginDefinition();

    return $definition['has_options'] && isset($this->options[$option]);
  }

  /**
   * Gets the option.
   *
   * @param string $option
   *   The option that will be fetched.
   *
   * @return mixed
   *   The value of the option.
   */
  protected function getOption($option) {
    return $this->options[$option];
  }


}
