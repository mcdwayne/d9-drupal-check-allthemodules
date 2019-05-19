<?php

namespace Drupal\trash\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteTab extends LocalTaskDefault implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\content_moderation\ModerationInformation
   */
  public $moderationInformation;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Constructs a new DeleteTab object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\content_moderation\ModerationInformation $moderation_information
   *   The moderation information.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $moderation_information) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    // Override the node here with the latest revision.
    $this->entity = $route_match->getParameter($this->pluginDefinition['entity_type_id']);
    return parent::getRouteParameters($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->moderationInformation->isModeratedEntity($this->entity)
      ? $this->t('Move to trash')
      : $this->t('Delete');
  }
}