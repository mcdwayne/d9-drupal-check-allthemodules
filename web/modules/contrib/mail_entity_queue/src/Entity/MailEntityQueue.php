<?php

namespace Drupal\mail_entity_queue\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\mail_entity_queue\MailEntityQueueProcessorPluginCollection;

/**
 * Defines the mail queue entity class.
 *
 * @ConfigEntityType(
 *   id = "mail_entity_queue",
 *   label = @Translation("Mail entity queue"),
 *   label_singular = @Translation("Mail entity queue"),
 *   label_plural = @Translation("Mail entity queues"),
 *   label_count = @PluralTranslation(
 *     singular = "@count mail entity queue",
 *     plural = "@count mail entity queues",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\mail_entity_queue\MailEntityQueueListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mail_entity_queue\Form\MailEntityQueueForm",
 *       "edit" = "Drupal\mail_entity_queue\Form\MailEntityQueueForm",
 *       "delete" = "Drupal\mail_entity_queue\Form\MailEntityQueueDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer mail entity queues",
 *   config_prefix = "mail_entity_queue",
 *   bundle_of = "mail_entity_queue_item",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "uuid",
 *     "id",
 *     "label",
 *     "description",
 *     "cron_delay",
 *     "cron_items",
 *     "format",
 *     "queue_processor",
 *   },
 *   links = {
 *     "add-form" = "/config/system/mail-entity-queue/add",
 *     "edit-form" = "/config/system/mail-entity-queue/{mail_entity_queue}/edit",
 *     "delete-form" = "/config/system/mail-entity-queue/{mail_entity_queue}/delete",
 *     "collection" = "/config/system/mail-entity-queue",
 *   },
 * )
 */
class MailEntityQueue extends ConfigEntityBundleBase implements MailEntityQueueInterface {

  /**
   * The mail entity queue ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The mail entity queue label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this mail entity queue.
   *
   * @var string
   */
  protected $description;

  /**
   * Number of items to process in each cron run.
   *
   * @var integer
   */
  protected $cron_items = 10;

  /**
   * Pause between execution of mail queue elements, in milliseconds.
   *
   * @var integer
   */
  protected $cron_delay = 1000;

  /**
   * Defines what format to send the e-mails in.
   *
   * @var string
   */
  protected $format = 'text/plain';

  /**
   * The mail queue plugin processor id.
   *
   * @var string
   */
  protected $queue_processor;

  /**
   * The mail queue processor plugin.
   *
   * @var \Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorInterface
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
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
  public function getCronItems() {
    return $this->cron_items;
  }

  /**
   * {@inheritdoc}
   */
  public function setCronItems(integer $cron_items) {
    $this->cron_items = $cron_items;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCronDelay() {
    return $this->cron_delay;
  }

  /**
   * {@inheritdoc}
   */
  public function setCronDelay(integer $cron_delay) {
    $this->cron_delay = $cron_delay;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueProcessor() {
    return $this->getMailEntityQueueProcessorCollection()->get($this->queue_processor);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueProcessorId() {
    return $this->queue_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueProcessorId(string $queue_processor) {
    $this->queue_processor = $queue_processor;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(string $mail, array $data, $entity_type = NULL, $entity_id = NULL) {
    $item = MailEntityQueueItem::create([
      'queue' => $this->id(),
      'mail' => $mail,
      'data' => $data,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'status' => MailEntityQueueItemInterface::PENDING,
    ]);

    return $item->save();
  }

  /**
   * Encapsulates the creation of the mail queue processor's
   * LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The mail queue's processor plugin collection.
   */
  protected function getMailEntityQueueProcessorCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new MailEntityQueueProcessorPluginCollection(\Drupal::service('plugin.manager.mail_entity_queue.processor'), $this->queue_processor, $this->id);
    }

    return $this->pluginCollection;
  }

  /**
   * Gets the e-mail format.
   *
   * @return string
   */
  public function getFormat() {
    return $this->format;
  }

  /**
   * Sets the format for the e-mail.
   *
   * @param string $format
   *   The format for the e-mail.
   *
   * @return $this
   */
  public function setFormat(string $format) {
    $this->format = $format;
    return $this;
  }
}
