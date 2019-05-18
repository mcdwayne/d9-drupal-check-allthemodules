<?php

namespace Drupal\rng_quick\Plugin\Block;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\rng_quick\Form\RegisterBlockForm;

/**
 * Provides a 'Quick Registration' block.
 *
 * @Block(
 *   id = "rng_quick_registration",
 *   admin_label = @Translation("Quick Registration"),
 *   category = @Translation("RNG"),
 *   context = {
 *     "rng_event" = @ContextDefinition("entity",
 *       label = @Translation("Event"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class RngQuickRegisterBlock extends RngQuickBlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new RngQuickRegisterBlock instance.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, EventManagerInterface $event_manager, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $route_match, $event_manager);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('rng.event_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    try {
      if ($event = $this->getContextValue('rng_event')) {
        return $this->formBuilder->getForm(RegisterBlockForm::class, $event);
      }
    }
    catch (PluginException $e) {
    }
    return [];
  }

}
