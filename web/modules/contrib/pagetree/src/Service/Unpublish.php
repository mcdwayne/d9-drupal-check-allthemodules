<?php

namespace Drupal\pagetree\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\iq_block_module\Service\NodeCollector;
use Drupal\Node\entity\Node;
use Drupal\user\Entity\User;

class Unpublish
{
    /**
     * The language in which to publish the node.
     *
     * @var string
     */
    protected $_lang = null;

    /**
     * The message to add to.
     *
     * @var string
     */
    protected $_message = "unpublishing revision";

    /*
     * Publish a page and all rows, components and contents within.
     *
     * Returns a tree array of all unpublished node and revision ids.
     *
     * @return The unpublished node and revision ids.
     */
    public function unpublishPage($entity = null, $language = null, $message = null, $self = true)
    {
        $this->_lang = $language;
        if ($message != null && !empty($message)) {
            $this->_message = $message;
        }
        $revision = $this->getNode($entity, $this->_lang);
        $results = $this->_processChildren($revision);
        if ($self) {
            $results['unpublished'] = $this->unpublishNode($revision);
        }
        return $results;
    }

    /*
     * Publish a page and all rows, components and contents within.
     *
     * Returns a tree array of all unpublished node and revision ids.
     *
     * @return The unpublished node and revision ids.
     */
    protected function _processChildren($revision)
    {

        // Get structure for this node
        $structure = $revision->field_structure->value;

        $matches = array();
        // Search structure for id entries and publish the row / component
        preg_match_all(
            '/{{([0-9]+)}}/',
            $structure,
            $matches
        );

        $results = array($revision->id() => $revision->getRevisionId(), 'lang' => $this->_lang, 'children' => array(), 'unpublished' => null);
        foreach ($matches[1] as $group) {
            $results['children'][] = $this->unpublishRow($group);
        }

        Cache::invalidateTags(['block_view']);
        return $results;
    }

    /**
     * Publish a row node and its children.
     *
     * @param int $id The id of the node.
     * @return array The unpublished node and revision ids.
     */
    protected function unpublishRow($id)
    {
        $revision = $this->getNode($id, $this->_lang);
        if ($revision == null) {
            return $id . " not found";
        }
        if ($revision->getType() != "iqbm_row") {
            return $this->unpublishComponent($id);
        }

        $row = $revision->field_structure->value;

        $matches = array();
        // Search structure for id entries and publish the row / component
        preg_match_all(
            '/{{([0-9]+)}}/',
            $row,
            $matches
        );
        $results = array($id => $revision->getRevisionId(), 'lang' => $this->_lang, 'children' => array(), 'unpublished' => null);
        foreach ($matches[1] as $group) {
            $results['children'][] = $this->unpublishRow($group);
        }
        $results['unpublished'] = $this->unpublishNode($revision);
        return $results;
    }

    /**
     * Publish a component node and its children.
     *
     * @param int $id The id of the node.
     * @return array The unpublished node and revision ids.
     */
    protected function unpublishComponent($id)
    {
        $revision = $this->getNode($id, $this->_lang);
        if ($revision == null) {
            return $id . " not found";
        }

        $component = $revision->field_structure->value;

        $matches = array();
        // Search structure for id entries and publish the component
        preg_match_all(
            '/{{([0-9]+)}}/',
            $component,
            $matches
        );
        $results = array($id => $revision->getRevisionId(), 'lang' => $this->_lang, 'children' => array(), 'unpublished' => null);
        foreach ($matches[1] as $group) {
            $results['children'][] = $this->unnpublishContent($group);
        }
        $results['unpublished'] = $this->unpublishNode($revision);
        return $results;
    }

    /**
     * Publish a content node and its children.
     *
     * @param int $id The id of the node.
     * @return array The unpublished node and revision id.
     */
    protected function unnpublishContent($id)
    {
        $revision = $this->getNode($id, $this->_lang);
        if ($revision == null) {
            return $id . " not found";
        }
        $unpublished = $this->unpublishNode($revision);
        return array($id => $revision->getRevisionId(), 'lang' => $this->_lang, 'unpublished' => $unpublished);
    }

    /**
     * Helper function to publish a node.
     *
     * @param Node $entity
     * @return void
     */
    protected function unpublishNode(Node $entity)
    {
        $contentTypes = \Drupal::configFactory()->get('pagetree.settings')->get('contentTypes');
        if ($entity->isPublished() || $entity->get('moderation_state')->target_id != 'draft' || in_array($entity->getType(), $contentTypes)) {
            drupal_register_shutdown_function(function () use ($entity) {
                if ($entity->getType() == 'iqbm_page' && $entity->hasField('field_iqbm_disable_search') && $entity->field_iqbm_disable_search->value == null) {
                    $entity->field_iqbm_disable_search->value = 0;
                }
                $entity->set('moderation_state', 'unpublished');
                $entity->setPublished(false);
                $entity->save();
                $entity->setRevisionLogMessage($this->_message);
                $entity->set('moderation_state', 'draft');
                $entity->isDefaultRevision(true);
                $entity->save();
            });
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Wrapper for NodeCollector::getLatestRevision
     *
     * @param int $id
     * @param string $lang
     * @return Drupal\Node\Entity\Node
     */
    protected function getNode($id, $lang)
    {
        return NodeCollector::getLatestRevision($id, $lang);
    }

    /**
     * Cleanup revisions.
     *
     * Removes all revisions that are drafts and are older than the second to latest unpublished revision.
     *
     * @param Node $node
     * @return void
     */
    protected function _deleteRevisions(Node $node)
    {
        $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
        $vids = $nodeStorage->revisionIds($node);
        $vids = array_reverse($vids);
        $lastPublish = 0;
        foreach ($vids as $vid) {
            $revision = $nodeStorage->loadRevision($vid);
            if ($revision->hasTranslation($this->_lang) && $revision->getTranslation($this->_lang)->isRevisionTranslationAffected()) {
                $revision = $revision->getTranslation($this->_lang);
                if ($lastPublish > 1) {
                    if ((!$revision->isPublished() || $revision->moderation_state->target_id != 'unpublished') && $revision->getRevisionLogMessage() != 'Importierte Seite') {
                        $nodeStorage->deleteRevision($vid);
                    }
                } elseif ($revision->isPublished()) {
                    $lastPublish += 1;
                }
            }
        }
    }
}
