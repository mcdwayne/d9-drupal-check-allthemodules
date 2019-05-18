<?php

namespace Drupal\pagetree\Service;

use Drupal\node\Entity\Node;
use Drupal\system\Entity\Menu;

/**
 * Provides a resource to get the page tree.
 */
class Tree
{
    protected $_contentTypes = [];
    protected $_languagesUsed = [];
    protected $_nids = [];
    protected $_permissions = [];

    public function __construct()
    {
        $this->_contentTypes = \Drupal::configFactory()->get('pagetree.settings')->get('contentTypes');
        $languagesSelected = \Drupal::configFactory()->get('pagetree.settings')->get('languages');
        $this->_languagesUsed = [];
        $translations = array();
        $languages = \Drupal::service('language_manager')->getLanguages();
        $this->_languagesUsed = [];
        if ($languagesSelected != null && count($languagesSelected) > 0) {
            foreach ($languages as $language) {
                if (in_array($language->getId(), $languagesSelected)) {
                    $this->_languagesUsed[] = $language;
                }
            }
        } else {
            $this->_languagesUsed = $languages;
        }
    }

    /*
     * Request the current page tree.
     *
     * @return \Drupal\rest\ResourceResponse The response containing the page tree.
     *
     */
    public function get()
    {
        // Get nids in menu
        $menus = \Drupal::configFactory()->get('pagetree.settings')->get('menus');
        $menuHelper = \Drupal::service('pagetree.menu_helper');
        $nids = [];
        foreach ($menus as $menuId) {
            $menu = Menu::load($menuId);
            $tree = $menuHelper::getMenuTree($menu->id());
            $this->collectNids($tree, $nids);
        }
        if (count($nids) == 0) {
            return [];
        }
        // Get node and revision information
        $nodes = $this->getNodes($nids);
        $revisions = $this->getLatestRevisions($nids);
        $handlers = \Drupal::service('plugin.manager.pagetree_state_handler')->getHandlers();
        foreach ($handlers as $handler) {
            $handler->annotate($revisions);
        }
        $entityInfos = [];
        $this->processNodes($nodes, $revisions, $entityInfos);

        // Generate tree for frontend
        $trees = [];
        foreach ($menus as $menuId) {
            $menu = Menu::load($menuId);
            // Get menu tree
            $tree = $menuHelper::getMenuTree($menu->id());
            // Combine the info
            $arrayTree = $this->buildArrayTree($tree, $entityInfos);
            $trees[] = ['label' => $menu->label(), 'id' => $menu->id(), 'tree' => $arrayTree];
        }
        $data = ['hash' => md5(serialize($trees)), 'trees' => $trees];
        // Prevent caching of response
        return $data;
    }

    protected function processNodes(&$nodes, &$revisions, &$entityInfos)
    {

        foreach ($this->_languagesUsed as &$language) {
            if (!empty($nodes[$language->getId()])) {
                foreach ($nodes[$language->getId()] as $node) {
                    if (empty($entityInfos[$node['id']])) {
                        $entityInfos[$node['id']] = ['translations' => [], 'permissions' => $this->getPermissions($node['type'])];
                    }

                    if (!empty($revisions[$node['id'] . $language->getId()])) {
                        $revision = $revisions[$node['id'] . $language->getId()];
                        $status = 0;
                        $translation = [];
                        if ($node['status'] == 1) {
                            if ($revision['status'] == 1) {
                                $status = 1;
                            } else {
                                $status = 2;
                            }
                        }
                        $externalLink = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node['id'], $language->getId());
                        $settingsLink = '/node/' . $node['id'] . '/edit';
                        if (\Drupal::languageManager()->isMultilingual()) {
                            $externalLink = '/' . $language->getId() . $externalLink;
                            $settingsLink = '/' . $language->getId() . $settingsLink;
                        }

                        $translation['name'] = $revision['title'];
                        $translation['status'] = $status;
                        $translation['externalLink'] = $externalLink;
                        $translation['settingsLink'] = $settingsLink;

                        $entityInfos[$node['id']]['translations'][$language->getId()] = $translation;
                    } else {
                        $entityInfos[$node['id']]['translations'][$language->getId()] = null;
                    }
                }
            }
        }
        foreach ($entityInfos as $nodeId => $node) {
            foreach ($this->_languagesUsed as &$language) {
                if (empty($entityInfos[$nodeId]['translations'][$language->getId()])) {
                    $entityInfos[$nodeId]['translations'][$language->getId()] = null;
                }
            }
        }
    }

    public function getNodes($nids)
    {
        $nodes = [];
        foreach ($this->_languagesUsed as &$language) {
            $statement = "SELECT nid, vid, langcode, status, type FROM node_field_data WHERE `nid` IN (:nids[])";
            $data = [
                ':nids[]' => $nids,
            ];
            if (count($this->_contentTypes) > 0) {
                $statement .= "AND `type` IN (:types[])";
                $data[':types[]'] = array_values($this->_contentTypes);
            }
            $stmt = \Drupal::database()->query($statement, $data);
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($results as $row) {
                $nodes[$row->langcode][] = ['id' => $row->nid, 'status' => $row->status, 'langcode' => $row->langcode, 'vid' => $row->vid, 'type' => $row->type];
            }
        }
        return $nodes;
    }

    public function getLatestRevisions($ids)
    {
        $revisions = [];
        foreach ($this->_languagesUsed as &$language) {
            $stmt = \Drupal::database()->query(
                "SELECT a.nid, a.langcode, a.status, a.vid, link.title FROM node_field_revision a JOIN (SELECT vid FROM (SELECT MAX(vid) as vid FROM node_field_revision WHERE `nid` IN (:nids[]) AND langcode LIKE :langcode AND revision_translation_affected = 1 GROUP BY changed, nid, langcode ORDER BY `changed` DESC) as sub) b on a.vid=b.vid AND a.langcode LIKE :langcode JOIN menu_link_content_data link ON link__uri = CONCAT('entity:node/', a.nid) AND link.langcode = :langcode",
                [
                    ':nids[]' => $ids,
                    ':langcode' => $language->getId(),
                ]
            );
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($results as $row) {
                if (empty($revisions[$row->nid . $row->langcode]) || $revisions[$row->nid . $row->langcode]['vid'] < $row->vid) {
                    $revisions[$row->nid . $row->langcode] = ['nid' => $row->nid, 'langcode' => $row->langcode, 'title' => $row->title, 'status' => $row->status, 'vid' => $row->vid];
                }
            }
        }
        return $revisions;
    }

    /**
     * Create a array tree representation of the menu based on the given menutree.
     * Annotate it with info from entityInfos.
     *
     * @param MenuTree $tree
     * @param array[] $entityInfos
     * @return void
     */
    public function buildArrayTree($tree, $entityInfos)
    {
        $return = array();
        foreach ($tree as $entry) {
            if (array_key_exists('node', $entry->link->getRouteParameters())) {
                $id = $entry->link->getRouteParameters()['node'];
                if (isset($entityInfos[$id])) {
                    $return[] = array(
                        'id' => $id,
                        'linkId' => str_replace('menu_link_content:', '', $entry->link->getPluginId()),
                        'translations' => $entityInfos[$id]['translations'],
                        'permissions' => $entityInfos[$id]['permissions'],
                        'children' => $this->buildArrayTree($entry->subtree, $entityInfos),
                    );
                }
            }
        }
        return $return;
    }

    public function collectNids($tree, &$nids = [])
    {
        foreach ($tree as $entry) {
            if (array_key_exists('node', $entry->link->getRouteParameters())) {
                $nids[] = $entry->link->getRouteParameters()['node'];
                $this->collectNids($entry->subtree, $nids);
            }
        }
        return $nids;
    }

    public function getPermissions($bundle)
    {
        if (isset($this->_permissions[$bundle])) {
            return $this->_permissions[$bundle];
        }

        $nids = \Drupal::entityQuery('node')->condition('type', $bundle)->range(0, 1)->execute();
        $entity = Node::load(reset($nids));

        $this->_permissions[$bundle]['create'] = $entity->access('create');
        $this->_permissions[$bundle]['update'] = $entity->access('update');
        $this->_permissions[$bundle]['delete'] = $entity->access('delete');

        return $this->_permissions[$bundle];
    }
}
