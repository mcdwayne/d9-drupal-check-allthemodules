<?php

namespace Drupal\og_sm\Tests;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides methods to facilitate og_sm site tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait SiteCreationTrait {

  /**
   * Helper to create a content type.
   *
   * @param string $name
   *   Content type name.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The node type.
   */
  protected function createNodeType($name) {
    // Create the content type.
    $node_type = NodeType::create(['type' => $name, 'name' => $name]);
    $node_type->save();
    return $node_type;
  }

  /**
   * Create a Group node type.
   *
   * @param string $name
   *   Content type name.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The node type.
   */
  protected function createGroupNodeType($name) {
    $type = $this->createNodeType($name);
    Og::groupTypeManager()->addGroup('node', $name);
    return $type;
  }

  /**
   * Create a Group Content node type.
   *
   * @param string $name
   *   Content type name.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The node type.
   */
  protected function createGroupContentNodeType($name) {
    $type = $this->createNodeType($name);
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $name);
    return $type;
  }

  /**
   * Create a Group node.
   *
   * @param string $node_type
   *   The node type to create the group for.
   * @param array $values
   *   The node values on creation.
   *
   * @return \Drupal\node\NodeInterface
   *   The created group node.
   */
  protected function createGroup($node_type, array $values = []) {
    $values['type'] = $node_type;
    return $this->createNode($values);
  }

  /**
   * Create a Group Content node.
   *
   * @param string $node_type
   *   The node type to create the group for.
   * @param \Drupal\node\NodeInterface[] $groups
   *   (optional) Array of groups the node is member of.
   * @param array $values
   *   The node values on creation.
   *
   * @return \Drupal\node\NodeInterface
   *   The created group content node.
   */
  protected function createGroupContent($node_type, array $groups = [], array $values = []) {
    $values['type'] = $node_type;
    foreach ($groups as $group) {
      $values[OgGroupAudienceHelperInterface::DEFAULT_FIELD][] = [
        'target_id' => $group->id(),
      ];
    }
    return $this->createNode($values);
  }

  /**
   * Create a user that is member of one or more Groups.
   *
   * @param array $permissions
   *   (optional) Array of permissions for this user.
   * @param \Drupal\node\NodeInterface[] $groups
   *   (optional) Array of groups the node is member of.
   * @param array $site_permissions
   *   (optional) Array of groups permissions for this user's memberships.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user object.
   */
  protected function createGroupUser(array $permissions = [], array $groups = [], $site_permissions = []) {
    $og_roles = [];
    $account = $this->createUser($permissions);
    // Add the group memberships (if any).
    foreach ($groups as $group) {
      $membership = Og::createMembership($group, $account);

      if ($site_permissions) {
        if (!isset($og_roles[$group->getEntityTypeId()][$group->bundle()])) {
          $og_role = OgRole::create();
          $og_role
            ->setName($this->randomMachineName())
            ->setLabel($this->randomString())
            ->setGroupBundle($group->bundle())
            ->setGroupType($group->getEntityTypeId());

          foreach ($site_permissions as $site_permission) {
            $og_role->grantPermission($site_permission);
          }
          $og_role->save();
          $og_roles[$group->getEntityTypeId()][$group->bundle()] = $og_role;
        }
        $membership->addRole($og_roles[$group->getEntityTypeId()][$group->bundle()]);
      }

      $membership->save();
    }

    return $account;
  }

  /**
   * Helper to create a taxonomy with OG field.
   *
   * @param string $name
   *   The machine name for the taxonomy.
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   *   The vocabulary object.
   */
  protected function createGroupVocabulary($name) {
    // Create the vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $name,
      'description' => 'Test the ' . $name,
      'vid' => $name,
    ]);
    // Add the group field.
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'taxonomy_term', $vocabulary->id());
    return $vocabulary;
  }

  /**
   * Helper to create a term.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary to create the term for.
   * @param string $name
   *   The term name.
   * @param \Drupal\node\NodeInterface[] $groups
   *   The optional array of groups the term belongs to.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term.
   */
  protected function createTerm(VocabularyInterface $vocabulary, $name, array $groups = []) {
    $values = [
      'vid' => $vocabulary->id(),
      'name' => $name,
      'description' => sprintf('Test term for %s vocabulary', $vocabulary->label()),
    ];

    // Add the group memberships (if any).
    foreach ($groups as $group) {
      $values[OgGroupAudienceHelperInterface::DEFAULT_FIELD][] = [
        'target_id' => $group->id(),
      ];
    }

    return Term::create($values);
  }

  /**
   * Creates a node based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the node, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array.
   *   The following defaults are provided:
   *   - title: Random string.
   *   - type: 'page'.
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNode(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'title'     => $this->randomMachineName(8),
      'type'      => 'page',
      'uid'       => \Drupal::currentUser()->id(),
    ];
    $node = Node::create($settings);
    $node->save();

    return $node;
  }

}
