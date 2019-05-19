<?php

namespace Drupal\wisski_core\Entity\Render;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Entity\Render\EntityFieldRenderer;
use Drupal\Core\Field\FieldItemList;
use Drupal\wisski_core\Entity\WisskiEntity;



/**
 * Changes render mechanism for entities - because wisski views
 * do not necessarily store information in the right position
 *
 */
class WisskiEntityFieldRenderer extends EntityFieldRenderer {
  /**
   * Builds the render arrays for all fields of all result rows.
   *
   * The output is built using EntityViewDisplay objects to leverage
   * multiple-entity building and ensure a common code path with regular entity
   * view.
   * - Each relationship is handled by a separate EntityFieldRenderer instance,
   *   since it operates on its own set of entities. This also ensures different
   *   entity types are handled separately, as they imply different
   *   relationships.
   * - Within each relationship, the fields to render are arranged in unique
   *   sets containing each field at most once (an EntityViewDisplay can
   *   only process a field once with given display options, but a View can
   *   contain the same field several times with different display options).
   * - For each set of fields, entities are processed by bundle, so that
   *   formatters can operate on the proper field definition for the bundle.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   An array of all ResultRow objects returned from the query.
   *
   * @return array
   *   A renderable array for the fields handled by this renderer.
   *
   * @see \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected function buildFields(array $values) {
    $build = [];
#    dpm("yay!");
    // most of the code is copied from the original file.
    if ($values && ($field_ids = $this->getRenderableFieldIds())) {
      $entity_type_id = $this->getEntityTypeId();

      // Collect the entities for the relationship, fetch the right translation,
      // and group by bundle. For each result row, the corresponding entity can
      // be obtained from any of the fields handlers, so we arbitrarily use the
      // first one.
      $entities_by_bundles = [];
      $field = $this->view->field[current($field_ids)];
      $aux_entities = [];

      foreach ($values as $result_row) {
        if ($entity = $field->getEntity($result_row)) {
          $entities_by_bundles[$entity->bundle()][$result_row->index] = $this->getEntityTranslation($entity, $result_row);
          
          foreach ($field_ids as $field_id) {
            $mfield = $this->view->field[$field_id];
            $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);

            $bundles = $field_storage_definitions[$mfield->definition['field_name']]->getBundles();
            
            $bundle_id = current($bundles);

#            dpm(serialize($field_storage_definitions[$mfield->definition['field_name']]['settings']['target_type']), "seria");

            if($bundle_id == $entity->bundle())
              continue;
            
            $field_name = $mfield->definition['field_name'];

            $is_file = in_array('file',$field_storage_definitions[$mfield->definition['field_name']]->getDependencies()['module']);
            
            if (isset($result_row->$field_id)) {
              $data = $result_row->$field_id;
#              dpm($data, "data");
#              $tmpentity = new WisskiEntity(array('eid' => $result_row->index, 'bundle' => $bundle_id, $field_name  => $data),'wisski_individual',$bundle_id);
#              dpm($entity->id(), "eid parent");
              $tmpentity = new WisskiEntity(array('eid' => $entity->id(), 'bundle' => $bundle_id, $field_name  => $data),'wisski_individual',$bundle_id);
              
              // this kills the url.... which probably is bad!
              $tmpentity->enforceIsNew();
#               $tmpentity = $entity;

#              $tmpentity->set('id', $entity->id());

#              $tmpentity->id() = $entity->id();
              
#              dpm(serialize($tmpentity), "sads");
#              dpm($tmpentity->id(), "id");
#              dpm($tmpentity->isNew(), "isnew");
// This is not needed anymore due to the changes in the query!
/*              
              if($is_file) {
                $storage = \Drupal::entityTypeManager()->getStorage('wisski_individual');
                
                foreach($data as $key => $dat) {
#                  dpm($data, "data");
                
#                  dpm($storage->getFileId(current($data)), "fid");
                  
                  $data[$key] = $storage->getFileId($dat);
                
                }
                                  
              }
*/
              $tmpentity->set($field_name, $data);

              $aux_entities[$field_id][$result_row->index] = $tmpentity;
            }
          }      
        }
      }
#      dpm($aux_entities, "aux");
      // Determine unique sets of fields that can be processed by the same
      // display. Fields that appear several times in the View open additional
      // "overflow" displays.
      $display_sets = [];
      foreach ($field_ids as $field_id) {
        $field = $this->view->field[$field_id];
        $field_name = $field->definition['field_name'];
        $index = 0;
        while (isset($display_sets[$index]['field_names'][$field_name])) {
          $index++;
        }
        $display_sets[$index]['field_names'][$field_name] = $field;
        $display_sets[$index]['field_ids'][$field_id] = $field;
      }

      $aux_data = [];
      // For each set of fields, build the output by bundle.
      foreach ($display_sets as $index => $display_fields) {
        
        // first generate the aux entities
        // these are used for the fields that are not
        // directly attached to the requested entities
        // that are used as key visuals  
        foreach($aux_entities as $field_id => $tmp_entities) {         
          
          $field = $this->view->field[$field_id];
          $field_name = $field->definition['field_name'];
#          dpm($field_name, "fn");
          $bundles = $field_storage_definitions[$field_name]->getBundles();
          
          $bundle_id = current($bundles);                     
          
#          dpm($bundle_id, "bun");
#          dpm($entity_type_id, "eit");
          
          // Create the display, and configure the field display options.
          $display = EntityViewDisplay::create([
            'targetEntityType' => $entity_type_id,
            'bundle' => $bundle_id,
            'status' => TRUE,
          ]);

          $display->setComponent($field->definition['field_name'], [
            'type' => $field->options['type'],
            'settings' => $field->options['settings'],
          ]);
#          dpm($field->options['type'], "type");
#          dpm($field->options['settings'], "set");
#          dpm($tmp_entities, "tmp");
          // generate the field-thingies
          $auxdata = $display->buildMultiple($tmp_entities);

#          dpm($auxdata, "aux");

          // and copy them to the values array.
          foreach($auxdata as $key => $val) {
            $build[$key][$field_id] = $val[$field_name];
          }
          
#          dpm($build, "build");
        }
        
        // this is copied from the original code
        foreach ($entities_by_bundles as $bundle => $bundle_entities) {
          // Create the display, and configure the field display options.
          $display = EntityViewDisplay::create([
            'targetEntityType' => $entity_type_id,
            'bundle' => $bundle,
            'status' => TRUE,
          ]);

          foreach ($display_fields['field_ids'] as $field) {
            $display->setComponent($field->definition['field_name'], [
              'type' => $field->options['type'],
              'settings' => $field->options['settings'],
            ]);
          }

          // Let the display build the render array for the entities.
          $display_build = $display->buildMultiple($bundle_entities);

          // Collect the field render arrays and index them using our internal
          // row indexes and field IDs.
          foreach ($display_build as $row_index => $entity_build) {

            foreach ($display_fields['field_ids'] as $field_id => $field) {
              // only add something if there isn't already something - otherwise 
              // we might overwrite it.
              if(empty($build[$row_index][$field_id]))
                $build[$row_index][$field_id] = !empty($entity_build[$field->definition['field_name']]) ? $entity_build[$field->definition['field_name']] : [];
            }
          }
        }
      }
    }
#    dpm($build, "build");
    return $build;
  }

}
