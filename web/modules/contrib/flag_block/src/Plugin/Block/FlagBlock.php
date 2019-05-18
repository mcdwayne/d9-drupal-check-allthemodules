<?php

namespace Drupal\flag_block\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\FlagLinkBuilderInterface;

/**
 * Provides Flag Block.
 *
 * @Block(
 *   id = "flag_block",
 *   admin_label = @Translation("Flag block"),
 *   category = @Translation("Flag")
 * )
 */
class FlagBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
   private $flag;

  /**
   * Flag link builder service.
   *
   * @var \Drupal\flag\FlagLinkBuilderInterface
   */
  private $flagLinkBuilder;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * FlagBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\flag\FlagServiceInterface $flag
   * @param \Drupal\flag\FlagLinkBuilderInterface $flag_link_builder
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlagServiceInterface $flag, FlagLinkBuilderInterface $flag_link_builder, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flag = $flag;
    $this->flagLinkBuilder = $flag_link_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag'),
      $container->get('flag.link_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['flag_block_settings'] = [
      '#type' => 'select',
      '#title' => $this->t('Flag'),
      '#options' => $this->getFlagTypes(),
      '#required' => TRUE,
      '#default_value' => isset($config['flag_block_settings']) ? $config['flag_block_settings'] : '',
      '#description' => $this->t('You need select a type of <a href="@flag" target="_blank">Flag</a>', [
        '@flag' => Url::fromRoute('entity.flag.collection')->toString(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['flag_block_settings'] = $values['flag_block_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    /** @var \Drupal\flag\Entity\Flag $flag */
    $flag = $this->flag->getFlagById($config['flag_block_settings']);

    $build = $this->flagLinkBuilder->build(
      $flag->getFlaggableEntityTypeId(),
      $this->getRoutEntityId(),
      $config['flag_block_settings']
    );

    return $build;
  }

  /**
   * Get all flag types.
   *
   * @return array
   */
  private function getFlagTypes() {
    $flags = $this->flag->getAllFlags();
    $flag_list = [];

    foreach ($flags as $flag) {
      $flag_list[$flag->id()] = $flag->label();
    }

    return $flag_list;
  }

  /**
   * Get entity ID from path.
   *
   * @return int|null
   */
  private function getRoutEntityId() {
    // Entity will be found in the route parameters.
    if (($route = $this->routeMatch->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $this->routeMatch->getParameter($name);
          // Check if it is a correct entity.
          if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
            return $entity->id();
          }
        }
      }
    }
  }

}
