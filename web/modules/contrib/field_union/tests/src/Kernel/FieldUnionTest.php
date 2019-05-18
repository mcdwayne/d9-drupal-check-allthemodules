<?php

namespace Drupal\Tests\field_union\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_union\Entity\FieldUnion;
use Drupal\field_union\Plugin\Field\FieldType\FieldUnion as FieldUnionItem;
use Drupal\field_union\Plugin\Field\FieldType\FieldUnion as FieldUnionFieldType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\link\LinkItemInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines a class for testing basic functionality.
 *
 * @group field_union
 */
class FieldUnionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_union',
    'entity_test',
    'system',
    'field',
    'text',
    'user',
    'link',
    'filter',
    'filter_test',
    'taxonomy',
  ];

  /**
   * Test vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter', 'filter_test']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->vocabulary = Vocabulary::create([
      'name' => 'Categories',
      'vid' => 'categories',
    ]);
    $this->vocabulary->save();
    $this->installEntitySchema('entity_test');
    $union = FieldUnion::create([
      'id' => 'applicant',
      'label' => 'Applicant details',
      'description' => 'Applicant details',
      'fields' => [
        'first_name' => [
          'field_type' => 'string',
          'label' => 'First name',
          'name' => 'first_name',
          'description' => 'Enter first name',
          'required' => TRUE,
          'translatable' => FALSE,
          'default_value' => [],
          'settings' => [
            'max_length' => 255,
          ],
        ],
        'surname' => [
          'field_type' => 'string',
          'label' => 'Surname',
          'name' => 'surname',
          'description' => 'Enter surname',
          'required' => TRUE,
          'translatable' => FALSE,
          'default_value' => [],
          'settings' => [
            'max_length' => 255,
          ],
        ],
        'resume' => [
          'field_type' => 'link',
          'label' => 'Resume',
          'name' => 'resume',
          'description' => 'Enter resume',
          'required' => TRUE,
          'translatable' => FALSE,
          'default_value' => [],
          'settings' => [],
          'instance_settings' => [
            'title' => DRUPAL_OPTIONAL,
            'link_type' => LinkItemInterface::LINK_EXTERNAL,
          ],
        ],
        'bio' => [
          'field_type' => 'text',
          'label' => 'Bio',
          'name' => 'bio',
          'description' => 'Enter your bio',
          'required' => TRUE,
          'translatable' => FALSE,
          'default_value' => [],
          'settings' => [
            'max_length' => 255,
          ],
        ],
        'category' => [
          'field_type' => 'entity_reference',
          'label' => 'Category',
          'name' => 'category',
          'description' => 'Select category',
          'required' => TRUE,
          'translatable' => FALSE,
          'default_value' => [],
          'settings' => [
            'target_type' => 'taxonomy_term',
          ],
        ],
      ],
    ]);
    $union->save();
    $storage = FieldStorageConfig::create([
      'field_name' => 'applicant',
      'entity_type' => 'entity_test',
      'type' => 'field_union:applicant',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $storage->save();
    $field = FieldConfig::create([
      'field_name' => 'applicant',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => 'Name',
    ]);
    $field->save();
  }

  /**
   * Tests union.
   */
  public function testFieldUnion() {
    $entity = $this->assertEntityCreationStorageAndRetrieval();
    $item = $this->assertCreationOfAdditionalFieldItem($entity);
    $this->assertUpdatingFieldValuesViaProxies($item);
    $this->assertUnsettingFieldValuesViaProxies($item);
  }

  /**
   * Tests creation storage and retrieval.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Created entity.
   */
  protected function assertEntityCreationStorageAndRetrieval() {
    // Note we don't save this so we can test the preSave methods.
    $accepted = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Accepted',
    ]);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = EntityTest::create([
      'name' => 'entity',
      'applicant' => [
        'first_name' => 'Jerry',
        'surname' => 'Johnson',
        'resume' => [
          'uri' => 'http://example.com/jerry.johnson',
          'title' => 'My resume',
        ],
        'bio' => [
          'value' => '<p>My bio<strong>is amazing!</strong></p><div>but divs are not allowed</div>',
          'format' => 'filtered_html',
        ],
        'category' => $accepted,
      ],
    ]);
    $entity->save();
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('entity_test');
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    $this->assertEquals('Jerry', $entity->applicant->first_name->value);
    $this->assertEquals('Johnson', $entity->applicant->surname->value);
    $this->assertEquals($accepted->id(), $entity->applicant->category->entity->id());
    $this->assertEquals('Accepted', $entity->applicant->category->entity->label());
    $this->assertEquals('http://example.com/jerry.johnson', $entity->applicant->resume->uri);
    $this->assertEquals('My resume', $entity->applicant->resume->title);
    $this->assertEquals('<p>My bio<strong>is amazing!</strong></p><div>but divs are not allowed</div>', $entity->applicant->bio->value);
    $this->assertEquals('filtered_html', $entity->applicant->bio->format);
    $this->assertEquals('<p>My bio<strong>is amazing!</strong></p>but divs are not allowed', $entity->applicant->bio->processed);
    return $entity;
  }

  /**
   * Asserts creation and addition of a new item.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to add items to.
   *
   * @return \Drupal\field_union\Plugin\Field\FieldType\FieldUnion
   *   Field union item created.
   */
  protected function assertCreationOfAdditionalFieldItem(ContentEntityInterface $entity) {
    $entity->applicant[] = [
      'first_name' => 'Sally',
      'surname' => 'Samson',
      'resume' => [
        'uri' => 'http://example.com/sally.samson',
        'title' => 'My resume',
      ],
      'bio' => [
        'value' => '<p>Hi there</p>',
        'format' => 'filtered_html',
      ],
    ];
    $item = $entity->get('applicant')->get(1);
    $this->assertEquals('Sally', $item->first_name->value);
    $this->assertEquals('Samson', $item->surname->value);
    $this->assertEquals('http://example.com/sally.samson', $item->resume->uri);
    $this->assertEquals('My resume', $item->resume->title);
    $this->assertEquals('<p>Hi there</p>', $item->bio->value);
    $this->assertEquals('filtered_html', $item->bio->format);
    $this->assertEquals('<p>Hi there</p>', $item->bio->processed);
    return $item;
  }

  /**
   * Tests updating item properties using the proxy properties.
   *
   * @param \Drupal\field_union\Plugin\Field\FieldType\FieldUnion $item
   *   Item to update.
   */
  protected function assertUpdatingFieldValuesViaProxies(FieldUnionItem $item) {
    $item->first_name = 'Sally-Ann';
    $item->surname->value = 'Samson-Smith';
    $this->assertEquals('Sally-Ann', $item->first_name->value);
    $this->assertEquals('Samson-Smith', $item->surname->value);
    $this->assertEquals([
      'first_name__value' => 'Sally-Ann',
      'surname__value' => 'Samson-Smith',
      'resume__uri' => 'http://example.com/sally.samson',
      'resume__title' => 'My resume',
      'resume__options' => [],
      'bio__value' => '<p>Hi there</p>',
      'bio__format' => 'filtered_html',
    ], $item->getValue());
  }

  /**
   * Test unsetting works.
   *
   * @param \Drupal\field_union\Plugin\Field\FieldType\FieldUnion $item
   *   Field union item to update.
   */
  protected function assertUnsettingFieldValuesViaProxies(FieldUnionItem $item) {
    $item->first_name = NULL;
    $item->surname->value = NULL;
    $values = [
      'resume__uri' => 'http://example.com/sally.samson',
      'resume__title' => 'My resume',
      'bio__value' => '<p>Hi there</p>',
      'bio__format' => 'filtered_html',
    ];
    $this->assertEquals($values, array_filter($item->getValue()));

    // Link field has multiple properties, but also a main property name.
    // Setting the field proxy to null should set the uri to null, but not the
    // title (this is how LinkItem works).
    $item->resume = NULL;
    $this->assertEquals([
      'bio__value' => '<p>Hi there</p>',
      'resume__title' => 'My resume',
      'bio__format' => 'filtered_html',
    ], array_filter($item->getValue()));
  }

  /**
   * Tests isEmpty functionality.
   */
  public function testIsEmpty() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = EntityTest::create([
      'name' => 'entity',
    ]);
    $this->assertTrue($entity->get('applicant')->isEmpty());
    $entity = EntityTest::create([
      'name' => 'entity',
      'applicant' => [
        'first_name' => 'Jerry',
        'resume' => [
          'uri' => 'http://example.com/jerry.johnson',
        ],
      ],
    ]);
    $this->assertFalse($entity->get('applicant')->isEmpty());
  }

  /**
   * Tests generateSampleValues functionality.
   */
  public function testGenerateSampleValues() {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('entity_test',
        'entity_test');
    $values = FieldUnionFieldType::generateSampleValue($field_definitions['applicant']);
    $this->assertNotEmpty($values);
    $this->assertArrayHasKey('first_name', $values);
    $this->assertArrayHasKey('value', $values['first_name']);
    $this->assertArrayHasKey('bio', $values);
    $this->assertArrayHasKey('value', $values['bio']);
    $this->assertArrayHasKey('resume', $values);
    $this->assertArrayHasKey('uri', $values['resume']);
  }

}
