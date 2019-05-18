<?php

namespace Drupal\collect_crm\Tests;

use Drupal\collect\Entity\Container;
use Drupal\crm_core_activity\Entity\Activity;
use Drupal\crm_core_activity\Entity\ActivityType;
use Drupal\crm_core_contact\Entity\Individual;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests a processing workflow for creating CRM Contacts from a container.
 *
 * @group collect_crm
 */
class ProcessContactTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect_crm',
    'crm_core_activity',
    'entity_test',
    // @todo Remove node dependency after https://www.drupal.org/node/2308745
    'node',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Place local tasks, actions and page title blocks.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    // Add fields to the test entity type: one for some kind of originator, one
    // for some kind of recipient. One is a name and one is an email, to test
    // different aspects of matching.
    $this->createField('entity_test_mul', 'gift', 'donor', 'email', 'Donor email');
    $this->createField('entity_test_mul', 'gift', 'recipient', 'string', 'Recipient name');

    // Add activity type.
    ActivityType::create([
      'name' => t('Gift'),
      'type' => 'gift',
    ])->save();
  }

  /**
   * Tests setting up contact matching through the processing configuration UI.
   *
   * An entity is created for a dummy entity type with a name and an email
   * field. A CollectJSON model is created to handle it, and its processing is
   * set up to match CRM Contacts from containers and create a CRM Activity
   * record. The post-processing is then triggered, and the resulting Contacts
   * and the Activity are asserted.
   */
  public function testContactProcessing() {
    // Create an entity.
    $entity = EntityTestMul::create([
      'type' => 'gift',
      'name' => 'Cookie',
      'donor' => 'jlennon@example.com',
      'recipient' => 'Yoko',
    ]);
    $entity->save();

    // Log in as Collect administrator.
    $admin_user = $this->drupalCreateUser([
      'administer collect',
      'view any crm_core_individual entity',
      'view any crm_core_activity entity',
      'view test entity',
      'administer matchers'
    ]);
    $this->drupalLogin($admin_user);
    // Enable CRM matching rule.
    $this->drupalPostForm('admin/config/crm-core/match/individual', [
      'configuration[rules][name:given][status]' => TRUE,
      'configuration[rules][name:given][operator]' => 'CONTAINS',
      'configuration[rules][contact_mail:value][status]' => TRUE,
      'configuration[rules][contact_mail:value][operator]' => '=',
    ], t('Save'));

    // Capture the created entity.
    $this->drupalPostForm('admin/content/collect/capture', ['entity_type' => 'entity_test_mul'], t('Select entity type'));
    $this->drupalPostForm(NULL, ['operation' => 'single', 'entity' => 'Cookie (' . $entity->id() . ')'], t('Capture'));
    $this->assertText('The Test entity - data table entity has been captured.');

    // Create suggested model.
    $this->clickLink(t('Set up a @plugin model', ['@plugin' => t('Collect JSON')]));
    $this->drupalPostForm(NULL, ['label' => 'User entity', 'id' => 'user_entity'], t('Save'));

    // Edit model processing workflow.
    $this->drupalGet('admin/structure/collect/model/manage/user_entity/processing');
    $this->drupalPostForm(NULL, ['processor_add_select' => 'contact_matcher'], t('Add'));
    $this->drupalPostForm(NULL, ['processor_add_select' => 'contact_matcher'], t('Add'));
    $this->drupalPostForm(NULL, ['processor_add_select' => 'activity_creator'], t('Add'));
    list($contact_matcher_from_uuid, $contact_matcher_to_uuid, $activity_creator_uuid) = $this->getProcessorKeys();
    $this->assertText('Matches or creates a CRM Core Contact entity.');
    $this->assertText('Creates a CRM Core Activity entity, including matched contacts.');
    // Form submission is divided because field list is populated after
    // selecting contact_type.
    $this->drupalPostForm(NULL, [
      'processors[' . $contact_matcher_from_uuid . '][settings][relation]' => 'from',
      'processors[' . $contact_matcher_from_uuid . '][settings][contact_type]' => 'customer',
      'processors[' . $contact_matcher_from_uuid . '][settings][matcher]' => 'inmail_individual',
      'processors[' . $contact_matcher_to_uuid . '][settings][relation]' => 'to',
      'processors[' . $contact_matcher_to_uuid . '][settings][contact_type]' => 'customer',
      'processors[' . $contact_matcher_to_uuid . '][settings][matcher]' => 'inmail_individual',
      'processors[' . $activity_creator_uuid . '][settings][title_property]' => 'name',
    ], t('Save'));
    $this->drupalPostForm(NULL, [
      // The donor is identified by email.
      'processors[' . $contact_matcher_from_uuid . '][settings][fields][contact_mail][model_property]' => 'donor',
      // The recipient is identified by name.
      'processors[' . $contact_matcher_to_uuid . '][settings][fields][name][model_property]' => 'recipient',
    ], t('Save'));

    // Execute processing on the entity container.
    $containers = Container::loadMultiple();
    $user_container = end($containers);
    \Drupal::service('collect.postprocessor')->process($user_container);

    $contact_ids = \Drupal::entityQuery('crm_core_individual')->execute();

    // Assert new CRM Contact was created.
    $this->drupalGet('crm-core/individual');
    // Find recipient's name.
    $this->assertText('Yoko');
    // Go to nameless donor and find its email address.
    $this->drupalGet('crm-core/individual/' . current($contact_ids));
    // @todo: Uncomment after https://www.drupal.org/node/2708253.
    // $this->clickLink(t('Nameless #@id', ['@id' => current($contact_ids)]));
    $this->assertText('Mail');
    $this->assertText('jlennon@example.com');

    // Assert new CRM Activity was created.
    $containers = Container::loadMultiple();
    /** @var Container $latest_container */
    $latest_container = end($containers);
    $formatted_date = \Drupal::service('date.formatter')->format($latest_container->getDate(), 'medium');
    $this->drupalGet('crm-core/activity');
    $this->assertText($formatted_date);
    $this->clickLink('Cookie');
    $this->assertLink(t('Nameless #@id', ['@id' => current($contact_ids)]));
    $this->assertLink('Yoko');

    // The next processing should match the existing contacts, and not create
    // new ones.
    \Drupal::service('collect.postprocessor')->process($user_container);
    $this->drupalGet('crm-core/individual');
    $this->assertEqual(2, count($this->xpath('//tbody/tr')));
    // A new activity should be created.
    $this->drupalGet('crm-core/activity');
    $this->assertEqual(2, count($this->xpath('//tbody/tr')));
  }

  /**
   * Creates a field on a given entity type.
   */
  protected function createField($entity_type, $bundle, $name, $type, $label) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'type' => $type,
      'entity_type' => $entity_type,
    ])->save();
    FieldConfig::create([
      'field_name' => $name,
      'field_type' => $type,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
    ])->save();
  }

  /**
   * Tests matching contacts through user URI.
   */
  public function testUserUriContactMatching() {
    // Add a content type.
    $this->drupalCreateContentType(['type' => 'article']);

    // Log in as Collect administrator.
    $admin_user = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'administer collect',
      'view any crm_core_individual entity',
      'view any crm_core_activity entity',
      'view test entity',
      'administer matchers'
    ]);
    $this->drupalLogin($admin_user);

    // Create a node.
    $entity = Node::create(['title' => 'Foo', 'type' => 'article']);
    $entity->save();

    // Capture the created entity.
    $this->drupalPostForm('admin/content/collect/capture', ['entity_type' => 'node'], t('Select entity type'));
    $this->drupalPostForm(NULL, ['operation' => 'single', 'entity' => 'Foo (' . $entity->id() . ')'], t('Capture'));

    // Create suggested model.
    $this->clickLink(t('Set up a @plugin model', ['@plugin' => t('Collect JSON')]));
    $this->drupalPostForm(NULL, [
      'label' => 'Content entity',
      'id' => 'collect_json_node_article'
    ], t('Save'));

    // Add a new contact matcher processor.
    $this->drupalGet('admin/structure/collect/model/manage/collect_json_node_article/processing');
    $this->drupalPostForm(NULL, ['processor_add_select' => 'contact_matcher'], t('Add'));
    list($contact_matcher_uuid) = $this->getProcessorKeys();
    $this->drupalPostForm(NULL, [
      'processors[' . $contact_matcher_uuid . '][settings][relation]' => 'relation',
      'processors[' . $contact_matcher_uuid . '][settings][contact_type]' => 'customer',
      'processors[' . $contact_matcher_uuid . '][settings][matcher]' => 'inmail_individual',
    ], t('Save'));

    // Edit existing entity and capture it in order to create a new CRM contact.
    $entity->setTitle('Foo Bar');
    $entity->save();
    $this->drupalPostForm('admin/content/collect/capture', ['entity_type' => 'node'], t('Select entity type'));
    $this->drupalPostForm(NULL, ['operation' => 'single', 'entity' => 'Foo Bar (' . $entity->id() . ')'], t('Capture'));

    // Go to contacts and assert there is a new contact created.
    $this->drupalGet('crm-core/individual');
    // @todo: Uncomment after https://www.drupal.org/node/2708253.
    // $this->assertText('Nameless');
    $this->assertText('Customer');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    // Create a new node with the same user, and assert that new contact is not
    // created.
    $entity = Node::create(['title' => 'Pa ra pa pa', 'type' => 'article']);
    $entity->save();
    $this->drupalPostForm('admin/content/collect/capture', ['entity_type' => 'node'], t('Select entity type'));
    $this->drupalPostForm(NULL, ['operation' => 'single', 'entity' => 'Pa ra pa pa (' . $entity->id() . ')'], t('Capture'));
    $this->drupalGet('crm-core/individual');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    // Enable CRM matching rule.
    $this->drupalPostForm('admin/config/crm-core/match/individual', [
      'configuration[rules][name:given][status]' => TRUE,
      'configuration[rules][name:given][operator]' => 'CONTAINS',
      'configuration[rules][contact_mail:value][status]' => TRUE,
      'configuration[rules][contact_mail:value][operator]' => '=',
    ], t('Save'));

    // Capture the author user, process with ContactMatcher, and assert that the
    // existing contact is updated with user values.
    $author = $entity->getOwner()
      ->setUsername('Shelley')
      ->setEmail('shelley@example.com');
    $author->save();
    $author_container = \Drupal::service('collect.capture_entity')->capture($author);
    $this->drupalGet($author_container->url());
    $this->clickLink(t('Set up a @plugin model', ['@plugin' => t('Collect JSON')]));
    $this->drupalPostForm(NULL, ['label' => 'User', 'id' => 'user'], t('Save'));
    $this->drupalPostAjaxForm('admin/structure/collect/model/manage/user/processing', ['processor_add_select' => 'contact_matcher'], ['op' => t('Add')]);
    list($contact_matcher_uuid) = $this->getProcessorKeys();
    $this->drupalPostForm(NULL, [
      'processors[' . $contact_matcher_uuid . '][settings][contact_type]' => 'customer',
      'processors[' . $contact_matcher_uuid . '][settings][matcher]' => 'inmail_individual',
    ], t('Save'));
    $this->drupalPostForm(NULL, [
      'processors[' . $contact_matcher_uuid . '][settings][fields][name][model_property]' => 'name',
      'processors[' . $contact_matcher_uuid . '][settings][fields][contact_mail][model_property]' => 'mail',
    ], t('Save'));
    \Drupal::service('collect.postprocessor')->process($author_container);
    $this->drupalGet('crm-core/individual');
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    $this->assertText('Shelley');
    // @todo: Replace with clicking on "Edit", when operations are fixed.
    $this->drupalGet('crm-core/individual/1');
    $this->assertText('shelley@example.com');
  }

  /**
   * Test Activity with DER processing.
   */
  public function testActivityProcessing() {
    // Log in as Collect administrator.
    $admin_user = $this->drupalCreateUser([
      'administer collect',
      'view any crm_core_individual entity',
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($admin_user);

    $individual = Individual::create(['type' => 'customer']);
    $individual->save();
    $activity = Activity::create(['type' => 'meeting']);
    $activity->addParticipant($individual);
    $activity->save();
    $this->drupalPostForm('/admin/content/collect/capture', [
      'entity_type' => 'crm_core_activity',
      'operation' => 'multiple',
    ], t('Capture'));
    $this->assertResponse(200);
    $this->assertUrl('/admin/content/collect');
    $this->assertRaw('All CRM Core Activity entites have been captured.');
    $this->clickLink('View');
    $this->assertLink('Raw data');
    $this->assertLink('Revisions');
  }

  /**
   * Finds the processor keys in the processing form raw content.
   *
   * @return string[]
   *   The processor keys (uuids), in order of weight.
   */
  protected function getProcessorKeys() {
    preg_match_all('/processors\[([^\]]+)\]/', $this->getRawContent(), $matches);
    return array_values(array_unique($matches[1]));
  }

}
