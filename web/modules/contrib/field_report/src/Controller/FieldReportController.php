<?php

namespace Drupal\field_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Url;

/**
 * Class FieldReportController.
 *
 * @package Drupal\field_report\Controller
 */
class FieldReportController extends ControllerBase {

  /**
   * Return Entities listing and fields.
   */
  public function getEntityBundles() {

    $entityList =  \Drupal::entityManager()->getDefinitions();
    $allFields = \Drupal::service('entity.manager')->getFieldMap();
    $fieldListings = [];
    foreach ($entityList as $entityKey => $entityValue) {
      // If the Entity has bundle_entity_type set we grab it.
      $bundle_entity_type = $entityValue->get('bundle_entity_type');
      // Check to see if the entity has any bundle before continuing.
      if (!empty($bundle_entity_type)) {
        $entityTypes = \Drupal::service('entity.manager')->getStorage($bundle_entity_type)->loadMultiple();
        // Override the Entity Title / Label for select entities.
        switch ($entityKey) {
          case 'block_content':
            $bundleParent = $this->t('Blocks');
            break;

          case 'comment':
            $bundleParent = $this->t('Comments');
            break;

          case 'contact_message':
            $bundleParent = $this->t('Contact Forms');
            break;

          case 'node':
            $bundleParent = $this->t('Content Types');
            break;

          case 'shortcut':
            $bundleParent = $this->t('Shortcut Menus');
            break;

          case 'taxonomy_term':
            $bundleParent = $this->t('Taxonomy Terms');
            break;

          default:
            $entityLabel = $entityValue->get('label');
            $labelArray = (array) $entityLabel;
            $bundleParent = reset($labelArray);
        }
        // Output the Parent Entity label.
        $fieldListings[] = array(
          '#type' => 'markup',
          '#markup' => "<h1 class='fieldReportTable--h1'>" . $bundleParent . "</h1><hr />",
        );

        foreach ($entityTypes as $entityType) {
          // Load in the entityType fields.
          $fields = $this->entityTypeFields($entityKey, $entityType->id());

          foreach ($fields as $field => $field_array) {
            $relatedBundles = array();
            $entityOptions = array();

            // Get the related / used in bundles from the field.
            $relatedBundlesArray = $allFields[$entityKey][$field]['bundles'];

            // Create the edit field URLs.
            if ($field_array->access('update') && $field_array->hasLinkTemplate("{$field_array->getTargetEntityTypeId()}-field-edit-form")) {
              $editRoute = $field_array->urlInfo("{$field_array->getTargetEntityTypeId()}-field-edit-form");
              $entityEdit = \Drupal::l('Edit', $editRoute);
              $entityOptions[] = $entityEdit;
            }

            if ($field_array->access('delete') && $field_array->hasLinkTemplate("{$field_array->getTargetEntityTypeId()}-field-delete-form")) {
              // Create the delete field URLs.
              $deleteRoute = $field_array->urlInfo("{$field_array->getTargetEntityTypeId()}-field-delete-form");
              $entityDelete = \Drupal::l('Delete', $deleteRoute);
              $entityOptions[] = $entityDelete;
            }

            // Loop through related bundles.
            foreach ($relatedBundlesArray as $relatedBundlesValue) {
              if ($entityTypes[$relatedBundlesValue]->id() != $entityType->id()) {
                $relatedBundlesURL = $entityTypes[$relatedBundlesValue]->toUrl('edit-form');
                $relatedBundlesLabel = $entityTypes[$relatedBundlesValue]->label();
                if ($relatedBundlesURL) {
                  $relatedBundles[] = \Drupal::l($relatedBundlesLabel, $relatedBundlesURL);
                }
                else {
                  $relatedBundles[] = $relatedBundlesLabel;
                }
              }
            }

            $relatedBundlesRow['data']['related']['data'] = [
              '#theme' => 'item_list',
              '#items' => $relatedBundles,
              '#context' => ['list_style' => 'comma-list'],
            ];

            $entityOptionsEditDelete['data']['options']['data'] = [
              '#theme' => 'item_list',
              '#items' => $entityOptions,
              '#context' => ['list_style' => 'comma-list'],
            ];

            // Build out our table for the fields.
            $rows[] = array(
              $field_array->get('label'),
              $field_array->get('field_type'),
              $field_array->get('description'),
              $relatedBundlesRow,
              $entityOptionsEditDelete,
            );
          }
          // Output the field label.
          $fieldListings[] = array(
            '#type' => 'markup',
            '#markup' => "<h3 class='fieldReportTable--h3'>" . $entityType->label() . "</h3>",
          );
          // Output the field descriptin.
          $fieldListings[] = array(
            '#type' => 'markup',
            '#markup' => "<p>" . $entityType->get('description') . "</p>",
          );
          // If no rows exist we display a no results message.
          if (!empty($rows)) {
            $fieldListings[] = array(
              '#type' => 'table',
              '#header' => array(
                $this->t('Field Label'),
                $this->t('Field Type'),
                $this->t('Field Description'),
                $this->t('Also Used In'),
                $this->t('Options'),
              ),
              '#rows' => $rows,
              '#attributes' => array(
                'class' => array('fieldReportTable'),
              ),
              '#attached' => array(
                'library' => array(
                  'field_report/field-report',
                ),
              ),
            );
          }
          else {
            $fieldListings[] = array(
              '#type' => 'markup',
              '#markup' => $this->t("<p><b>No Fields are avaliable.</b></p>"),
            );
          }
          // Clear out the rows array to start fresh.
          unset($rows);
        }
      }
    }
    return $fieldListings;
  }

  /**
   * Helper function to get the field definitions.
   *
   * @param string $entityKey
   *   The entity's name.
   * @param string $contentType
   *   The content type name.
   *
   * @return array
   *   Returns an array of the fields.
   */
  public function entityTypeFields($entityKey, $contentType) {
    $entityManager = \Drupal::service('entity.manager');
    $fields = [];

    if (!empty($entityKey) && !empty($contentType)) {
      $fields = array_filter(
        $entityManager->getFieldDefinitions($entityKey, $contentType), function ($field_definition) {
          return $field_definition instanceof FieldConfigInterface;
        }
      );
    }

    return $fields;
  }

}
