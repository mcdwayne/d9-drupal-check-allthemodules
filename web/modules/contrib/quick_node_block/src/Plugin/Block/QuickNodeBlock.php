<?php

namespace Drupal\quick_node_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a Node Block with his display.
 *
 * @Block(
 *   id = "quick_node_block",
 *   admin_label = @Translation("Quick Node Block"),
 *   category = @Translation("Quick Node Block"),
 * )
 */
class QuickNodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity manager type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('current_route_match'),
        $container->get('entity_type.manager'),
        $container->get('entity_display.repository')
      );
  }

  /**
   * This function construct a block.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplay
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $entityDisplay) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplay = $entityDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['quick_node'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node'),
      '#description' => $this->t('What node do you want to show? You can write a node number or node title.'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'quick_node_block.autocomplete',
      '#default_value' => $config['quick_node'],
      '#ajax' => [
        'wrapper' => 'quick-ajax-wrapper',
        'callback' => [$this, 'ajaxCallback'],
        'disable-refocus' => TRUE,
      ],
    ];

    // Get node from the url.
    $nid_add = $this->routeMatch->getParameter('node');

    // Check if there is a node selected.
    $element = $form_state->getTriggeringElement();
    if (!empty($element['#value'])) {
      $node_title = $element['#value'];
    }
    elseif (!empty($nid_add)) {
      // Default value when creating the block from the node.
      $node = $this->entityTypeManager->getStorage('node')->load($nid_add);
      $node_title = $node->getTitle() . ' (' . $nid_add . ')';
      $form['quick_node']['#default_value'] = $node_title;
      $form['quick_node']['#attributes']['disabled'] = TRUE;
    }
    else {
      $node_title = $config['quick_node'];
    }
    // Get nid.
    preg_match("/.+\s\(([^\)]+)\)/", $node_title, $matches);
    $nid = $matches[1];

    if (empty($nid)) {
      $quick_display = $this->getQuickDisplays();
    }
    else {
      // Get bundle.
      $storage = $this->entityTypeManager->getStorage('node');
      $node = $storage->load($nid);
      $content_type = $node->getType();

      // Get view mode.
      $quick_display = $this->entityDisplay->getViewModeOptionsByBundle('node', $content_type);
    }

    $form['quick_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#description' => $this->t('How do you want the node to be displayed?. You must first choose a node.'),
      '#required' => TRUE,
      '#options' => $quick_display,
      '#default_value' => $config['quick_display'],
      '#prefix' => '<div id="quick-ajax-wrapper">',
      '#suffix' => '</div>',
      // Hide field if quick_node is empty.
      '#states' => [
        'visible' => [
          ':input[name$="[quick_node]"]' => [
            [
              'empty' => FALSE,
            ],
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Updates the options of a select list.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated form element.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {

    // Get the currently selected node.
    $element = $form_state->getTriggeringElement();
    $node_title = $element['#value'];

    // Get nid.
    preg_match("/.+\s\(([^\)]+)\)/", $node_title, $matches);
    $nid = $matches[1];

    // Get bundle.
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->load($nid);
    $content_type = $node->getType();

    // Get view mode.
    $view_mode = $this->entityDisplay->getViewModeOptionsByBundle('node', $content_type);
    $form['settings']['quick_display']['#options'] = $view_mode;
    return $form['settings']['quick_display'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    // Get quick_node value.
    $node_title = $form_state->getValue('quick_node');

    // Get nid.
    preg_match("/.+\s\(([^\)]+)\)/", $node_title, $matches);
    $nid = $matches[1];

    // Check if the node exists.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $values = $query->condition('nid', $nid)
      ->execute();

    if (empty($values)) {
      $form_state->setErrorByName('quick_node', $this->t('The chosen node does not exist. Choose another one please.'));
    }
    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['quick_node'] = $values['quick_node'];
    $this->configuration['quick_display'] = $values['quick_display'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();
    if (preg_match("/.+\s\(([^\)]+)\)/", $config['quick_node'], $matches)) {
      $nid = $matches[1];
      $view_mode = $config['quick_display'];
      $entity_type = 'node';
      $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
      $storage = $this->entityTypeManager->getStorage($entity_type);
      if ($node = $storage->load($nid)) {
        $build = $view_builder->view($node, $view_mode);
      }
    }
    return $build;
  }

  /**
   * Show all display modes of content.
   */
  protected function getQuickDisplays() {
    return $this->entityDisplay->getViewModeOptions('node');

  }

}
