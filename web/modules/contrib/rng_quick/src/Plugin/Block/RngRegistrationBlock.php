<?php

namespace Drupal\rng_quick\Plugin\Block;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rng\Entity\Registration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rng\EventManagerInterface;

/**
 * Provides a 'Registration' block.
 *
 * @Block(
 *   id = "rng_registration",
 *   admin_label = @Translation("Registration"),
 *   category = @Translation("RNG"),
 *   context = {
 *     "rng_event" = @ContextDefinition("entity",
 *       label = @Translation("Event"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class RngRegistrationBlock extends RngQuickBlockBase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new RngRegistrationBlock instance.
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
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, EventManagerInterface $event_manager, EntityFormBuilderInterface  $entity_form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $route_match, $event_manager);
    $this->entityFormBuilder = $entity_form_builder;
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
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    try {
      if ($event = $this->getContextValue('rng_event')) {
        $event_meta = $this->eventManager->getMeta($event);
        $registration_types = $event_meta->getRegistrationTypes();

        // Just want one registration type.
        if (count($registration_types) === 1) {
          $registration_type = reset($registration_types);
          $registration = Registration::create([
            'type' => $registration_type->id(),
            'event' => $event,
          ]);
          return $this->entityFormBuilder->getForm($registration);
        }
      }
    }
    catch (PluginException $e) {
    }

    return [];
  }

}
