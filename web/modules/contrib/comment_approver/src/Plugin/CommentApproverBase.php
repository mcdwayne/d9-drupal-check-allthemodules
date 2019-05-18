<?php

namespace Drupal\comment_approver\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Comment approver plugins.
 */
abstract class CommentApproverBase extends PluginBase implements CommentApproverInterface {
  /**
   * An array of field types being searched for by the plugin.
   *
   * @var array
   */
  protected $handleTypes = ['text', 'text_long'];

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration ? $this->configuration : $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Returns all the textual data present in a comment.
   *
   * @param $comment
   *   Entity object of type comment.
   *
   * @return array
   *   Returns array of textual data keyed by field name.
   */
  public function getTextData($comment) {
    $handleTypes = $this->handleTypes;
    $handleFields = ['subject' => $comment->getSubject()];
    $fields = $comment->getFieldDefinitions();
    foreach ($fields as $fieldname => $field) {
      if (in_array($field->getType(), $handleTypes)) {
        $handleFields[$fieldname] = $comment->get($fieldname)->value;
      }
    }
    return $handleFields;
  }

}
