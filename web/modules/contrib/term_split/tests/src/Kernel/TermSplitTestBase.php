<?php

namespace Drupal\Tests\term_split\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

class TermSplitTestBase extends KernelTestBase {

  use TaxonomyTestTrait;
  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'field',
    'node',
    'taxonomy',
    'term_reference_change',
    'term_split',
    'text',
    'user',
    'system',
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * @var \Drupal\taxonomy\TermStorage
   */
  protected $termStorage;

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->setUpContentType();

    $this->setUpPrivateTempStore();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->vocabulary = $this->createVocabulary();
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
  }

  protected function setUpContentType() {
    $bundle = 'page';
    $this->createContentType([
      'type' => $bundle,
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    $entityType = 'node';
    $fieldName = 'field_terms';
    $fieldLabel = 'Terms';
    $targetEntityType = 'taxonomy_term';
    $this->createEntityReferenceField($entityType, $bundle, $fieldName, $fieldLabel, $targetEntityType);
  }

  protected function setUpPrivateTempStore(): void {
    $accountProxy = new AccountProxy();
    $account = self::getMock(AccountInterface::class);
    $account->method('id')->willReturn(24);
    $this->container->get('current_user')->setAccount($account);
    $this->privateTempStore = $this->container->get('user.private_tempstore');
  }

}
