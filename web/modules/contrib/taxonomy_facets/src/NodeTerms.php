<?php

namespace Drupal\taxonomy_facets;

class NodeTerms{
  // Node we just edited in node form and that is in the process of saving.
  private $node = null;
  // Node before editing begun.
  private $old_node = null;

  public function __construct($node) {

    // Load from this module settings and see which vocabularies need cascading.
    $config = \Drupal::config('taxonomy_facets.settings');
    $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    foreach($vocabularies as $vocabulary){
      // If setting is 1 deal with a vocabulary
      if($config->get($vocabulary->id())) {
        $this->node = $node;
        if($node->id()){
          $this->old_node = node_load($node->id());
        }
        $this->findFieldToCascadeTerms($vocabulary->id());
      }
    }

  }

  /**
   * Check node fields for Taxo Faceted fields.
   *
   * Check if this node have such a field, i.e taxonomy reference field
   * to a given vocabulary. If yes cascade terms.
   *
   * @param $vid
   *  Vocabulary id of a vocabulary against which we want to check for a
   *  field existence.
   */
  function findFieldToCascadeTerms($vid) {
    // Go trough all fields until you find a field referencing to this taxonomy.
    //kint($this->node->getFieldDefinitions());
    //exit();
    foreach($this->node->getFieldDefinitions() as $field_definition) {

      if (method_exists($field_definition, 'get')) {
        if ($field_definition->get('field_type') === 'entity_reference') {
          $settings = $field_definition->get('settings');
          if (isset($settings['handler_settings']['target_bundles']) && $vid === current($settings['handler_settings']['target_bundles'])){
            // Field found, now cascade terms.
            $cardinality = $field_definition->getFieldStorageDefinition()->get('cardinality');
            if ($cardinality === -1) {
              $this->cascadeTerms($field_definition->get('field_name'));
            }
            else {
              //drupal_set_message('warning', t("Taxonomy reference field:) " . ));
              drupal_set_message(
                t(
                  'The entity reference filed: @fieldName, that is a 
                  reference to Taxonomy: @account, has not been set as mutivalue
                  field. Please change definition of the field, set "Allowed number of values" to "unlimited".
                  Alternatively change the setting of the Taxonomy Faceted search, deselect "CASCADE TERMS" 
                  checkbox for this vocabulary in Administration >> Configuration >> Taxonomy Facets configuration',
                  [
                    '@fieldName' =>  $field_definition->get('field_name'),
                    '@account' => $vid,
                  ]
                ),'warning'
              );
            }
          }
        }
      }
    }
  }

  function cascadeTerms($filed_name) {
    // print_r($filed_name);
    $terms = $this->node->$filed_name->getValue();
    $parents = [];
    foreach ($terms as $term) {
      // print_r($term);
      $parents = array_merge($parents, $this::getTermParents($term['target_id']));
    }
    $all_parents = array_unique($parents);
    // getTermParents returns a term in question so If more than one, i.e if
    // there are parents than cascade
    if (count($all_parents) >= 2) {
      // Get term out if it was already in the node before editing,
      // we don't want to add it twice.
      $old_terms = [];
      if($this->old_node){
        $old_terms = $this->old_node->$filed_name->getValue();
      }
      $old_terms_array = [];
      foreach ($old_terms as $old_term){
        $old_terms_array[] = $old_term['target_id'];
      }
      $all_parents = array_diff($all_parents, $old_terms_array);

      // Convert to associative array.
      // $terms = [];
      foreach($all_parents as $per) {
       // $terms[] = ['target_id' => $per];
        $this->node->$filed_name[] = ['target_id' => $per];
      }
    }
  }

  static function getTermParents($tid) {
    $ancestors = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($tid);
    $list = [];
    foreach ($ancestors as $term) {
      $list[] = $term->id();
    }
    return $list;
  }
}
