<?php

namespace Drupal\entity_tools_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_tools\NodeQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_tools\EntityTools;

/**
 * Provides an 'PromotedNodesBlock' block.
 *
 * Lists the n latest nodes from a content type
 * display the first as a highlighted view mode (defaults to full)
 * and the 2 others as another view mode (defaults to teaser).
 *
 * @Block(
 *  id = "promoted_nodes_block",
 *  admin_label = @Translation("Promoted Nodes"),
 * )
 */
class PromotedNodesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\entity_tools\EntityTools definition.
   *
   * @var \Drupal\entity_tools\EntityTools
   */
  protected $entityTools;

  /**
   * Constructs a new NodeViewModesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTools $entity_tools
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTools = $entity_tools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_tools')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items' => 3,
      'content_type' => 'article',
      'highlighted' => $this->t('full'),
      'default' => $this->t('teaser'),
      'list_type' => 'ul',
      'wrapper_class' => '',
      'list_class' => '',
      'item_class' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $contentTypes = $this->entityTools->getContentTypes();
    $contentTypeOptions = [];
    foreach ($contentTypes as $type) {
      $contentTypeOptions[$type->id()] = $type->label();
    }
    $form['items'] = [
      '#type' => 'number',
      '#title' => $this->t('Items'),
      '#description' => $this->t('Amount of items to be displayed.'),
      '#default_value' => $this->configuration['items'],
      '#weight' => '1',
    ];
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#description' => '',
      '#default_value' => $this->configuration['content_type'],
      '#options' => $contentTypeOptions,
      '#weight' => '2',
    ];
    $form['view_modes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View modes'),
      '#weight' => '3',
      '#open' => TRUE,
    ];
    $form['view_modes']['highlighted'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlighted view mode'),
      '#description' => '',
      '#default_value' => $this->configuration['highlighted'],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['view_modes']['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default view mode'),
      '#description' => '',
      '#default_value' => $this->configuration['default'],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['output'] = [
      '#type' => 'details',
      '#title' => $this->t('Output'),
      '#description' => $this->t('Change markup and classes.'),
      '#weight' => '4',
      '#open' => FALSE,
    ];
    $form['output']['list_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('List type'),
      '#options' => ['ul' => $this->t('Unordered list'), 'li' => $this->t('Ordered list')],
      '#default_value' => $this->configuration['list_type'],
    ];
    $form['output']['wrapper_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('The class to provide on the wrapper, outside the list.'),
      '#default_value' => $this->configuration['wrapper_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['output']['list_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List class'),
      '#description' => $this->t('The class to provide on the list element itself.'),
      '#default_value' => $this->configuration['list_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['output']['item_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item class'),
      '#description' => $this->t('The class to provide on each list item.'),
      '#default_value' => $this->configuration['item_class'],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['items'] = $form_state->getValue('items');
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['highlighted'] = $form_state->getValue(['view_modes', 'highlighted']);
    $this->configuration['default'] = $form_state->getValue(['view_modes', 'default']);
    $this->configuration['list_type'] = $form_state->getValue(['output', 'list_type']);
    $this->configuration['wrapper_class'] = $form_state->getValue(['output', 'wrapper_class']);
    $this->configuration['list_class'] = $form_state->getValue(['output', 'list_class']);
    $this->configuration['item_class'] = $form_state->getValue(['output', 'item_class']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Select and order.
    $query = new NodeQuery();
    $query->limit((int) $this->configuration['items']);
    $query->latestFirst();
    // Load from storage.
    $nodes = $this->entityTools->getNodes($this->configuration['content_type'], $query);
    // Get the display.
    $items = [];
    $count = 0;
    foreach ($nodes as $node) {
      // Override the first view mode.
      if ($count === 0) {
        $viewMode = $this->configuration['highlighted'];
      }
      else {
        $viewMode = $this->configuration['default'];
      }
      $items[] = $this->entityTools->entityDisplay($node, $viewMode);
      ++$count;
    }
    // Prepare the render array.
    $listAttributes = [];
    $listAttributes['type'] = $this->configuration['list_type'];
    $listAttributes['list_class'] = $this->configuration['list_class'];
    $listAttributes['item_class'] = $this->configuration['item_class'];
    $build['entity_tools_example_articles'] = $this->entityTools->listDisplay($items, $listAttributes);
    if (!empty($this->configuration['wrapper_class'])) {
      $build['#attributes']['class'][] = $this->configuration['wrapper_class'];
    }
    // For debug purpose only.
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
