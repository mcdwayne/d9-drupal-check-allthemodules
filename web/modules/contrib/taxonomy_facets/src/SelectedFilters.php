<?php

/**
 * Get all selected filters from the url.
 *
 * Get the current url, taxonomy terms that are currently applied as
 * filter are in the url, this function examines the url and gets
 * all of the filters applied for the current page.
 *
 * @return array
 *   Array of filter arrays, each filter array has all the info about a filter:
 *   tid, path alias, term name, vid
 */

namespace Drupal\taxonomy_facets;


class SelectedFilters {

  // Terms, array of term objects
  protected $terms = array();

  function __construct($term_names = array()) {
    // Set filters
    foreach ($term_names as $term_name) {
      if ($tid = self::getTermIdFromUrlAlias($term_name)) {
        $this->terms[] = \Drupal\taxonomy\Entity\Term::load($tid);
      }
    }

    // Sort by vocabulary id so that we always get same order of filters, to avoid
    // duplicate urls for the same page.

    if($this->terms) {
      usort($this->terms, function($a, $b){
        if( (int) $a->id() == (int) $b->id() ) {
          return 0;
        }

        if ( (int) $a->id() < (int) $b->id() ) {
          return -1;
        }
        else{
          return 1;
        }
      });
    }
  }


  /**
   * Get term id.
   *
   * For a given taxonomy term name return the term id.
   * @todo deal with duplicate term names, i.e same name in 2 vocabularies.
   *
   * @return integer
   *   Return the term id. return null if no term with this name found.
   */
  static function getTermIdFromUrlAlias($term_alias) {

    $select = db_select('url_alias', 'u');

    // Select these specific fields for the output.
    $select->addField('u', 'source');

    // Filter only persons named "John".
    $select->condition('u.alias', '/' . $term_alias);

    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
    if(!empty($entries)) {
      $entries = explode('/', current(current($entries)));
      return $entries[3];
    }
    else{
      return NULL;
    }
  }
  
  public function getAppliedFilters() {
    return $this->terms;
  }
  
  public function getAppliedFilterNames() {
    //return $this->term_names;
  }

  public function getAppliedFilterTids() {
    $tids = array();
    foreach($this->terms as $term){
      $tids[] = $term->id();
    }
    return $tids;
  }

  // We store a fey filters in the object, each belongs to a different vocabulary.
  // Get the term id of the filter that belongs to a given vocabulary.
  public function getSelectedFilterForVocabulary($vid) {
    foreach ($this->terms as $term) {
       if($term->getVocabularyId() == $vid){
         return $term->id();
       }
    }
    return null;
  }

  public function getSelectedFilterTermForVocabulary($vid) {
    foreach ($this->terms as $term) {
      if($term->getVocabularyId() == $vid){
        return $term;
      }
    }
    return null;
  }
}


