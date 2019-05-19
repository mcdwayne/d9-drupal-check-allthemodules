<?php

namespace Drupal\taxonomy_facets;

class MenuLeaf {

  private $vid = NULL;
  public $tid = NULL;
  public $termName = NULL;
  public $urlAlias = NULL;
  public $leafClass = NULL;
  public $linkUrl = NULL;
  public $leafAnchorClass = NULL;
  private $filtersObject = NULL;

  public function __construct($leaf_term_object, $leafClass = array()) {

    // Get fully loaded terms for all applied filters.
    $this->filtersObject = taxonomy_facets_get_selected_filters();
    $this->termName = $leaf_term_object->name;
    $this->tid = $leaf_term_object->tid;
    $this->vid = $leaf_term_object->vid;
    if($leafClass){
      $this->leafAnchorClass = $leafClass['leafAnchorClass'];
      $this->leafClass = $leafClass['leafClass'];
    }
    $this->getTermUrlAlias();
    $this->buildLinkUrl();
  }

  /**
   * Get taxonomy term url alias from the term object.
   *
   * @param object
   *   The term object
   *
   * @return string
   *   Return url alias
   */
  private function getTermUrlAlias() {
    $query = db_select('url_alias', 'u')
      ->fields('u', array('alias'))
      ->condition('source', '/taxonomy/term/' . $this->tid)
      ->execute();
    if($aliases = $query->fetchAll()) {
      $aliases = current($aliases)->alias;
      $this->urlAlias = ltrim($aliases,"/");
    }
    else {
      drupal_set_message(t('You do not have any url aliases generate for taxonomy terms, see Taxonomy Facets module readme file.'),'warning');
    }

  }


  private function buildLinkUrl() {

    $url = [];

    $noTermFromCurrentVocabularyFound = TRUE;

    $filters = null;
    if($this->filtersObject) {
      $filters = $this->filtersObject->getAppliedFilters();
    }

    if ($filters
    ) {
      // Loop trough applied filters.
      foreach ($filters as $filter) {
        // if filter is from current vocabulary than apply this leaf url alias
        // instead of already applied filter
        if ($filter->getVocabularyId() == $this->vid) {
          $obj = new \stdClass;
          $obj->vid = $this->vid;
          $obj->url = $this->urlAlias;
          $url[] = $obj;
          $noTermFromCurrentVocabularyFound = FALSE;
        }
        // put in url alias of the applied filter.
        else {
          $obj = new \stdClass;
          $obj->vid = $filter->getVocabularyId();
          $obj->url = ltrim($filter->url(), "/");
          $url[] = $obj;
        }
      }
    }

    // If filters from this vocabulary were not in the applied filters than
    // also apply the alias from the current leaf
    if ($noTermFromCurrentVocabularyFound == TRUE) {
      $obj = new \stdClass;
      $obj->vid = $this->vid;
      $obj->url = $this->urlAlias;
      $url[] = $obj;
    }
    // @TODO replace 'listings' hard coded string with user configurable variable.
    $this->linkUrl = $this->getLanguagePrefix() . '/listings';

    // Now order url aliases (filters) by vocabulary id so that we preserve
    // order, so we don't end up with duplicate pages for same filter
    // combinations.

    usort($url, function($a, $b) {
      if( $a->vid ==  $b->vid ){ return 0 ; }
      return ($a->vid < $b->vid) ? -1 : 1;
    });

    foreach ($url as $u) {
      $this->linkUrl .= '/' . $u->url;
    }
  }


  function getLanguagePrefix() {
    if($prefixes = \Drupal::config('language.negotiation')->get('url.prefixes')) {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if($prefixes[$language]){
        return "/" . $prefixes[$language];
      }
    }
    // @TODO add case when using different domains for language negotiation.
    // kint($config->get('url.domains'));
    return null;
  }
}
