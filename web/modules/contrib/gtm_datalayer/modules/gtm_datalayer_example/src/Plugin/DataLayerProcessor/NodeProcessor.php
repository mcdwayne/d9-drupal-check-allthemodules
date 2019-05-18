<?php

namespace Drupal\gtm_datalayer_example\Plugin\DataLayerProcessor;

use Drupal\gtm_datalayer\MetatagsRenderer;
use Drupal\gtm_datalayer\Plugin\DataLayerProcessorEntityBase;
use Drupal\gtm_datalayer\TagsRenderer;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a GTM dataLayer processor for node entities.
 *
 * @DataLayerProcessor(
 *   id = "gtm_datalayer_example_node",
 *   label = @Translation("Node"),
 *   description = @Translation("Provides a processor for node entities."),
 *   group = @Translation("Global"),
 *   category = @Translation("Entity"),
 * )
 */
class NodeProcessor extends DataLayerProcessorEntityBase {

  /**
   * The dataLayer Metatags renderer.
   *
   * @var \Drupal\gtm_datalayer\MetatagsRenderer
   */
  protected $metatagsRenderer;

  /**
   * The dataLayer Tags renderer.
   *
   * @var \Drupal\gtm_datalayer\TagsRenderer
   */
  protected $tagsRenderer;

  /**
   * {@inheritdoc}
   */
  public function render() {
    parent::render();

    if (!$this->isRequestException() && $this->getEntity() instanceof NodeInterface) {
      $this->addTag(['entity_owner'], (int) $this->getEntity()->getOwnerId());
      $this->addTag(['entity_created'], $this->dateFormatter->format($this->getEntity()->getCreatedTime(), 'gtm_datalayer'));
      $this->addTag(['entity_promoted'], $this->getEntity()->isPromoted());
      $this->addTag(['entity_sticky'], $this->getEntity()->isSticky());
    }

    return $this->getTags();
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeRenderers() {
    // Add needed field type renderers.
    $this->tagsRenderer = new TagsRenderer();
    $this->metatagsRenderer = new MetatagsRenderer($this->getEntity(), $this->token);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityFromContext() {
    $this->setEntity($this->currentRouteMatch->getParameter('node'));
  }

  /**
   * Renders the entity_reference fields.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The entity field definition.
   * @param \Drupal\Core\Field\FieldItemListInterface $entity_field
   *   The lists of field items.
   */
  protected function renderFieldTypeEntityReference(FieldDefinitionInterface $field_definition, FieldItemListInterface $entity_field) {
    $settings = $field_definition->getSettings();

    $target_type = $settings['target_type'];
    $bundles = isset($settings['handler_settings']['target_bundles']) ? $settings['handler_settings']['target_bundles'] : [];

    switch ($target_type) {
      case 'taxonomy_term':
        // Tags case.
        if (in_array('tags', $bundles)) {
          $tags = $this->tagsRenderer->render($entity_field->referencedEntities());

          if (count($tags)) {
            $entity_tags = $this->getTag(['entity_tags'], []);
            $this->addTag(['entity_tags'], array_merge($entity_tags, $tags));
          }
        }
        break;
    }
  }

  /**
   * Renders the metatag fields.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The entity field definition.
   * @param \Drupal\Core\Field\FieldItemListInterface $entity_field
   *   The lists of field items.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function renderFieldTypeMetatag(FieldDefinitionInterface $field_definition, FieldItemListInterface $entity_field) {
    $tags = $this->metatagsRenderer->render(unserialize($entity_field->first()->value));

    if (count($tags)) {
      $entity_metatags = $this->getTag(['entity_metatags'], []);
      $this->addTag(['entity_metatags'], array_merge($entity_metatags, $tags));
    }
  }

}
