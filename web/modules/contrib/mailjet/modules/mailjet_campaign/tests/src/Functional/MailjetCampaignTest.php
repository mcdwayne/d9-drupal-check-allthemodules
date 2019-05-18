<?php

namespace Drupal\Tests\mailjet_campaign\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Tests core campaign functionality.
 *
 * @group mailjet
 */
class MailjetCampaignTest extends BrowserTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mailjet', 'mailjet_campaign'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();

    $campaign_data = [
      'name' => 'Test campaign',
      'camp_id_mailjet' => '1111112e1',
      'order_id' => '91911',
    ];

    $campaign = \Drupal::entityManager()
      ->getStorage('campaign_entity')
      ->create($campaign_data);

    $campaign->save();

  }


  /**
   * Tests retrieval of a specific campaign.
   */
  public function testGetCampaign() {
    $campaign_id = '1111112e1';

    $query = \Drupal::database()->select('mailjet_campign', 'mj');
    $query->addField('mj', 'campaign_id');
    $query->condition('mj.camp_id_mailjet', trim($campaign_id));
    $query->range(0, 1);
    $id = $query->execute()->fetchField();

    $campaign = \Drupal::entityManager()->getStorage('campaign_entity')->load($id);
    $this->assertTrue(is_object($campaign), 'Tested retrieval of campaign data.');

    $this->assertEqual($campaign->id, $campaign_id);
    $this->assertEqual($campaign->name, 'Test campaign');
    $this->assertEqual($campaign->order_id, '91911');

  }

}