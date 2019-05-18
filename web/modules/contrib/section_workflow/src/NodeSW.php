<?php

/**
 * @file
 * Contains NodeSW.php..
 */

namespace Drupal\section_workflow;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Class SectionConfig.
 *
 * @package Drupal\section_workflow
 */
class NodeSW extends SectionsConfig {

  /**
   * The Root Node ID of the section.
   *
   * @var int
   */
  public $rootEID = NULL;

  /**
   * The Parent Node ID of the given node.
   *
   * @var int
   */
  public $parentNID = NULL;

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Loads the section config for the context.  The context is determined by URL
   * form_state and node entity (extracted from form_state).  If it is
   * determined that we are within a section then load the specific config
   * that relates to this node and take action accordingly.
   *
   * @param $form_state
   *   A keyed array containing the current state of the form.
   */
  public function formAction(\Drupal\Core\Form\FormStateInterface &$form_state = NULL) {
    // Load parameters from URL.
    $query_pars = \Drupal::request()->query->all();
    $path_args = explode('/', \Drupal::service('path.current')->getPath());
    $this->contentSection = NULL;
    // If there is a parent id available it is because we are going to be inserting
    // a node.
    $parent_nid = NULL;
    $insert_content_type = NULL;
    $insert_context_key = NULL;
    if (!empty($query_pars) && isset($query_pars['parent_nid']) && isset($path_args[3])) {
      $parent_nid = isset($query_pars['parent_nid']) ? $query_pars['parent_nid'] : NULL;
      $insert_context_key = isset($query_pars['key']) ? $query_pars['key'] : NULL;
    }

    // Load Node from form object if available.
    $node = NULL;
    if (!is_null($form_state)) {
      $node = $form_state->getFormObject()->getEntity();
      $node_id = $node->id();
    }
    // If we have a parent id then load that from node if the form does not have
    // a node object for us.
    elseif (!is_null($parent_nid) && $parent_nid !== 'add') {
      $node = \Drupal\node\Entity\Node::load($parent_nid);
      $node_id = $node->id();
    }

    // Handle the inserting of a new node into a section to begin a new section.
    if (isset($query_pars['config_key']) && $node->isNew()) {
        // We are starting a new section then we are at root level.
      $this->updateMode = self::CONTEXT_NEW_SECTION;
      // Because we are starting a new section then regardless we are going to
      // say that we are in a section.
      $this->inSection = TRUE;
      $this->loadSectionConfigByKey($query_pars['config_key']);
      $this->loadConfigContextByEID($this->sectionConfig, 0);
    }
    // Handle a submission of a new section node.
    elseif (isset($query_pars['config_key']) && !is_null($node_id)) {
      // We are submitting a new node that is the beginning of a new section.
      $this->updateMode = self::CONTEXT_SUBMIT_NEW_SECTION;
      // If we are trying to save a new section then should have an entity id for it.
      // that will be the root since it is a new section.
      $this->inSection = TRUE;
      $this->rootEID = $node_id;
      $this->loadSectionConfigByKey($query_pars['config_key']);
      $this->loadConfigContextByEID($this->sectionConfig, 0);
      $this->saveEIDToConfig($node_id);
      $this->insertSave($node, $form_state);

    }
    // Handle the inserting of a new node into a section.
    elseif (isset($node) && is_null($node_id) && $node->isNew() && !is_null($parent_nid)) {
      // We are inserting a new node within an existing section.
      $this->updateMode = self::CONTEXT_INSERT;
      $this->inSection = TRUE;
      // Get the Entity id of the top most entity of this section.
      $root_nid = $this->loadAncestry($parent_nid);
      $this->loadSectionConfigByRootId($root_nid);
      $this->loadConfigContextByEID($this->sectionConfig, $parent_nid);
      $this->loadNextLevelContextByKey($this->contentSection, $insert_context_key);
    }
    // Handle a submission of a new node into a section.
    elseif (isset($node) && !is_null($node_id) && !$node->isNew()  && !is_null($parent_nid)) {
      // We are submitting a new node within an existing section.
      $this->updateMode = self::CONTEXT_SUBMIT;
      $this->inSection = TRUE;
      $root_nid = $this->loadAncestry($node_id);
      $this->loadSectionConfigByRootId($root_nid);
      $this->loadConfigContextByEID($this->sectionConfig, $parent_nid);
      $this->loadNextLevelContextByKey($this->contentSection, $insert_context_key);
      $this->saveEIDToConfig($node_id);
      $this->insertSave($node, $form_state, $parent_nid);
    }
    // Editing a node of a section.
    elseif (isset($node) && !is_null($node_id) && !$node->isNew()) {
      $this->updateMode = self::CONTEXT_EDIT;
      // Get the Entity id of the top most entity of this section.
      $root_nid = $this->loadAncestry($node_id);
      $this->loadSectionConfigByRootId($root_nid);
      $this->loadConfigContextByEID($this->sectionConfig, $node_id);

    }
    $this->parentNID = $parent_nid;
//    dpm( '--node_id:' . $node_id . '--parentNID:' . $this->parentNID . '--root_nid:' . $root_nid  . '::insertCT:'.  $insert_content_type .'::updateMode:'  . $this->updateMode. '|  section_type:' . $this->contentSection['section_type'] . '|  context setion:' . $this->contentSection['context_key']);
//    dpm($this->contentSection);

  }

  /**
   * Check if a node is inside a section.
   *
   * @param int $node_id
   *   Node id.
   *
   * @return bool inSection
   *   Flag to indicate if we are in a section or not.
   */
  public function getIfNIDInSection($node_id = NULL) {
    // Get the Entity id of the top most entity of this section.
    $root_nid = $this->loadAncestry($node_id);
    $this->loadSectionConfigByRootId($root_nid);
    if (!empty($this->sectionConfig)) {
      $this->loadConfigContextByEID($this->sectionConfig, $node_id);
    }
    return $this->inSection;
  }

  /**
   * Get the taxonomy term id for the section workflow field for a given node id.
   *
   * @param $nid
   *   The id of the node to be loaded.
   *
   * @return int
   *   Term id of the the section workflow field.
   */
  private function loadParentLandingTID($nid = NULL) {
    if(!is_null($nid) && $nid !== 'add') {
      $parent_node = \Drupal\node\Entity\Node::load(intval($nid));
      if (isset($parent_node->get(self::SECTION_FIELD_NAME)->target_id)) {
        return $parent_node->get(self::SECTION_FIELD_NAME)->target_id;
      }
    }
    return NULL;
  }

  /**
   * Save content relating to section workflow (taxonomy and menu) acccording
   * to the section type.
   *
   * @param $node
   *   The node that is being saved during a submit process.
   * @param $form_state
   *   The form_state that is being saved during a submit process.
   * @param $parent_nid
   *   The node id of the parent node (as per node hierarchy).
   */
  private function insertSave(\Drupal\node\Entity\Node $node, \Drupal\Core\Form\FormStateInterface &$form_state, $parent_nid = NULL) {
    switch ($this->contentSection['section_type']) {
      case self::SECTION_TYPE_LANDING:
      case self::SECTION_TYPE_ROOT_LANDING:
        // Add to menu as per config for this section.
        $this->AddToMenu($node, $form_state, $parent_nid);
        // Create and add to Section workflow term.
        $parent_landing_tid = $this->loadParentLandingTID($parent_nid);
        $term_id = $this->AddSectionTerm($node->getTitle(), $parent_landing_tid);
        $this->SaveSectionTerm($node, $term_id);
        break;

      case self::SECTION_TYPE_PAGE:
        // Add to menu as per config for this section.
        $this->AddToMenu($node, $form_state, $parent_nid);
        // Create and add to Section workflow term.
        $parent_landing_tid = $this->loadParentLandingTID($parent_nid);
        $this->SaveSectionTerm($node, $parent_landing_tid);
        break;

      case self::SECTION_TYPE_LISTING:
        // Create and add to Section workflow term.
        $parent_landing_tid = $this->loadParentLandingTID($parent_nid);
        $this->SaveSectionTerm($node, $parent_landing_tid);
        break;

      case self::SECTION_TYPE_REFERENCE:
        // Create and add to Section workflow term.
        $this->AddEntityReference($node, $form_state);
        break;

      default:
    }
    // Clear cache since we will have updated the config.
    \Drupal::cache()->delete('cache_section_workflow_config_all');
  }

  /**
   * Save an entity reference on node id to a node.
   *
   * @param $node
   *   The node that is being saved during a submit process.
   * @param $form_state
   *   The form_state that is being saved during a submit process.
   * @param $add_node_nid
   *   The node id that is to be saved to the entity reference field.
   */
  private function AddEntityReference(\Drupal\node\Entity\Node $node, \Drupal\Core\Form\FormStateInterface &$form_state, $add_node_nid = NULL) {
    $add_node = \Drupal\node\Entity\Node::load(intval($add_node_nid));
    if ($add_node->hasField($this->contentSection['parent_section_type_field'])) {
      $add_node->get($this->contentSection['parent_section_type_field'])->appendItem(['target_id' => $node->id()]);
      $add_node->save();
    }
    else {
      // @TODO report error message.
    }
  }

  /**
   * Save a section term item for a node according to it's section type.
   *
   * @param $node
   *   The node that is being saved during a submit process.
   * @param $term_tid
   *   The form_state that is being saved during a submit process.
   */
  private function SaveSectionTerm(\Drupal\node\Entity\Node $node, $term_tid = NULL) {
    $node->get(self::SECTION_FIELD_NAME)->appendItem(['target_id' => $term_tid]);
    $node->save();
  }

  /**
   * Create a menu link as per config and link it to the node.
   *
   * @param $node
   *   The node that is being saved during a submit process.
   * @param $form_state
   *   The form_state that is being saved during a submit process.
   * @param $parent_nid
   *   Id of the parent node (as per menu hierarchy) so that we can save this
   *   node below it.
   */
  private function AddToMenu(\Drupal\node\Entity\Node $node, \Drupal\Core\Form\FormStateInterface &$form_state, $parent_nid = NULL) {
    // We do not create menu links for content that is of Listing section type.
    if (!is_null($node->id())
      && $this->contentSection['section_type'] !== self::SECTION_TYPE_LISTING) {
#     // Create the menu link object and populate.
      $menu_entity = MenuLinkContent::create(array(
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'langcode' => $node->language()->getId(),
      ));
      $menu_entity->title->value = $node->getTitle();
      $menu_entity->menu_name->value = $this->sectionConfig['menu'];
      if (isset($this->contentSection['enable_menu']) && $this->contentSection['enable_menu'] == FALSE) {
        $menu_entity->enabled->value = FALSE;
      }
      else {
        $menu_entity->enabled->value = TRUE;
      }
      $parent_mid = NULL;

      // Use the parent nid to maintain the hierarchy.
      if (intval($parent_nid > 0)) {
        $parent_node = \Drupal\node\Entity\Node::load(intval($parent_nid));
        $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
        $links = $menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $parent_node->id()));
        $link = reset($links);
        $parent_mid = $link->getPluginId();
      }
      if (!is_null($parent_mid)) $menu_entity->parent->value = $parent_mid;

      // Get default language config as per node.
      if ($menu_entity->isTranslatable()) {
        if (!$menu_entity->hasTranslation($node->language()->getId())) {
          $menu_entity = $menu_entity->addTranslation($node->language()
            ->getId(), $menu_entity->toArray());
        }
        else {
          $menu_entity = $menu_entity->getTranslation($node->language()->getId());
        }
      }

      // Save menu link.
      $menu_entity->save();

      // Leave this for _menu_ui_node_save() to pick up so we don't end up with
      // duplicate menu-links.
      $form_state->setValue(['menu', 'entity_id'], $menu_entity->id());
    }
  }

  /**
   * Delete a node id from the section config.
   *
   * @param $node
   *   The node that is being deleted.
   */
  public function section_workflow_node_delete($node) {

    $tid = $node->get(self::SECTION_FIELD_NAME)->target_id;
    $node_id = $node->id();
    $this->loadSectionConfigByVid(NULL, $tid);
    $this->loadConfigContextByEID($this->sectionConfig, $node_id);
    $this->updateMode = self::CONTEXT_DELETE_NODE;

    // Load corresponding config.
    $key_path = $this->contentSection['key_path'];
    $config_factory = \Drupal::configFactory();
    $settings = $config_factory->getEditable('section_workflow.sections.' . $this->sectionConfig['key']);

    // Get the entity ids for the path that corresponds to this node.
    $entity_ids = $settings->get($this->contentSection['key_path'] . '.eids');

    // Delete the node id from the array.
    $key = array_search($node_id, $entity_ids);
    if($key!==false){
      unset($entity_ids[$key]);
    }
    if (count($entity_ids) == 0) {
      $entity_ids = NULL;
    }

    // Save config.
    $settings->set($key_path . '.eids', $entity_ids);
    $settings->save();

    // Clear cache since we will have updated the config.
    \Drupal::cache()->delete('cache_section_workflow_config_all');
  }

  /**
   * Load data required by the section node admin page.
   *
   * @return array
   *   An array of content types with corresponding section config.
   */
  public function section_workflow_context_admin() {
    $admin_config = array();
    $menu_links = array();
    $section_landings = array();
    $section_parent = array();
    $parent = array();
    $listings = array();

    // Load parameters from URL.
    $path_args = explode('/', \Drupal::service('path.current')->getPath());
    if (isset($path_args[3]) && $path_args[3] == 'admin-section-node') {
      $this->updateMode = self::CONTEXT_ADMIN;
      $node_id = isset($path_args[2]) ? $path_args[2] : NULL;

      // Get node title for context.
      $node_title = db_query("SELECT title FROM {node_field_data} WHERE nid = :nid", array(
        ':nid' => $node_id,
      ))->fetchField();

      $this->rootEID = $this->loadAncestry($node_id);

      // Load config and ensure we are in a section.
      if ($this->loadSectionConfigByRootId($this->rootEID) == TRUE) {
        $this->loadConfigContextByEID($this->sectionConfig, $node_id);
        $this->inSection = TRUE;

        // Get top level variables.
        $root_node = entity_load('node', $this->rootEID);
        $root_sw_tid = $root_node->get(self::SECTION_FIELD_NAME)->target_id;
        $admin_config['root']['nid'] = $this->rootEID;
        $admin_config['root']['tid'] = $root_sw_tid;
        $admin_config['root']['title'] = $root_node->getTitle();
        $admin_config['root']['content_type'] = $this->sectionConfig['content_type'];

        // Get Menu and landing page ancestry starting point
        $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
        $menu_link = $menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $node_id));
        $node_menu_plugin_id = key($menu_link);

        // All nodes except section home page.
        if ($node_id !== $this->rootEID) {
          // Proceed if this node has at least one menu item.
          if (is_array($menu_link) && count($menu_link)) {
            $menu_link = reset($menu_link);
            $ancestors = $menu_link_manager->getParentIds($menu_link->getParent());

            // Ascending order is preferred.
            $ancestors = array_reverse($ancestors);

            // Use this to load the parent.  The last ancestor is naturally it.
            $last_ancestor_id = end($ancestors);

            foreach ($ancestors as $ancestor_id) {
              $ancestor = $menu_link_manager->createInstance($ancestor_id);
              // Store all ancestors.
              $nid = $ancestor->getRouteParameters()['node'];
              $title = $ancestor->getTitle();
              $menu_links[$nid]['nid'] = $nid;
              $menu_links[$nid]['title'] = $title;
              $menu_links[$nid]['parent'] = $ancestor->getParent();
              $menu_links[$nid]['plugin_id'] = $ancestor_id;

              // Load the portion of the config relating for this context (node).
              $this->loadConfigContextByEID($this->sectionConfig, $nid);
              $section_content = $this->contentSection;

              // All section landing pages
              if ($section_content['section_type'] == self::SECTION_TYPE_ROOT_LANDING) {
                $section_landings[$nid]['nid'] = $nid;
                $section_landings[$nid]['title'] = $title;
                $section_landings[$nid]['content_type'] = $section_content['content_type'];
              }

              // The last section landing page in the loop is the parent landing
              // page, so we overwrite this portion of the array until no more.
              if ($section_content['section_type'] == self::SECTION_TYPE_ROOT_LANDING) {
                $section_parent['nid'] = $nid;
                $section_parent['title'] = $title;
                $section_parent['content_type'] = $section_content['content_type'];
              }

              // Get parent.
              if ($last_ancestor_id == $ancestor_id) {
                $parent['nid'] = $nid;
                $parent['title'] = $title;
                $parent['parent']['content_type'] = $section_content['content_type'];
                $parent['section_type'] = $section_content['section_type'];
              }
            }
          }
        }

        // Children content from the menu.
        $menu_tree = \Drupal::menuTree();
        $menu_name = $this->sectionConfig['menu'];
        $link_id = $node_menu_plugin_id;
        $parameters = new MenuTreeParameters();
        $parameters->setRoot($link_id)->setTopLevelOnly()->setMinDepth(1)->setMaxDepth(3);
        // Load the tree based on this set of parameters.
        $tree = $menu_tree->load($menu_name, $parameters);
        // Transform the tree using the manipulators you want.
        $manipulators = array(
          // Only show links that are accessible for the current user.
          array('callable' => 'menu.default_tree_manipulators:checkAccess'),
          // Use the default sorting of menu links.
          array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
        );
        $tree = $menu_tree->transform($tree, $manipulators);
        // Finally, build a renderable array from the transformed tree.
        $menu = $menu_tree->build($tree);
        $children = array();
        $this->GetNodeSettingsFromMenuTree($menu['#items'], $children);

        // Listings content
        $listing_types = $this->GetContentTypesFromConfig($this->sectionConfig, $listing_types, self::SECTION_TYPE_LISTING);
        if (count($listing_types) > 0) {
          $listing_types = array_unique(array_keys($listing_types));
          $node = entity_load('node', $node_id);
          $node_sw_tid = $node->get(self::SECTION_FIELD_NAME)->target_id;
          $query = \Drupal::database()->select('node__section_workflow', 'nsf');
          $query->addField('nsf', 'entity_id');
          $query->condition('nsf.section_workflow_target_id', $node_sw_tid);
          $query->condition('nsf.bundle', $listing_types, 'IN');
          $nids = $query->execute()->fetchAllKeyed();
          $nids = array_keys($nids);
          $listing_nodes = entity_load_multiple('node', $nids);
          foreach ($listing_nodes as $listing) {
            $nid = $listing->id();
            $listings[$nid]['nid'] = $nid;
            $listings[$nid]['title'] = $listing->getTitle();
            $listings[$nid]['content_type'] = $listing->getType();
          }
        }
      }
    }

    // Load admin config array.
    $admin_config['settings']['url_admin'] = self::URL_ADMIN;
    $admin_config['context']['nid'] = $node_id;
    $admin_config['context']['title'] = $node_title;
    $admin_config['menu_links'] = $menu_links;
    $admin_config['section_landings'] = $section_landings;
    $admin_config['section_parent'] = $section_parent;
    $admin_config['parent'] = $parent;
    $admin_config['children'] = $children;
    $admin_config['listings'] = $listings;

    return $admin_config;
  }

  /**
   * Get node settings from a menu tree.
   *
   * @param $menu_tree
   *   The menu tree items.
   * @param $populate_settings
   *   Node settings that are being populated.
   * @param $depth
   *   The current depth of the tree.
   */
  private function GetNodeSettingsFromMenuTree($menu_tree = NULL, &$populate_settings = array(), $depth = 0) {
    if (count($menu_tree) > 0) {
      foreach ($menu_tree as $level) {
        $nid = $level['url']->getRouteParameters();
        $nid = isset($nid['node']) ? $nid['node'] : NULL;
        if (isset($level['url']) && !is_null($nid)) {
          $populate_settings[$nid]['nid'] = $nid;
          $populate_settings[$nid]['title'] = $level['title'];
          $populate_settings[$nid]['depth'] = $depth;
        }
        // Look for sub menu items.
        if (isset($level['below']) && count(isset($level['below']) > 0)) {
          // Update the depth if we go deeper.
          $depth++;
          $this->GetNodeSettingsFromMenuTree($level['below'], $populate_settings, $depth);
        }
        // Update the depth after an iteration.
        $depth--;
      }
    }
  }
}