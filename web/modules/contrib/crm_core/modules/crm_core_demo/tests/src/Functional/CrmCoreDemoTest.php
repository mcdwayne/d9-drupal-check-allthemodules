<?php

namespace Drupal\Tests\crm_core_demo\Functional;

use Drupal\crm_core_activity\Entity\ActivityType;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\crm_core_contact\Entity\OrganizationType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests CRM core demo.
 *
 * @group crm_core_demo
 */
class CrmCoreDemoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'dynamic_entity_reference',
    'crm_core',
    'crm_core_contact',
    'crm_core_activity',
    'crm_core_demo',
  ];

  /**
   * Tests default configuration.
   */
  public function testDefaultConfiguration() {
    $individual = IndividualType::load('customer');
    $this->assertEquals('customer', $individual->id(), 'Correct ID for the customer found.');
    $this->assertEquals('Customer', $individual->label(), 'Correct label for the customer found.');
    $this->assertEquals('A single customer.', $individual->getDescription(), 'Correct description for the customer found.');

    $household = OrganizationType::load('household');
    $this->assertEquals('household', $household->id(), 'Correct ID for the household found.');
    $this->assertEquals('Household', $household->label(), 'Correct label for the household found.');
    $this->assertEquals('A collection of individuals generally located at the same residence.', $household->getDescription(), 'Correct description for the supplier found.');

    $supplier = OrganizationType::load('supplier');
    $this->assertEquals('supplier', $supplier->id(), 'Correct ID for the supplier found.');
    $this->assertEquals('Supplier', $supplier->label(), 'Correct label for the supplier found.');
    $this->assertEquals('A person or company that supplies goods or services.', $supplier->getDescription(), 'Correct description for the supplier found.');

    $meeting = ActivityType::load('meeting');
    $this->assertEquals('meeting', $meeting->id(), 'Correct ID for the meeting found.');
    $this->assertEquals('Meeting', $meeting->label(), 'Correct label for the meeting found.');
    $this->assertEquals('A meeting between 2 or more contacts.', $meeting->description, 'Correct description for the meeting found.');

    $phone_call = ActivityType::load('phone_call');
    $this->assertEquals('phone_call', $phone_call->id(), 'Correct ID for the phone call found.');
    $this->assertEquals('Phone call', $phone_call->label(), 'Correct label for the phone call found.');
    $this->assertEquals('A phone call between 2 or more contacts.', $phone_call->description, 'Correct description for the meeting found.');
  }

}
