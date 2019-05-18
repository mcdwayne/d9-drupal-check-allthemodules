<?php

namespace Drupal\ief_dnd_categories\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ief_dnd_categories\Services\IefDndCategoriesFormHandler;
use Drupal\ief_dnd_categories\Services\IefDndCategoriesPositionHandler;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'documents_list_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ief_dnd_categories_field_formatter",
 *   label = @Translation("DnD categories field formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class IefCategoriesListFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $configKey = [$items->getEntity()->getEntityType()->id(), $items->getEntity()->bundle(), $items->getName()];
    $dndCategoriesFormHandler = new IefDndCategoriesFormHandler($configKey[0], $configKey[1], $configKey[2]);
    $dndFormConfig = $dndCategoriesFormHandler->getFormConfig(implode('::', $configKey));

    $delta = 0;
    $elements = [];

    // Processes categories positions from items categories.
    $entityList = $items->getValue();
    foreach ($entityList as $index => $entityData) {
      $targetType = $items->get($index)->getDataDefinition()->getSetting('target_type');
      $rowEntity = \Drupal::entityTypeManager()->getStorage($targetType)->load($entityData['target_id']);
      $categoryId = $rowEntity->get($dndFormConfig['field_category'])->getValue();
      if (count($categoryId) && isset($categoryId[0]['target_id'])) {
        $entityList[$index]['category-id'] = $categoryId[0]['target_id'];
      }
      else {
        $entityList[$index]['category-id'] = '';
      }
    }

    $dndCategoriesFormHandler->categoriesHandler->setCategoriesPositionsFromEntityRowsData($entityList);

    $categoriesPositions = $dndCategoriesFormHandler->categoriesHandler->getCategoriesPositions();

    foreach ($items as $position => $item) {
      // Categories:
      if (isset($categoriesPositions[$position])) {
        $term = Term::load($categoriesPositions[$position]);
        if ($term instanceof Term) {
          $delta++;
          $elements[$delta]['category'] = entity_view($term, 'default');
        }
      }
      // Entity Rows:
      $entityTypeName = $item->getDataDefinition()->getSetting('target_type');
      $entity = \Drupal::service('entity_type.manager')->getStorage($entityTypeName)->load($item->getValue()['target_id']);
      $elements[$delta]['links'][] = entity_view($entity, 'default');
    }
    return $elements;
  }

}
