<?php

namespace Drupal\term_tree_selector\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Term Tree Selector block.
 *
 * @Block(
 *   id = "term_tree_selector_block",
 *   admin_label = @Translation("Term Tree Selector"),
 * )
 */
class SelectorBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for SelectorBlock class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $configuration = $this->getConfiguration();

    // Build array of vocabulary options.
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $vocabulary_options = ['' => '- Select -'];
    foreach ($vocabularies as $vocabulary) {
      $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
    }
    $form['vocabulary'] = [
      '#type' => 'select',
      '#title' => t('Vocabulary'),
      '#required' => TRUE,
      '#options' => $vocabulary_options,
      '#default_value' => !empty($configuration['vocabulary']) ? $configuration['vocabulary'] : '',
    ];

    $form['root_label'] = [
      '#type' => 'textfield',
      '#title' => t('Root Label'),
      '#required' => TRUE,
      '#default_value' => !empty($configuration['root_label']) ? $configuration['root_label'] : '',
    ];

    $form['leaf_label'] = [
      '#type' => 'textfield',
      '#title' => t('Leaf Label'),
      '#required' => TRUE,
      '#default_value' => !empty($configuration['leaf_label']) ? $configuration['leaf_label'] : '',
    ];

    $form['leaf_level'] = [
      '#type' => 'select',
      '#title' => t('Leaf Level'),
      '#required' => TRUE,
      '#options' => [
        '2' => 'Second',
        '3' => 'Third',
        'all' => 'All',
      ],
      '#default_value' => !empty($configuration['leaf_level']) ? $configuration['leaf_level'] : '',
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#required' => FALSE,
      '#default_value' => !empty($configuration['description']) ? $configuration['description'] : '',
    ];

    $form['submit_label'] = [
      '#type' => 'textfield',
      '#title' => t('Submit Label'),
      '#required' => TRUE,
      '#default_value' => !empty($configuration['submit_label']) ? $configuration['submit_label'] : '',
    ];

    $form['autosubmit'] = [
      '#type' => 'checkbox',
      '#title' => t('Auto Submit'),
      '#required' => FALSE,
      '#default_value' => !empty($configuration['autosubmit']) ? $configuration['autosubmit'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('vocabulary', $form_state->getValue('vocabulary'));
    $this->setConfigurationValue('root_label', $form_state->getValue('root_label'));
    $this->setConfigurationValue('leaf_label', $form_state->getValue('leaf_label'));
    $this->setConfigurationValue('leaf_level', $form_state->getValue('leaf_level'));
    $this->setConfigurationValue('description', $form_state->getValue('description'));
    $this->setConfigurationValue('submit_label', $form_state->getValue('submit_label'));
    $this->setConfigurationValue('autosubmit', $form_state->getValue('autosubmit'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_configuration = $this->getConfiguration();
    return [
      '#theme' => 'term_tree_selector',
      '#vocabulary' => $block_configuration['vocabulary'],
      '#block_title' => $block_configuration['label'],
      '#root_label' => $block_configuration['root_label'],
      '#leaf_label' => $block_configuration['leaf_label'],
      '#leaf_level' => $block_configuration['leaf_level'],
      '#description' => $block_configuration['description'],
      '#submit_label' => $block_configuration['submit_label'],
      '#autosubmit' => $block_configuration['autosubmit'],
      '#attached' => [
        'library' => ['term_tree_selector/selector'],
      ],
    ];
  }

}
