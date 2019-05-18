<?php

namespace Drupal\message_thread\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\message_thread\MessageThreadTemplateInterface;

/**
 * Defines the Message thread template entity class.
 *
 * @ConfigEntityType(
 *   id = "message_thread_template",
 *   label = @Translation("Message thread template"),
 *   config_prefix = "template",
 *   bundle_of = "message_thread",
 *   entity_keys = {
 *     "id" = "template",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *   },
 *   admin_permission = "administer message threads",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\message_thread\Form\MessageThreadTemplateForm",
 *       "edit" = "Drupal\message_thread\Form\MessageThreadTemplateForm",
 *       "delete" = "Drupal\message_thread\Form\MessageThreadTemplateDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\message_thread\MessageThreadTemplateListBuilder",
 *     "view_builder" = "Drupal\message_thread\MessageThreadViewBuilder",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/message-threads/template/add",
 *     "edit-form" = "/admin/structure/message-threads/manage/{message_thread_template}",
 *     "delete-form" = "/admin/structure/message-threads/delete/{message_thread_template}"
 *   },
 *   config_export = {
 *     "template",
 *     "label",
 *     "langcode",
 *     "description",
 *     "message_template",
 *     "view_display",
 *     "view_display_id",
 *     "text",
 *     "settings",
 *     "status"
 *   }
 * )
 */
class MessageThreadTemplate extends ConfigEntityBundleBase implements MessageThreadTemplateInterface {

  /**
   * The ID of this message template thread.
   *
   * @var string
   */
  protected $template;

  /**
   * The UUID of the message template thread.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The human-readable name of the message template thread.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this message template thread.
   *
   * @var string
   */
  protected $description;

  /**
   * The message template the thread applies to.
   *
   * @var string
   */
  protected $message_template;

  /**
   * Array with the arguments and their replacement value, or callbacks.
   *
   * The argument keys will be replaced when rendering the message, and it
   * should be prefixed by @, %, ! - similar to way it's done in Drupal
   * core's t() function.
   *
   * @var array
   *
   * @code
   *
   * // Assuming out message-text is:
   * // %user-name created <a href="@message-url">@message-title</a>
   *
   * $message_template->arguments = [
   *   // Hard code the argument.
   *   '%user-name' => 'foo',
   *
   *   // Use a callback, and provide callbacks arguments.
   *   // The following example will call Drupal core's url() function to
   *   // get the most up-to-date path of message ID 1.
   *   '@message-url' => [
   *      'callback' => 'url',
   *      'callback arguments' => ['message/thread/1'],
   *    ],
   *
   *   // Use callback, but instead of passing callback argument, we will
   *   // pass the Message entity itself.
   *   '@message-title' => [
   *      'callback' => 'example_bar',
   *      'pass message thread' => TRUE,
   *    ],
   * ];
   * @endcode
   *
   * Arguments assigned to message-template can be overridden by the ones
   * assigned to the message thread.
   */
  public $arguments = [];

  /**
   * Serialized array with misc options.
   *
   * Purge settings:
   * - 'purge_override': TRUE or FALSE override the global behavior.
   *    "Message settings" will apply. Defaults to FALSE.
   * - 'purge_methods': An array of purge method plugin configuration, keyed by
   *   the plugin ID. An empty array indicates no purge is enabled (although
   *   global settings will be used unless 'purge_override' is TRUE).
   *
   * Token settings:
   * - 'token replace': Indicate if message thread's text should be passed
   *    through token_replace(). defaults to TRUE.
   * - 'token options': Array with options to be passed to
   *    token_replace().
   *
   * Tokens settings assigned to message-template can be overriden by the ones
   * assigned to the message thread.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->template;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default_value = NULL) {
    if (isset($this->settings[$key])) {
      return $this->settings[$key];
    }

    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemplate($template) {
    $this->template = $template;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessageTemplate($message_template) {
    $this->message_template = $message_template;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageTemplate() {
    return $this->message_template;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !$this->isNew();
  }

}
