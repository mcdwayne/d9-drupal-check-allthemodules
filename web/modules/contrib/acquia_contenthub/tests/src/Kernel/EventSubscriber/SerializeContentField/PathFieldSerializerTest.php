<?php

namespace Drupal\Tests\acquia_contenthub\Kernel\EventSubscriber\SerializeContentField;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\path\Plugin\Field\FieldType\PathFieldItemList;

/**
 * Class PathFieldSerializerTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 *
 * @covers \Drupal\acquia_contenthub\EventSubscriber\SerializeContentField\PathFieldSerializer
 */
class PathFieldSerializerTest extends EntityKernelTestBase {

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $adminSettings;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_test',
    'node',
    'language',
    'content_translation',
    'path',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');

    // Enable two additional languages.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('hu')->save();

    $this->adminSettings = \Drupal::configFactory()
      ->getEditable('acquia_contenthub.admin_settings');

    $this
      ->adminSettings
      ->set('client_name', 'test-client')
      ->set('origin', '00000000-0000-0001-0000-123456789123')
      ->set('api_key', 'kZvJl17RyLUhIOCdssssshm5j')
      ->set('secret_key', 'Sv6KgchlGWNgxBqFls123213MkmVwklnuOK2pIimlXss23123Xl')
      ->set('hostname', 'https://dev-euc1.content-hub.acquia.dev')
      ->set('shared_secret', '12312321312321')
      ->save();

    $this->dispatcher = $this->container->get('event_dispatcher');
  }

  /**
   * Tests the serialization of the path field.
   *
   * @param array $languages
   *   Data for create node with translations.
   * @param array $expected
   *   Excepted data for assertion.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider settingsDataProvider
   */
  public function testPathFieldSerialization(array $languages, array $expected) {

    /** @var \Drupal\acquia_contenthub\Client\ClientFactory $clientFactory */
    $clientFactory = \Drupal::service('acquia_contenthub.client.factory');
    $settings = $clientFactory->getClient()->getSettings();

    $entity = Node::create([
      'title' => 'Test node',
      'type' => 'article',
      'tnid' => 0,
      'langcode' => 'en',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => 1,
    ]);
    $entity->save();

    $this->addTranslationAndAlias($entity, $languages);

    $cdf = new CDFObject('drupal8_content_entity', $entity->uuid(), date('c'), date('c'), $settings->getUuid());

    foreach ($entity as $fieldName => $field) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field */
      if ($field instanceof PathFieldItemList) {
        $event = new SerializeCdfEntityFieldEvent($entity, $fieldName, $field, $cdf);

        $this->dispatcher->dispatch(AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD, $event);

        // Check propagationStopped property is changed.
        $this->assertTrue($event->isPropagationStopped());
        // Check expected output after path field serialization.
        $this->assertEquals($expected, $event->getFieldData());
      }
    }
  }

  /**
   * Provides sample data for client's settings and expected data for assertion.
   *
   * @return array
   *   Settings.
   */
  public function settingsDataProvider() {
    return [
      [
        [
          'hu' => 'hu',
          'de' => 'de',
        ],
        [
          'value' => [
            'en' => [
              'langcode' => 'en',
            ],
            'de' => [
              'alias' => '/path_de',
              'source' => '',
              'pid' => '',
            ],
            'hu' => [
              'alias' => '/path_hu',
              'source' => '',
              'pid' => '',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Add translation and path aliases for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $node
   *   Base entity.
   * @param array $languages
   *   Translation languages.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addTranslationAndAlias(ContentEntityInterface $node, array $languages) {
    foreach ($languages as $language) {
      $translation = $node->addTranslation($language);
      \Drupal::service('path.alias_storage')->save('/node/' . $translation->id(), '/' . $language, $language);
      $translation->title = 'test.' . $language;
      $translation->path = '/path_' . $language;
      $translation->save();
    }
  }

}
