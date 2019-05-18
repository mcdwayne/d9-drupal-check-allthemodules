<?php

namespace Drupal\entity_reference_text\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityReferenceController extends ControllerBase {

  public function autocomplete($target_type, $bundle, $field_name) {
    $field_definitions = $this->entityManager()->getFieldDefinitions($target_type, $bundle);
    if (!isset($field_definitions[$field_name])) {
      throw new NotFoundHttpException("No field: $field_name");
    }

    $field_definition = $field_definitions[$field_name];

    $possible_entities =$this->flattenBundles($this->getSelectionPlugin($field_definition)->getReferenceableEntities());
    return new JsonResponse(array_map([$this, 'encodeNameForJs'], array_values($possible_entities)));
  }

  /**
   * Encodes names for JS by replacing replaces with underscores.
   *
   * @param string $name
   *
   * @return string
   *
   * @see \Drupal\entity_reference_text\Plugin\Field\FieldWidget\EntityReferenceWithTextAutocompletion::decodeNameFromJs
   */
  protected function encodeNameForJs($name) {
    return str_replace(' ', '_', $name);
  }

  /**
   * Removes the bundle level keys from an array of entities by bundle.
   *
   * @param mixed[][] $entities_by_bundles
   *   An array of entity IDs keyed by bundle.
   *
   * @return mixed[]
   *   An array of entity IDs.
   */
  protected function flattenBundles(array $entities_by_bundles) {
    $entities = [];
    array_walk($entities_by_bundles, function ($entities_one_bundle) use (&$entities) {
      $entities += $entities_one_bundle;
    });
    return $entities;
  }

  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  protected function getSelectionPlugin(FieldDefinitionInterface $field) {
    $options = array(
      'target_type' => $field->getFieldStorageDefinition()->getSetting('target_type'),
      'handler' => $field->getSetting('handler'),
      'handler_settings' => $field->getSetting('handler_settings'),
    );
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

}
