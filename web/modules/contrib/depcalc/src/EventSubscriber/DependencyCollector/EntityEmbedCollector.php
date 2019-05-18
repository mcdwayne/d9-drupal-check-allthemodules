<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\depcalc\FieldExtractor;
use Drupal\field\Entity\FieldStorageConfig;

class EntityEmbedCollector extends BaseDependencyCollector {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates entities embedded into the text areas of other entities.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The CalculateEntityDependenciesEvent event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if (!\Drupal::moduleHandler()->moduleExists('entity_embed')) {
      return;
    }
    $this->extractEmbeddedEntities($event->getEntity(), $event);
  }

  /**
   * Extracts embedded entities from the text fields of another entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to extract embedded entities.
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The CalculateEntityDependenciesEvent event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function extractEmbeddedEntities(EntityInterface $entity, CalculateEntityDependenciesEvent $event) {
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    $fields = FieldExtractor::getFieldsFromEntity($entity, function(ContentEntityInterface $entity, $field_name, FieldItemListInterface $field) {
      $storage_definition = $field->getFieldDefinition()->getFieldStorageDefinition();
      return ($storage_definition instanceof FieldStorageConfig && $storage_definition->getTypeProvider() == 'text');
    });
    foreach ($fields as $field) {
      foreach ($field->getValue() as $value) {
        /** @var \Drupal\filter\Entity\FilterFormat $filter_format */
        $filter_format = \Drupal::entityTypeManager()->getStorage('filter_format')->load($value['format']);
        $filters = $filter_format->filters();
        $filters->sort();
        /** @var \Drupal\filter\Plugin\FilterInterface $filter */
        foreach ($filters as $filter) {
          // If this text area can have entities embedded, we want to
          // manually extract the entities contained therein.
          if ($filter->getPluginId() == 'entity_embed') {
            $text = $value['value'];
            if (strpos($text, 'data-entity-type') !== FALSE && (strpos($text, 'data-entity-embed-display') !== FALSE || strpos($text, 'data-view-mode') !== FALSE)) {
              $dom = Html::load($text);
              $xpath = new \DOMXPath($dom);

              foreach ($xpath->query('//drupal-entity[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]') as $node) {
                /** @var \DOMElement $node */
                $entity_type = $node->getAttribute('data-entity-type');
                if ($id = $node->getAttribute('data-entity-uuid')) {
                  $embed = \Drupal::entityTypeManager()->getStorage($entity_type)->loadByProperties(['uuid' => $id]);
                  if ($embed) {
                    $embed = current($embed);
                  }
                }
                else {
                  $id = $node->getAttribute('data-entity-id');
                  $embed = \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);
                }
                if ($embed) {
                  $embed_wrapper = new DependentEntityWrapper($embed);
                  $local_dependencies = [];
                  $this->mergeDependencies($embed_wrapper, $event->getStack(), $this->getCalculator()
                    ->calculateDependencies($embed_wrapper, $event->getStack(), $local_dependencies));
                  $event->addDependency($embed_wrapper);
                }
              }
            }
          }
        }
      }
    }
  }

}
