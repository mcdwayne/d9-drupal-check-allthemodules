<?php

namespace Drupal\flag_search_api\Plugin\search_api\processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagService;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\SearchApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Item\ItemInterface;

/**
 * Search API Processor for indexing flags.
 *
 * @SearchApiProcessor(
 *   id = "flag_indexer",
 *   label = @Translation("Flag indexing"),
 *   description = @Translation("Switching on will enable indexing flags on
 *   content"), stages = {
 *     "add_properties" = 1,
 *     "pre_index_save" = -10
 *   }
 * )
 */
class FlagIndexer extends ProcessorPluginBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flagService;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag'),
      $container->get('logger.factory')->get('flag_search_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FlagService $flagService, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flagService = $flagService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'flag_index' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];

    $flags = $this->flagService->getAllFlags();
    foreach ($flags as $flag) {
      $options[$flag->get('id')] = $flag->get('label');
    }

    $form['flag_index'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable these flags on this index'),
      '#description' => $this->t('This will index IDs from users that flagged this content'),
      '#options' => $options,
      '#default_value' => isset($this->configuration['flag_index']) ? $this->configuration['flag_index'] : [],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValues()['flag_index']);
    if ($fields) {
      $fields = array_keys($fields);
    }
    $form_state->setValue('flag_index', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = array();

    if (!$datasource) {
      // Ensure that our fields are defined.
      $fields = $this->getFieldsDefinition();

      foreach ($fields as $field_id => $field_definition) {
        $properties[$field_id] = new ProcessorProperty($field_definition);
      }
    }
    return $properties;
  }

  /**
   * Helper function for defining our custom fields.
   */
  protected function getFieldsDefinition() {
    $config = $this->configuration['flag_index'];
    $fields = [];
    foreach ($config as $flag) {
      $label = $this->flagService->getFlagById($flag)->get('label');
      $fields['flag_' . $flag] = array(
        'label' => $label,
        'description' => $label,
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      );
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $config = $this->configuration['flag_index'];
    $flags = $this->flagService->getAllFlags();
    try {
      $entity = $item->getOriginalObject()->getValue();
      foreach ($config as $flag_id) {
        $fields = $this
          ->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'flag_' . $flag_id);
        foreach ($fields as $flag_field) {
          $users = $this->flagService->getFlaggingUsers($entity, $flags[$flag_id]);
          /** @var \Drupal\user\Entity\User $user */
          foreach ($users as $user) {
            $flag_field->addValue($user->id());
          }
        }
      }
    }
    catch (SearchApiException $exception) {
      $this->logger->error($exception->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->getFieldsDefinition() as $field_id => $field_definition) {
      try {
        $this->ensureField(NULL, $field_id, $field_definition['type']);
      }
      catch (SearchApiException $exception) {
        $this->logger->error($exception->getMessage());
      }
    }
  }

}
