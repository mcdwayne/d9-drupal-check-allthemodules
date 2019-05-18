<?php

namespace Drupal\pbf\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\user\Entity\User;

/**
 * General setup and helper function for testing pbf module.
 *
 * @group pbf
 */
class PbfTestBase extends WebTestBase {

  use TaxonomyTestTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'system',
    'language',
    'user',
    'node',
    'field',
    'field_ui',
    'taxonomy',
    'search',
    'pbf',
  );

  /**
   * Array standard permissions for normal user.
   *
   * @var array
   */
  protected $permissions;

  /**
   * User with permission to administer entites.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Standard User without permission on content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * User object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $otherUser;

  /**
   * Entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article1;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article2;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article3;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group1;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group2;

  /**
   * A vocabulary created.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * A term created.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * A term created.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term2;

  /**
   * A Role created.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $role1;

  /**
   * Setup and Rebuild node access.
   */
  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'group']);

    $this->adminUser = $this->drupalCreateUser([
      'create article content',
      'create group content',
      'edit own article content',
      'edit any article content',
      'edit any group content',
      'delete any article content',
      'delete own article content',
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer account settings',
      'administer node form display',
      'administer node display',
      'administer users',
      'administer user fields',
      'administer user form display',
      'administer user display',
      'bypass node access',
      'search content',
    ]);

    $this->permissions = [
      'create article content',
      'edit own article content',
      'delete own article content',
      'access content',
      'search content',
      'access user profiles',
    ];

    $this->normalUser = $this->drupalCreateUser($this->permissions);
    $this->otherUser = $this->drupalCreateUser($this->permissions);

    // Create articles nodes.
    $this->group1 = $this->drupalCreateNode(['type' => 'group', 'title' => 'Group 1']);
    $this->group2 = $this->drupalCreateNode(['type' => 'group', 'title' => 'Group 2']);

    // Create vocabulary and terms.
    $this->vocabulary = $this->createVocabulary();
    $this->term1 = $this->createTerm($this->vocabulary);
    $this->term2 = $this->createTerm($this->vocabulary);

    $this->role1 = $this->createRole($this->permissions);

    node_access_rebuild();
  }

  /**
   * Test base.
   *
   * - check there is no articles.
   */
  protected function testPbfBaseAccess() {

  }

  /**
   * On the search page, search for a string and assert the expected results.
   *
   * @param string $search_query
   *   String to search for.
   * @param int $expected_result_count
   *   Expected result count.
   */
  protected function checkSearchResults($search_query, $expected_result_count) {
    $this->drupalPostForm('search/node', array('keys' => $search_query), 'Search');
    $search_results = $this->xpath("//ol[contains(@class, 'search-results')]/li");
    $this->assertEqual(count($search_results), $expected_result_count, t('Found the expected number of search results'));
  }

  /**
   * Creates a field of an Pbf field storage on the specified bundle.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field; if it already exists, a new instance of the existing
   *   field will be created.
   * @param string $field_label
   *   The label of the field.
   * @param string $target_entity_type
   *   The type of the referenced entity.
   * @param string $selection_handler
   *   The selection handler used by this field.
   * @param array $selection_handler_settings
   *   An array of settings supported by the selection handler specified above.
   *   (e.g. 'target_bundles', 'sort', 'auto_create', etc).
   * @param int $cardinality
   *   The cardinality of the field.
   * @param string $user_method
   *   The method used for granting access to user.
   * @param int $priority
   *   The priority access.
   * @param string $synchronized_with
   *   The field is synchronized with another Pbf field.
   * @param int $synchronized_from_target
   *   The synchronized targeted field can synchronize the source.
   *
   * @return \Drupal\field\Entity\FieldConfig $field
   *   The field created or loaded.
   *
   * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase::buildConfigurationForm()
   */
  protected function createPbfField($entity_type, $bundle, $field_name, $field_label, $target_entity_type, $selection_handler = 'default', $selection_handler_settings = array(), $cardinality = -1, $user_method = 'user', $priority = 0, $synchronized_with = '', $synchronized_from_target = 0) {
    // Look for or add the specified field to the requested entity bundle.
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create(array(
        'field_name' => $field_name,
        'type' => 'pbf',
        'entity_type' => $entity_type,
        'cardinality' => $cardinality,
        'settings' => array(
          'target_type' => $target_entity_type,
        ),
      ))->save();
    }
    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create(array(
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => array(
          'handler' => $selection_handler,
          'handler_settings' => $selection_handler_settings,
          'priority' => $priority,
          'user_method' => $user_method,
          'synchronized_with' => $synchronized_with,
          'synchronized_form_target' => $synchronized_from_target,
        ),
      ))->save();
    }
    $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    return $field;

  }

  /**
   * Set the value of field_name attached to user.
   *
   * @param int $uid
   *   The user uid.
   * @param string $field_name
   *   The field name to set.
   * @param array $value
   *   The values of field name.
   */
  protected function setUserField($uid, $field_name = '', $value = NULL) {
    if ($field_name) {
      User::load($uid)->set($field_name, $value)->save();
    }

  }

  /**
   * Set the widget for a component in a form display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $mode
   *   The mode name.
   * @param string $field_name
   *   The field name to set.
   * @param string $widget_id
   *   The widget id to set.
   * @param array $settings
   *   The settings of widget.
   */
  protected function setFormDisplay($form_display_id, $entity_type, $bundle, $mode = 'default', $field_name, $widget_id, $settings) {
    // Set article's form display.
    $this->formDisplay = EntityFormDisplay::load($form_display_id);

    if (!$this->formDisplay) {
      EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->formDisplay = EntityFormDisplay::load($form_display_id);
    }
    if ($this->formDisplay instanceof EntityFormDisplayInterface) {
      $this->formDisplay->setComponent($field_name, [
        'type' => $widget_id,
        'settings' => $settings,
      ])->save();
    }
  }

  /**
   * Set the widget for a component in a View display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $mode
   *   The mode name.
   * @param string $field_name
   *   The field name to set.
   * @param string $formatter_id
   *   The formatter id to set.
   * @param array $settings
   *   The settings of widget.
   */
  protected function setViewDisplay($form_display_id, $entity_type, $bundle, $mode = 'default', $field_name, $formatter_id, $settings) {
    // Set article's view display.
    $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    if (!$this->viewDisplay) {
      EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    }
    if ($this->viewDisplay instanceof EntityViewDisplayInterface) {
      $this->viewDisplay->setComponent($field_name, [
        'type' => $formatter_id,
        'settings' => $settings,
      ])->save();
    }

  }

  /**
   * Helper function to create and attach a Pbf Node field.
   *
   * @param string $field_name
   *   The field name to create and attach.
   * @param array $widget_settings
   *   The widget form settings.
   */
  protected function attachPbfNodeFields($field_name, $widget_settings = []) {
    // Add a pbf field to the article content type which reference group.
    $handler_settings = array(
      'target_bundles' => array(
        'group' => 'group',
      ),
      'auto_create' => FALSE,
    );
    $this->createPbfField('node', 'article', $field_name, 'Content of group', 'node', 'default', $handler_settings, -1);
    // Add a pbf field to user entity which reference group.
    $this->createPbfField('user', 'user', $field_name, 'Member of group', 'node', 'default', $handler_settings, -1);

    // Set the form display.
    $settings = $widget_settings + [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_widget', $settings);
    $this->setFormDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_widget', $settings);

    // Set the view display.
    $settings = [
      'link' => TRUE,
    ];
    $this->setViewDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_formatter_default', $settings);
    $this->setViewDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_formatter_default', $settings);

  }

  /**
   * Helper function to create and attach a Pbf Node field synchronized.
   *
   * @param string $field_name
   *   The field name to create and attach on article and user.
   * @param string $group_field_name
   *   The field name to create and attach on group.
   * @param array $widget_settings
   *   The widget form settings.
   */
  protected function attachPbfSynchronizedFields($field_name, $group_field_name, $widget_settings = []) {
    // Add a pbf field to the article content type which reference group.
    $handler_settings = array(
      'target_bundles' => array(
        'group' => 'group',
      ),
      'auto_create' => FALSE,
    );
    $this->createPbfField('node', 'article', $field_name, 'Content of group', 'node', 'default', $handler_settings, -1);
    // Add a pbf field to user entity which reference group.
    $this->createPbfField('user', 'user', $field_name, 'Member of group', 'node', 'default', $handler_settings, -1);
    // Add a pbf field to group content type which reference user.
    $handler_settings = array(
      'target_bundles' => array(
        'user' => 'user',
      ),
      'auto_create' => FALSE,
    );
    $this->createPbfField('node', 'group', $group_field_name, 'Group members', 'user', 'default', $handler_settings, -1);

    // Set the form display.
    $settings = $widget_settings + [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_widget', $settings);
    $this->setFormDisplay('node.group.default', 'node', 'group', 'default', $group_field_name, 'pbf_widget', $settings);
    $this->setFormDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_widget', $settings);

    // Set the view display.
    $settings = [
      'link' => TRUE,
    ];
    $this->setViewDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_formatter_default', $settings);
    $this->setViewDisplay('node.group.default', 'node', 'group', 'default', $group_field_name, 'pbf_formatter_default', $settings);
    $this->setViewDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_formatter_default', $settings);

  }

  /**
   * Attach Pbf fields which reference taxonomy terms.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary of term referenced.
   * @param string $field_name
   *   The field name to create.
   */
  protected function attachPbfTermFields(VocabularyInterface $vocabulary, $field_name) {

    $handler_settings = array(
      'target_bundles' => array(
        $vocabulary->id() => $vocabulary->id(),
      ),
      'auto_create' => FALSE,
    );
    // Add a pbf field to the article content type which reference term.
    $this->createPbfField('node', 'article', $field_name, 'Content related to term', 'taxonomy_term', 'default', $handler_settings, -1);
    // Add a pbf field to user entity which reference term.
    $this->createPbfField('user', 'user', $field_name, 'User related to term', 'taxonomy_term', 'default', $handler_settings, -1);

    // Set the form display.
    $settings = [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_widget', $settings);
    $this->setFormDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_widget', $settings);

    // Set the view display.
    $settings = [
      'link' => TRUE,
    ];
    $this->setViewDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_formatter_default', $settings);
    $this->setViewDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_formatter_default', $settings);
  }

  /**
   * Attach Pbf fields which reference Users.
   *
   * @param string $field_name
   *   The field name created.
   * @param string $user_method
   *   The user method which calculate access permissions.
   */
  protected function attachPbfUserFields($field_name, $user_method = 'user') {

    $handler_settings = array(
      'target_bundles' => array(
        'user' => 'user',
      ),
      'auto_create' => FALSE,
    );
    // Add a pbf field to the article content type which reference term.
    $this->createPbfField('node', 'article', $field_name, 'Grant access to user', 'user', 'default', $handler_settings, -1, $user_method);
    // Set the form display.
    $settings = [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_widget', $settings);
    // Set the view display.
    $settings = [
      'link' => TRUE,
    ];
    $this->setViewDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_formatter_default', $settings);

    if ($user_method == 'ref_user') {
      // Add a pbf field to user entity which reference term.
      $this->createPbfField('user', 'user', $field_name, 'User related to user', 'user', 'default', $handler_settings, -1);
      $this->setFormDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_widget', $settings);
      $this->setViewDisplay('user.user.default', 'user', 'user', 'default', $field_name, 'pbf_formatter_default', $settings);
    }

  }


  /**
   * Helper function to create and attach a Pbf Role field.
   *
   * @param string $field_name
   *   The field name to create and attach.
   */
  protected function attachPbfRoleFields($field_name) {

    $handler_settings = array(
      'target_bundles' => NULL,
      'auto_create' => FALSE,
    );
    // Add a pbf field to the article content type which reference term.
    $this->createPbfField('node', 'article', $field_name, 'Grant access to role', 'user_role', 'default', $handler_settings, -1);

    // Set the form display.
    $settings = [
      'match_operator' => 'CONTAINS',
      'size' => 30,
      'placeholder' => '',
    ];
    $this->setFormDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_widget', $settings);

    // Set the view display.
    $settings = [
      'link' => FALSE,
    ];
    $this->setViewDisplay('node.article.default', 'node', 'article', 'default', $field_name, 'pbf_formatter_default', $settings);
  }


  /**
   * Create an article with value for Pbf field.
   *
   * @param string $title
   *   The content title.
   * @param string $field_name
   *   The Pbf field name to populate.
   * @param int|string $target_id
   *   The target id of Pbf field.
   * @param int $grant_public
   *   The grant public value.
   * @param int $grant_view
   *   The grant view value.
   * @param int $grant_update
   *   The grant update value.
   * @param int $grant_delete
   *   The grant delete value.
   *
   * @return \Drupal\node\NodeInterface
   *   The node created.
   */
  protected function createSimpleArticle($title, $field_name = '', $target_id = NULL, $grant_public = 1, $grant_view = 0, $grant_update = 0, $grant_delete = 0) {
    $values = array(
      'type' => 'article',
      'title' => $title,
      'body' => [
        'value' => 'Content body for ' . $title,
      ],
    );
    if ($field_name) {
      $values[$field_name] = [
        'target_id' => $target_id,
        'grant_public' => $grant_public,
        'grant_view' => $grant_view,
        'grant_update' => $grant_update,
        'grant_delete' => $grant_delete,
      ];
    }
    return $this->drupalCreateNode($values);
  }

  /**
   * Create an group with value for Pbf field.
   *
   * @param string $title
   *   The content title.
   * @param string $field_name
   *   The Pbf field name to populate.
   * @param int|string $target_id
   *   The target id of Pbf field.
   * @param int $grant_public
   *   The grant public value.
   * @param int $grant_view
   *   The grant view value.
   * @param int $grant_update
   *   The grant update value.
   * @param int $grant_delete
   *   The grant delete value.
   *
   * @return \Drupal\node\NodeInterface
   *   The node created.
   */
  protected function createSimpleGroup($title, $field_name = '', $target_id = NULL, $grant_public = 1, $grant_view = 0, $grant_update = 0, $grant_delete = 0) {
    $values = array(
      'type' => 'group',
      'title' => $title,
      'body' => [
        'value' => 'Content body for ' . $title,
      ],
    );
    if ($field_name) {
      $values[$field_name] = [
        'target_id' => $target_id,
        'grant_public' => $grant_public,
        'grant_view' => $grant_view,
        'grant_update' => $grant_update,
        'grant_delete' => $grant_delete,
      ];
    }
    return $this->drupalCreateNode($values);
  }

  /**
   * Create a Node with multiple Pbf fields filled.
   *
   * All the arrays passed as parameters must be with the same size.
   *
   * @param string $title
   *   The node title.
   * @param array $fields_name
   *   An array of field_name to populate.
   * @param array $target_id
   *   An array of target_id. target_id must be integer.
   * @param array $grant_public
   *   An array of boolean.
   * @param array $grant_view
   *   An array of boolean.
   * @param array $grant_update
   *   An array of boolean.
   * @param array $grant_delete
   *   An array of boolean.
   *
   * @return \Drupal\node\NodeInterface
   *   The Node created.
   */
  protected function createComplexArticle($title, $fields_name = [], $target_id = [], $grant_public = [], $grant_view = [], $grant_update = [], $grant_delete = []) {
    $values = array(
      'type' => 'article',
      'title' => $title,
      'body' => [
        'value' => 'Content body for ' . $title,
      ],
    );
    foreach ($fields_name as $key => $field_name) {
      $values[$field_name] = [
        'target_id' => $target_id[$key],
        'grant_public' => $grant_public[$key],
        'grant_view' => $grant_view[$key],
        'grant_update' => $grant_update[$key],
        'grant_delete' => $grant_delete[$key],
      ];
    }
    return $this->drupalCreateNode($values);
  }

  /**
   * Generate 2 articles with standard permissions.
   */
  protected function createArticles() {
    // Create articles nodes.
    $values = array(
      'type' => 'article',
      'title' => 'Article 1',
      'body' => [
        'value' => 'Content body for article 1',
      ],
      'field_pbf_group' => [
        'target_id' => $this->group1->id(),
        'grant_public' => 1,
        'grant_view' => 0,
        'grant_update' => 0,
        'grant_delete' => 0,
      ],
    );
    $this->article1 = $this->drupalCreateNode($values);

    $values = array(
      'type' => 'article',
      'title' => 'Article 2',
      'body' => [
        'value' => 'Content body for article 2',
      ],
      'field_pbf_group' => [
        'target_id' => $this->group1->id(),
        'grant_public' => 0,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ],
    );
    $this->article2 = $this->drupalCreateNode($values);
  }

}
