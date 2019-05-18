<?php

namespace Drupal\mail\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\mail\MailMessageInterface;

/**
 * Defines the Mail message entity.
 *
 * @ConfigEntityType(
 *   id = "mail_message",
 *   label = @Translation("Mail message"),
 *   handlers = {
 *     "list_builder" = "Drupal\mail\MailMessageListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\mail\Form\MailMessageForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\mail\MailMessageHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "mail_message",
 *   admin_permission = "administer mail messages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/mail/{mail_message}",
 *     "edit-form" = "/admin/config/system/mail/{mail_message}/edit",
 *     "collection" = "/admin/config/system/mail"
 *   }
 * )
 */
class MailMessage extends ConfigEntityBase implements ConfigEntityInterface, MailMessageInterface {

  /**
   * The Mail message ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Mail message label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailBackendPluginID() {
    return $this->mail_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailProcessorPluginID() {
    return $this->message_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Mail plugins don't implement PluginInspectionInterface, so can't be
    // passed to calculatePluginDependencies(), and can't be interrogated about
    // their provider.

    $processor_plugin_id = $this->getMailProcessorPluginID();
    if (!empty($processor_plugin_id)) {
      // TODO: this should use plugin collections.
      $processor_plugin = \Drupal::service('plugin.manager.mail_message_processor')->createInstance($processor_plugin_id);
      $this->calculatePluginDependencies($processor_plugin);
    }
  }

}
