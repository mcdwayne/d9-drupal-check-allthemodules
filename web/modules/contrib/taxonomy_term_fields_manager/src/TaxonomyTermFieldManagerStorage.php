<?php

namespace Drupal\taxonomy_term_fields_manager;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Build custom field from.
 */
class TaxonomyTermFieldManagerStorage {

  static function get_custom_fields_form(&$form, $vocabulary) {
    $taxonomy_vocabulary =  $vocabulary->id();
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $taxonomy_vocabulary);
    $tids_data = $query->execute();

    $entity_type_id = 'taxonomy_term';
    $bundle = $taxonomy_vocabulary;
    foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        $bundleFields[$field_name]['label'] = $field_definition->getLabel();
      }
    }
    if (!empty($bundleFields)) {
      foreach ($bundleFields as $key => $value) {
        $db_selected_field = $taxonomy_vocabulary . '_' . $key . '_cstm_taxonomy';
        $tax_field_name = \Drupal::state()->get($db_selected_field);
        $table_name = 'taxonomy_term__' . $tax_field_name;

        $i= 0;
        foreach ($tids_data as $key => $values) {
          $tid =  $key;
          $tid_key = 'tid:' . $key . ':0';

          if (db_table_exists($table_name)) {
            $query = \Drupal::database()->select($table_name, 'td');
            $query->fields('td');
            $query->condition('entity_id', $tid);
            $db_field_data = $query->execute()->fetchAll();

            foreach ($db_field_data as $key1 => $value1) {
              $db_tax_field_name = $tax_field_name . '_value';
              $values = $value1->$db_tax_field_name;

              $form['terms'][$tid_key][$tax_field_name] = array(
                '#type' => 'value',
                '#value' => $values,
              );
            }
          }
          else {
            \Drupal::state()->delete($taxonomy_vocabulary . '_' . $key . '_cstm_taxonomy');
          }
          $i++;
        }
        $form['#selected_field'][] = $tax_field_name;
      }
    }
    return $form;
  }
}
