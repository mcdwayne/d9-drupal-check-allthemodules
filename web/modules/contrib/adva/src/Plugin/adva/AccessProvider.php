<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines an plugin base for AccessProvider Plugins.
 */
class AccessProvider extends PluginBase implements AccessProviderInterface {

  use StringTranslationTrait;

  /**
   * The provider label.
   *
   * @var string
   */
  public $label;

  /**
   * List of operations that the access provider grants access for.
   *
   * @var string
   */
  public $operations;

  /**
   * Access Consumer for the provider instance.
   *
   * @var \Drupal\adva\Plugin\adva\AccessConsumerInterface
   */
  private $consumer;

  /**
   * Create a new AccessProvider.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Unique plugin id.
   * @param array|mixed $plugin_definition
   *   Plugin instance definition.
   * @param Drupal\adva\Plugin\adva\AccessConsumerInterface $consumer
   *   Associated Access Consumer Instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessConsumerInterface $consumer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->consumer = $consumer;
    $this->label = $this->getPluginDefinition()["label"];
    $this->operations = $this->getPluginDefinition()["operations"];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    if ($this->label === NULL) {
      // Load value from definition.
      $this->label = $this->get()["label"];
    }
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumer() {
    return $this->consumer;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    if ($this->operations === NULL) {
      // Load value from definition.
      $this->operations = $this->get()["operations"];
    }
    return $this->operations;
  }

  /**
   * {@inheritdoc}
   */
  public static function appliesToType(EntityTypeInterface $entityType) {
    // Make provider available to all types, unless overridden.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecords(EntityInterface $entity) {
    // Provide no grants by default.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    // Provide no grants by default.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm(array $form, FormStateInterface $form_state) {
    // Return null to not supply a sub_form.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public static function getHelperMessage(array $definition) {
    return \Drupal::translation()->translate('No information available.');
  }

}
