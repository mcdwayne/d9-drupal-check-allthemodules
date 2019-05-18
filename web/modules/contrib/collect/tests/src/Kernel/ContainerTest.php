<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\CollectStorageInterface;
use Drupal\collect\Entity\Container;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests create, read, update and delete of the container.
 *
 * @group collect
 */
class ContainerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'hal',
    'rest',
    'datetime',
    'collect',
    'serialization',
    'system',
    'views',
    'user',
    'collect_common',
    // Enabling config_translation is an easy way of testing link templates.
    'config_translation',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['collect']);
    $this->installEntitySchema('collect_container');
  }

  /**
   * Tests the container basic operations.
   */
  public function testContainerOperations() {
    $date = REQUEST_TIME + rand(-3600, 3600);
    /* @var \Drupal\collect\Entity\Container $container */
    $container = Container::create();

    $this->assertEqual(REQUEST_TIME, $container->getDate(), 'New containers hav request time set as date.');

    $container->setOriginUri('https://drupal.org/project/collect/test/1');
    $container->setDate($date);
    $container->setData('Morbi leo risus, porta ac consectetur ac, vestibulum at eros.');
    $container->setType('text/plain');
    $this->assertEqual(SAVED_NEW, $container->save(), t('Saved new container object.'));

    /* @var \Drupal\collect\Entity\Container $container_loaded */
    $container_loaded = Container::load($container->id());
    $this->assertNotNull($container_loaded, t('Successfully loaded container.'));
    $this->assertEqual('https://drupal.org/project/collect/test/1', $container_loaded->getOriginUri(), t('Got expected origin UIR'));
    $this->assertEqual($date, $container_loaded->getDate(), t('Got expected date'));
    $this->assertEqual('text/plain', $container_loaded->getType(), t('Got expected mime type'));
    $this->assertEqual('Morbi leo risus, porta ac consectetur ac, vestibulum at eros.', $container_loaded->getData(), t('Got expected data'));

    $container->delete();
    $container_loaded = Container::load($container->id());
    $this->assertNull($container_loaded, t('Container deleted'));
  }

  /**
   * Tests persistence and retrieval of binary data.
   */
  public function testBinaryData() {
    $data = base64_decode(
<<<EOL
a9uoXlTnK4+QUKj4h1gm+7TtnH6mUF/4Rd6hRJUMAWkon58rk7bKG91QtwPojRmt
W1MeANNYcPDSj7ux6HDYZAWnx6yMSPf6YgWkzcgezBzsVIA7HSMDBg2lCU+yynfc
XEhBxWSw78npLzRFmud2sRq5qqD2cwqCkkzX88oLnhzr0iSmZCesQmH+eWhnFk2Y
9SMxpB2r9BbXeRnHo3SRqZ79lA4vb9dkyef+tBxmk0zRrciYbunCUfGTsCjm6I0b
J1s7Qlt8AuC5DJd3H0ixRxpF6mhGqzg8fX9bP1kcMGYo/Y5CnedGy4DBexCtpEe1
W0RLDblW7RMjm5/pdr6WzWdMBSlBgh+Twlz6LINQa6oLZpOzfL5WVvC5cjfO5Rys
KrmO3AzRtVRl/0v1Je1PkoKXsZo5TmV7TCjymBVQRpYxOGICJi6NkwWs0DQtzhNi
+WKzXxwmQ7Xdv9Z1if4n5lXgbe7toYbfQzxtOYDl1IdzAfnUikXypGYPo2GvOv1C
wQddrUVB5IQAgoeZ6BuNVuNXdzIagWdJ6sP7LWMfOe+jflp98qhQ1ucUj2d+F823
c7tuMkN42f90sLGxtFrQsK1tS7fYnr8QP6+82f5zgAzX1pbI7MFKibUmCByWLMPk
UN3/7dSoKkIaw8TpOFy/QWOAe7yeE6S0kWApws5FYxk=
EOL
    );
    /* @var \Drupal\collect\Entity\Container $container */
    $container = Container::create(array(
      'origin_uri' => 'https://drupal.org/project/collect/test/1',
      'data' => $data,
      'type' => 'text/plain',
    ));
    $container->save();

    /* @var \Drupal\collect\Entity\Container $container_loaded */
    $container_loaded = Container::load($container->id());
    $this->assertEqual($data, $container_loaded->getData(), t('Got expected origin URI'));
    $this->assertEqual('text/plain', $container_loaded->getType(), t('Got text plain type.'));
  }

  /**
   * Tests the container entity storage.
   */
  public function testStorage() {
    /** @var \Drupal\collect\CollectStorageInterface $storage */
    $storage = \Drupal::service('entity.manager')->getStorage('collect_container');
    $this->assertTrue($storage instanceof CollectStorageInterface);
    $ids = array();
    foreach (['a', 'aa', 'b', 'bb'] as $uri) {
      $container = Container::create(['schema_uri' => $uri]);
      $container->save();
      $ids[$uri] = $container->id();
    }
    // Find containers with schema URIs matching 'a' or 'b', skip the first and
    // pick two.
    $returned_ids = array_values($storage->getIdsByUriPatterns(['a', 'b'], 2, 1));
    $this->assertEqual([$ids['aa'], $ids['b']], $returned_ids);
  }

}
