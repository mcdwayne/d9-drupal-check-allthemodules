<?php

namespace Drupal\taxonomy_facets;

class MenuTree {

  private $menuTree = array();
  private $vid = NULL;
  private $filtersObject = NULL;
  private $tidSelected = NULL;
  private $termSelected = null;
  private $menu_class = 'clearfix ';



  public function __construct($vid) {
    $this->vid = $vid;
    // Get fully loaded terms for all applied filters.
    $this->filtersObject = taxonomy_facets_get_selected_filters();
    // Get the term id of the filter that belongs to given vocabulary.
    if($this->filtersObject){
      $this->tidSelected = $this->filtersObject->getSelectedFilterForVocabulary($vid);
      $this->termSelected = $this->filtersObject->getSelectedFilterTermForVocabulary($vid);
    }
    $this->buildMenyTreeHeader();
    $this->buildMenuTreeRecursively(0);
  }

  public function getMenuTree(){
    return $this->menuTree;
  }

  private function buildMenuTreeRecursively($parent) {
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($this->vid, $parent, 1);

    $begin_ul = [
      '#theme' => 'taxonomy_facets_ul_wrapper_begin_template',
      '#menuClass' => $this->menu_class,
      '#attached' => [
        'library' => [
          'taxonomy_facets/change_links',
        ],
      ],
    ];
    // Only first time we call this function the menu class is cleclearfix, than we set it to null,
    // as sub <ul> css class should be only menu, not clearfix menu
    $this->menu_class = NULL;

    $this->menuTree[] = render($begin_ul);

    foreach ($tree as $leaf) {

      // @todo if($this->displayMenuItem($leaf)){
      if(true){

        $menuItemInChildrenLeafs = $this->menuItemInChildrenLeafs($leaf->tid);

        $leafCssClass = $this->calculateItemCssClass($menuItemInChildrenLeafs, $leaf->tid);
        $leaf = new MenuLeaf($leaf, $leafCssClass);

        $this->menuTree[] = [
          '#theme' => 'taxonomy_facets_menu_leaf_template',
          '#menuLeaf' => $leaf,
        ];

        if ($menuItemInChildrenLeafs || $this->menuItemIsFilterApplied($leaf->tid)){
          $this->buildMenuTreeRecursively($leaf->tid);
        }
      }
    }
    $end_ul =  [
      '#theme' => 'taxonomy_facets_ul_wrapper_end_template',
    ];
    $this->menuTree[] = render($end_ul);
  }

  private function calculateItemCssClass($menuItemInChildrenLeafs, $tid) {
    // Check if the current term in this loop has any children.
    $hasChildren = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($this->vid, $tid, 1);

    $return['leafClass'] = 'menu-item';

    if($hasChildren) {
      if ($menuItemInChildrenLeafs || $this->menuItemIsFilterApplied($tid)){
        $return['leafClass'] .= ' menu-item--expanded menu-item--active-trail';
      }
      else{
        $return['leafClass'] .= ' menu-item--collapsed';
      }
    }

    $return['leafAnchorClass'] = NULL;
    if($this->menuItemIsFilterApplied($tid)) {
      $return['leafAnchorClass'] = "is-active";
    }

    return $return;
  }

  private function buildMenyTreeHeader(){
    if ($this->termSelected) {
      $termObj = (object) ['name' => $this->termSelected->getName()];
      $termObj->tid = $this->termSelected->id();
      $termObj->vid = $this->termSelected->getVocabularyId();
      $headItem = new MenuLeaf($termObj);
      // remove current filter from the url
      $headItem->linkUrl = str_replace('/' . $headItem->urlAlias,"", $headItem->linkUrl);

      $this->menuTree[] = [
        '#theme' => 'taxonomy_facets_remove_filter_template',
        '#menuHed' => $headItem,
      ];
    }
  }


  /**
   * When building menu tree we check if we want to display a menu item
   * depending on various user preferences.
   * @param $term
   * @param $children
   * @param $terms
   * @param $tid_selected
   * @return bool
   *  true if to display, false if not to display
   */
  private function displayMenuItem($term) {
    $display_item = TRUE;
    $filter_applied = FALSE;

    $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($this->vid, $term->tid);


    // Get user preferences.
    $do_not_display_if_empty = variable_get('taxonomy_facets_display_link_if_empty', FALSE);
    $do_not_display_if_intersection_empty = variable_get('taxonomy_facets_display_link_if_intersection_empty', FALSE);

    // If user preference is to NOT display link if empty, i.e no nodes underneath.
    if ($do_not_display_if_empty) {
      // check if it has nodes underneath
      $has_nodes = taxonomy_facets_get_subnodes($this->vid, $term->tid);
      if (!$has_nodes) {
        // if no nodes do not display
        $display_item = FALSE;
      }
    }

    // User preference is to NOT display link if selection of filters have
    // no nodes underneath.
    if ($do_not_display_if_intersection_empty) {

      // Check if this item is already used as filter applied, if yes we display
      // item anyhow.

      if ($this->tidSelected == $term->tid) {
        $filter_applied = TRUE;
      }

      // Do this check only if item is last leaf
      // and if no filter applied.
      $applied_filters = $this->filtersObject->getAppliedFilters();
      if ($applied_filters && empty($children) && (!$filter_applied)) {
        // Remove filter from this vocabulary, if any.
        $new_terms_arr = array();
        foreach ($applied_filters as $t) {
          if ($this->vid != $t->vid) {
            $new_terms_arr[] = $t;
          }
        }

        // Add current item to filters.
        $curr_term = new \stdClass;
        $curr_term->tid = $term->tid;
        $new_terms_arr[] = $curr_term;

        $nodes = taxonomy_facets_get_nodes_based_on_intersect_of_terms($new_terms_arr);
        if (empty($nodes)) {
          $display_item = FALSE;
        }
      }
    }
    return $display_item;
  }

  /**
   * Check if selected filer is somewhere in the children leafs.
   */
  private function menuItemInChildrenLeafs($tid){

    $all_children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($this->vid, $tid);

    foreach ($all_children as $child) {
      if ($this->tidSelected == $child->tid) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private function menuItemIsFilterApplied($tid) {

    if ($this->tidSelected === $tid) {
      return TRUE;
    }
    return FALSE;
  }
}