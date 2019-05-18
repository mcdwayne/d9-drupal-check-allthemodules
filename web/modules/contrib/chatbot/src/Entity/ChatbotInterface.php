<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a chatbot entity.
 */
interface ChatbotInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Workflow title.
   *
   * @return string
   *   Title of the Workflow.
   */
  public function getTitle();

  /**
   * Sets the Workflow title.
   *
   * @param string $title
   *   The Workflow title.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   The called Workflow entity.
   */
  public function setTitle($title);

  /**
   * Gets the Workflow creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Workflow.
   */
  public function getCreatedTime();

  /**
   * Sets the Workflow creation timestamp.
   *
   * @param int $timestamp
   *   The Workflow creation timestamp.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   The called Workflow entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Workflow published status indicator.
   *
   * Unpublished Workflow are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Workflow is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Workflow.
   *
   * @param bool $published
   *   TRUE to set this Workflow to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   The called Workflow entity.
   */
  public function setPublished($published);
  /**
   * Returns the chatbot plugin.
   *
   * @return \Drupal\chatbot\Plugin\ChatbotPluginInterface
   *   The chatbot plugin used by this chatbot entity.
   */
  public function getPlugin();

  /**
   * Sets the chatbot plugin.
   *
   * @param string $plugin_id
   *   The chatbot plugin ID.
   */
  public function setPlugin($plugin_id);

  /**
   * Returns a Workflow enity.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   Workflow entity
   */
  public function getWorkflow();

  /**
   * Sets the Workflow entity.
   *
   * @param $workfow
   *   Workflow entity
   *
   * @return $this
   */
  public function setWorkfow($workfow);

  /**
   * Returns webhook path.
   *
   * @return string
   *  Webhook path.
   */
  public function getWebhookPath();

  /**
   * Sets the webhook path.
   *
   * @param string $webhook_path
   *  Webhook path.
   *
   * @return $this
   */
  public function setWebhookPath($webhook_path);

  /**
   * Returns the configuration.
   *
   * @return array
   *  Configuration.
   */
  public function getConfiguration();

  /**
   * Sets the configuration.
   *
   * @param array $configuration
   *  Configuration.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration);

}
