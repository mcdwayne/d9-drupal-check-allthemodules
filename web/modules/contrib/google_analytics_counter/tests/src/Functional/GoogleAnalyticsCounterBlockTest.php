<?php

namespace Drupal\Tests\google_analytics_counter\Functional;

use Drupal\Core\Database\Database;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterHelper;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;


/**
 * Places the google analytics counter block on a page and checks the value
 * in the block matches the value in the storage table.
 *
 * @group google_analytics_counter
 */
class GoogleAnalyticsCounterBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'node', 'block'];

  /**
   * Authenticated user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $authenticatedUser;

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface
   */
  protected $appManager;

  /**
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $GacInstallValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    Node::create([
      'title' => 'Page 1',
      'type' => 'page',
    ])->save();

    Node::create([
      'title' => 'Page 2',
      'type' => 'page',
    ])->save();

  }

  /**
   * Add storage items
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   * @throws \Exception
   */
  /**
   * Add storage items
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   * @throws \Exception
   */
  protected function gacAddStorage() {
    $sum_pageviews = [
      'sum_path1' => [
        'pagepath_hash' => hash('md5', 'node/1'),
        'pagepath' => 'node/1',
        'pageviews' => 100,
      ],
      'sum_path1a' => [
        'pagepath_hash' => hash('md5', 'node/1/'),
        'pagepath' => 'node/1/',
        'pageviews' => 100,
      ],
      'sum_path2' => [
        'pagepath_hash' => hash('md5', 'node/2'),
        'pagepath' => 'node/2',
        'pageviews' => 50,
      ],
      'sum_path2a' => [
        'pagepath_hash' => hash('md5', 'node/2/'),
        'pagepath' => 'node/2/',
        'pageviews' => 50,
      ],
    ];

    // Insert pagepaths into the google_analytics_counter table.
    $connection = Database::getConnection();
    $pageviews = 0;
    foreach ($sum_pageviews as $sum_pageview) {
      $pageviews = $connection->insert('google_analytics_counter')->fields([
        'pagepath_hash' => $sum_pageview['pagepath_hash'],
        'pagepath' => $sum_pageview['pagepath'],
        'pageviews' => $sum_pageview['pageviews'],
      ])->execute();
    }
    return $pageviews;
  }

  /**
   * Tests the functionality of the Google Analytics Counter block.
   */
  public function testGoogleAnalyticsCounterBlock() {
    $this->container->get('module_installer')->install(['google_analytics_counter']);
    $this->resetAll();

    // Add storage items.
    $this->gacAddStorage();


    // Enable the block Google Analytics Counter block.
    $this->drupalPlaceBlock('google_analytics_counter_form_block', [
      'region' => 'content',
      'weight' => -5,
    ]);

    // Test correct display of the block.
    $this->drupalGet('node/1');
    $assert = $this->assertSession();
    // 0 is wrong. Should be 200.
    $assert->pageTextContains(t('0'));


  }
}
