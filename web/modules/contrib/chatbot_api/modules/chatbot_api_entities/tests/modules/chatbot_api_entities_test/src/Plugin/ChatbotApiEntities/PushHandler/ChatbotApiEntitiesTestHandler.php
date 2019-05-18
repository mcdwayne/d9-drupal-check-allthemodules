<?php

namespace Drupal\chatbot_api_entities_test\Plugin\ChatbotApiEntities\PushHandler;

use Drupal\chatbot_api_entities\Entity\EntityCollection;
use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\chatbot_api_entities\Plugin\PushHandlerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a test push handler for storing pushed entities in state.
 *
 * @PushHandler(
 *   id = "chatbot_api_entities_test",
 *   label = @Translation("Test handler")
 * )
 */
class ChatbotApiEntitiesTestHandler extends PushHandlerBase {

  const STATE_KEY = 'chatbot_api_entities_test';

  /**
   * State storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ChatbotApiEntitiesTestHandler object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Drupal\Core\State\StateInterface $state
   *   State storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $httpClient, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $httpClient);
    $this->state = $state;
    $this->configuration += ['settings' => ['remote_id' => '']];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function pushEntities(array $entities, EntityCollection $entityCollection) {
    $remote_id = $this->configuration['settings']['remote_id'];
    $stored = $this->state->get(self::STATE_KEY, []);
    $stored[$remote_id] = $this->formatEntries($entities, $entityCollection);
    $this->state->set(self::STATE_KEY, $stored);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration(EntityCollectionInterface $entityCollection, array $configuration) {
    $configuration['settings']['added_at_save_time'] = $entityCollection->id();
    return parent::saveConfiguration($entityCollection, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state) {
    return [
      'remote_id' => [
        '#type' => 'textfield',
        '#title' => new TranslatableMarkup('Remote name'),
        '#description' => new TranslatableMarkup('Give the collection a name on the remote API'),
        '#default_value' => $this->configuration['settings']['remote_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(EntityCollectionInterface $entityCollection, array $form, FormStateInterface $form_state) {
    $element_key = [
      'push_handler_configuration',
      $this->pluginId,
      'settings',
      'remote_id',
    ];
    if (!$form_state->getValue($element_key)) {
      $form_state->setError($form['push_handlers']['settings'][$this->pluginId], new TranslatableMarkup('Remote ID is required'));
    }
  }

}
