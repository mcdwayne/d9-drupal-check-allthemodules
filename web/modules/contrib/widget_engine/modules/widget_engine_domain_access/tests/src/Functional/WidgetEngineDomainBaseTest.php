<?php

namespace Drupal\Tests\widget_engine_domain_access\Functional;

use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;
use Drupal\Tests\widget_engine\Traits\WidgetTypeCreationTrait;
use Drupal\widget_engine\Entity\Widget;

/**
 * @group widget_engine_domain_access
 */
class WidgetEngineDomainBaseTest extends BrowserTestBase {

  use WidgetTypeCreationTrait;
  use DomainTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'field',
    'text',
    'image',
    'user',
    'node',
    'language',
    'domain',
    'domain_access',
    'widget_engine',
    'widget_engine_domain_access',
  ];

  /**
   * Base widget ID.
   *
   * @var string
   */
  private $widgetType = 'test_widget';

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   */
  public $base_hostname;

  /**
   * Array of Widget entities.
   *
   * @var array
   */
  public $widgets;

  /**
   * Array of User entities.
   *
   * @var array
   */
  public $users;

  /**
   * Array of Domain entities.
   *
   * @var array
   */
  public $domains;

  /**
   * Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  public $negotiator;

  /**
   * Widget access control handler.
   *
   * @var EntityAccessControlHandlerInterface
   */
  public $controlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->domainCreateTestDomains(2);
    $this->createWidgeType(['type' => $this->widgetType]);
    $this->domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    /** @var \Drupal\domain\DomainNegotiatorInterface $negotiator */
    $this->negotiator = \Drupal::service('domain.negotiator');
    $this->controlHandler = \Drupal::entityTypeManager()->getAccessControlHandler('widget');
    foreach ($this->domains as $domain) {
      $domain_id = $domain->id();
      // Create user assigned to certain domain.
      $user = $this->createUser([
        'edit domain widgets',
        'create domain widgets',
        'save widgets on any assigned domain',
        'delete domain widgets',
        'view published widget entities',
        'create ' . $this->widgetType . ' widget on assigned domains',
        'update ' . $this->widgetType . ' widget on assigned domains',
        'delete ' . $this->widgetType . ' widget on assigned domains',
      ]);
      $user->set(DOMAIN_ACCESS_FIELD, [$domain_id]);
      $user->save();
      $this->users[$domain_id] = $user;

      // Create widget assigned to certain domain.
      $widget = Widget::create([
        'type' => $this->widgetType,
        'text' => 'test' . $domain_id,
        DOMAIN_ACCESS_FIELD => [$domain_id],
      ]);
      $widget->save();
      $this->widgets[$domain_id] = $widget;
    }
  }

  /**
   * Base user access tests.
   */
  public function testDomainAccess() {
    $this->negotiator->setActiveDomain($this->domains['example_com']);
    $create = $this->controlHandler->createAccess($this->widgetType, $this->users['example_com']);
    $view = $this->controlHandler->access($this->widgets['example_com'], 'view', $this->users['example_com']);
    $view_label = $this->controlHandler->access($this->widgets['example_com'], 'view label', $this->users['example_com']);
    $update = $this->controlHandler->access($this->widgets['example_com'], 'update', $this->users['example_com']);
    $delete = $this->controlHandler->access($this->widgets['example_com'], 'delete', $this->users['example_com']);
    $this->assertTrue($create, 'User with proper permission can create widgets');
    $this->assertTrue($view, 'User with proper permission can view widgets');
    $this->assertTrue($view_label, 'User with proper permission can view widgets labels');
    $this->assertTrue($update, 'User with proper permission can update widgets');
    $this->assertTrue($delete, 'User with proper permission can delete widgets');

    $create = $this->controlHandler->createAccess($this->widgetType, $this->users['one_example_com']);
    $view = $this->controlHandler->access($this->widgets['one_example_com'], 'view', $this->users['example_com']);
    $view_label = $this->controlHandler->access($this->widgets['one_example_com'], 'view label', $this->users['example_com']);
    $update = $this->controlHandler->access($this->widgets['one_example_com'], 'update', $this->users['example_com']);
    $delete = $this->controlHandler->access($this->widgets['one_example_com'], 'delete', $this->users['example_com']);
    $this->assertFalse($create, 'User without proper permission can\'t create widgets');
    $this->assertTrue($view, 'User with proper permission can view widgets');
    $this->assertTrue($view_label, 'User with proper permission can view widgets labels');
    $this->assertFalse($update, 'User without proper permission can\'t update widgets');
    $this->assertFalse($delete, 'User without proper permission can\'t delete widgets');
  }

}
