<?php

namespace Drupal\search_api_revisions\Plugin\search_api\processor;

use Drupal\comment\CommentInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\processor\ContentAccess;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Query\QueryInterface;

/**
 * Adds content access checks for nodes and comments.
 *
 * @SearchApiProcessor(
 *   id = "content_revision_access",
 *   label = @Translation("Content revision access."),
 *   description = @Translation("Adds content access checks for node revisions."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = -10,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class ContentRevisionAccess extends ContentAccess {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), ['node', 'comment'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Node revision access information'),
        'description' => $this->t('Data needed to apply node access.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'hidden' => TRUE,
      ];
      $properties['search_api_node_revision_grants'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    static $anonymous_user;

    if (!isset($anonymous_user)) {
      // Load the anonymous user.
      $anonymous_user = new AnonymousUserSession();
    }

    // Only run for node and comment items.
    $entity_type_id = $item->getDatasource()->getEntityTypeId();
    if (!in_array($entity_type_id, ['node', 'comment'])) {
      return;
    }

    // Get the node object.
    $node = $this->getNode($item->getOriginalObject());
    if (!$node) {
      // Apparently we were active for a wrong item.
      return;
    }

    $fields = $item->getFields();
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'search_api_node_revision_grants');
    foreach ($fields as $field) {
      // Collect grant information for the node.
      if (!$node->access('view', $anonymous_user)) {
        // If anonymous user has no permission we collect all grants with
        // their realms in the item.
        $sql = 'SELECT * FROM {node_access} WHERE (nid = 0 OR nid = :nid) AND grant_view = 1';
        $args = [':nid' => $node->id()];
        foreach ($this->getDatabase()->query($sql, $args) as $grant) {
          $field->addValue("node_access_{$grant->realm}:{$grant->gid}");
        }
      }
      else {
        // Add the generic pseudo view grant if we are not using node access
        // or the node is viewable by anonymous users.
        $field->addValue('node_access__all');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $entity_type = $datasource->getEntityTypeId();
      if (in_array($entity_type, ['node', 'comment'])) {
        $this->ensureField($datasource_id, 'status', 'boolean');
        if ($entity_type == 'node') {
          $this->ensureField($datasource_id, 'uid', 'integer');
        }
      }
    }

    $field = $this->ensureField(NULL, 'search_api_node_revision_grants', 'string');
    $field->setHidden();
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * Will be either the node itself, or the node the comment is attached to.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();
    if ($item instanceof CommentInterface) {
      $item = $item->getCommentedEntity();
    }
    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }

  /**
   * Adds a node access filter to a search query, if applicable.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to which a node access filter should be added, if applicable.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for whom the search is executed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if not all necessary fields are indexed on the index.
   */
  protected function addNodeAccess(QueryInterface $query, AccountInterface $account) {
    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    // Gather the affected datasources, grouped by entity type, as well as the
    // unaffected ones.
    $affected_datasources = [];
    $unaffected_datasources = [];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $entity_type = $datasource->getEntityTypeId();
      if (in_array($entity_type, ['node', 'comment'])) {
        $affected_datasources[$entity_type][] = $datasource_id;
      }
      else {
        $unaffected_datasources[] = $datasource_id;
      }
    }

    /* The filter structure we want looks like this:
     *   [belongs to other datasource]
     *   OR
     *   (
     *     [is enabled (or was created by the user, if applicable)]
     *     AND
     *     [grants view access to one of the user's gid/realm combinations]
     *   )
     * If there are no "other" datasources, we don't need the nested OR,
     * however, and can add the inner conditions directly to the query.
     */
    if ($unaffected_datasources) {
      $outer_conditions = $query->createConditionGroup('OR', ['content_access']);
      $query->addConditionGroup($outer_conditions);
      foreach ($unaffected_datasources as $datasource_id) {
        $outer_conditions->addCondition('search_api_datasource', $datasource_id);
      }
      $access_conditions = $query->createConditionGroup('AND');
      $outer_conditions->addConditionGroup($access_conditions);
    }
    else {
      $access_conditions = $query;
    }

    // Collect all the required fields that need to be part of the index.
    $unpublished_own = $account->hasPermission('view own unpublished content');

    $enabled_conditions = $query->createConditionGroup('OR', ['content_access_enabled']);
    foreach ($affected_datasources as $entity_type => $datasources) {
      foreach ($datasources as $datasource_id) {
        // If this is a comment datasource, or users cannot view their own
        // unpublished nodes, a simple filter on "status" is enough. Otherwise,
        // it's a bit more complicated.
        $status_field = $this->findField($datasource_id, 'status', 'boolean');
        if ($status_field) {
          $enabled_conditions->addCondition($status_field->getFieldIdentifier(), TRUE);
        }
        if ($entity_type == 'node' && $unpublished_own) {
          $author_field = $this->findField($datasource_id, 'uid', 'integer');
          if ($author_field) {
            $enabled_conditions->addCondition($author_field->getFieldIdentifier(), $account->id());
          }
        }
      }
    }
    /*
     * @TODO: This is related to https://www.drupal.org/node/2867441
     * $access_conditions->addConditionGroup($enabled_conditions);
     */

    // Filter by the user's node access grants.
    $node_grants_field = $this->findField(NULL, 'search_api_node_revision_grants', 'string');
    if (!$node_grants_field) {
      return;
    }
    $node_grants_field_id = $node_grants_field->getFieldIdentifier();
    $grants_conditions = $query->createConditionGroup('OR', ['content_access_grants']);
    $grants = node_access_grants('view', $account);
    foreach ($grants as $realm => $gids) {
      foreach ($gids as $gid) {
        $grants_conditions->addCondition($node_grants_field_id, "node_access_$realm:$gid");
      }
    }
    // Also add items that are accessible for everyone by checking the "access
    // all" pseudo grant.
    $grants_conditions->addCondition($node_grants_field_id, 'node_access__all');
    $access_conditions->addConditionGroup($grants_conditions);
  }

}
