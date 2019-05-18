<?php

namespace Drupal\Tests\form_alter_service\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\form_alter_service\Form\FormAlter;

/**
 * Testing the service within a compiled container.
 *
 * @group form_alter_service
 */
class KernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'form_alter_service',
    'form_alter_service_test',
  ];

  /**
   * Tests that ordering of handlers execution is appropriate.
   */
  public function testHandlersPrioritisation() {
    $form_alter = $this->container->get(FormAlter::SERVICE_ID);

    $this->assertAttributeCount(2, 'services', $form_alter);
    $this->assertCount(2, $form_alter->getServices('node_form'));
    $this->assertCount(1, $form_alter->getServices('views_form_user_admin_people_page_1'));

    $handlers = $this->readAttribute($this->container->get('form_alter.node_form'), 'handlers');

    $this->assertFalse(empty($handlers['#validate']));
    $this->assertFalse(empty($handlers['#submit']));

    $this->assertSame($handlers['#validate']['prepend'], [
      [-5, 'validateThird'],
      [0, 'validateSecond'],
      [10, 'validateFirst'],
    ]);
  }

  /**
   * Tests that "hasMatch()" method can block "alterForm()" from being called.
   */
  public function testHasMatchAndHandlersPopulation() {
    $this->enableModules(['user', 'node', 'system']);
    $this->installConfig(['system']);
    $this->installSchema('system', ['sequences']);

    foreach ([
      'node' => ['node_access'],
      'user' => ['users_data'],
    ] as $module => $tables) {
      $this->installEntitySchema($module);
      $this->installSchema($module, $tables);
    }

    $form_alter = $this->container->get(FormAlter::SERVICE_ID);
    $services = $form_alter->getServices('node_form');

    foreach ($services as $i => $service) {
      $mock = $this
        ->getMockBuilder(get_class($service))
        ->setConstructorArgs(['node_form'])
        ->setMethods(['hasMatch'])
        ->getMock();

      $mock
        ->expects(static::once())
        ->method('hasMatch')
        ->willReturn(TRUE);

      // Copy handlers into mocked service since "setHandlers()" method is
      // final and cannot be mocked.
      $mock->setHandlers($service->getHandlers());

      $services[$i] = $mock;
    }

    $property = new \ReflectionProperty($form_alter, 'services');
    $property->setAccessible(TRUE);
    $property->setValue($form_alter, ['node_form' => $services]);
    $property->setAccessible(FALSE);

    $this->container
      ->get('form_builder')
      // Update the form alter with mocked services.
      /* @see \Drupal\form_alter_service\Form\FormBuilder::setFormAlter() */
      ->setFormAlter($form_alter);

    $entity_manager = $this->container->get('entity_type.manager');
    $node_type_storage = $entity_manager->getStorage('node_type');
    $user_storage = $entity_manager->getStorage('user');
    $node_storage = $entity_manager->getStorage('node');

    $node_type = $node_type_storage->create([
      'name' => 'Page',
      'type' => 'page',
    ]);

    $node_type_storage->save($node_type);

    $user = $user_storage->create([
      'name' => $this->randomString(),
    ]);

    $user_storage->save($user);

    $node = $node_storage->create([
      'uid' => $user->id(),
      'type' => $node_type->id(),
      'title' => 'Test',
    ]);

    $node_storage->save($node);

    $form = $this->container
      ->get('entity.form_builder')
      ->getForm($node);

    /* @see \Drupal\form_alter_service_test\NodeFormAlterTest::alterForm() */
    $this->assertSame('', $form['markup']['#markup']);
    /* @see \Drupal\form_alter_service_test\NodeFormAlter2Test::alterForm() */
    $this->assertFalse(empty($form['dummy']));
    $this->assertFalse(empty($form['#submit']));
    $this->assertFalse(empty($form['#validate']));
  }

}
