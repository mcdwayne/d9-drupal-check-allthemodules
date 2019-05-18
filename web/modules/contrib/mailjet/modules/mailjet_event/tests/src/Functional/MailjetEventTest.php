<?php

namespace Drupal\Tests\mailjet_event\Functional;

use Drupal\Tests\BrowserTestBase;


/**
 * Tests core campaign functionality.
 *
 * @group mailjet
 */
class MailjetEventTest extends BrowserTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mailjet', 'mailjet_event'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();

    $json = '{
    "event": "unsub",
    "time": 1510665512,
    "MessageID": 20829151068018510,
    "email": "exampletestemail@domain.com",
    "mj_campaign_id": 5553978046,
    "mj_contact_id": 1821865074,
    "customcampaign": "mj.nl=3981828",
    "mj_list_id": 1716755,
    "ip": "82.103.125.33",
    "geo": "",
    "agent": ""
}';

    $event = json_decode($json);

    $event_data = [
      'event_id' => '1000',
      'event_field' => $event,
      'event_type' => $event->event,

    ];

    $event_en = \Drupal::entityManager()
      ->getStorage('event_entity')
      ->create($event_data);

    $event_en->save();

  }


  /**
   * Tests retrieval of a specific campaign.
   */
  public function testGetEvent() {
    $id = '1000';

    $campaign = $entity_manager->getStorage('campaign_entity')->load($id);


    $this->assertTrue(is_object($campaign), 'Tested retrieval of campaign data.');

    $this->assertEqual($campaign->id, $id);
    $this->assertEqual($campaign->event_type, 'unsub');

  }

}

