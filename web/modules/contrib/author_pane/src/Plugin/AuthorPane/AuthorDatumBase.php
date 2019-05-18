<?php
/**
 * @file
 * Contains \Drupal\author_pane\Plugin\AuthorPane\AuthorDatumBase.
 */

/**
 * Each author datum will extend this base.
 */

namespace Drupal\author_pane\Plugin\AuthorPane;

use Drupal\Component\Plugin\PluginBase;

abstract class AuthorDatumBase extends PluginBase {
  /**
   * Machine name of the plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * Title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * A longer explanation of what the plugin is for.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $description;

  /**
   * The value associated with the plugin in this instance.
   *
   * @var mixed
   */
  protected $value;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $author;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set the properties from the annotation.
    $this->id = $plugin_definition['id'];
    $this->label = $plugin_definition['label'];
    $this->description = $plugin_definition['description'];
  }

  /**
   * Returns the id of the datum.
   *
   * @return string
   */
  public function id() {
    return $this->id;
  }

  /**
   * Returns the label of the datum.
   *
   * @return \Drupal\Core\Annotation\Translation
   */
  public function label() {
    return $this->label;
  }

  /**
   * Returns the description of the datum.
   *
   * @return \Drupal\Core\Annotation\Translation
   */
  public function description() {
    return $this->description;
  }

  /**
   * Returns the value of the datum.
   *
   * @return mixed
   */
  protected function value() {
    return $this->value;
  }

  /**
   * Sets the value of the datum.
   *
   * @param $value
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Sets the author of the datum.
   *
   * @param $author
   */
  public function setAuthor($author) {
    $this->author = $author;
  }

  /**
   * Returns the HTML string that contains the output of the datum.
   */
  public function output() {

  }

}
