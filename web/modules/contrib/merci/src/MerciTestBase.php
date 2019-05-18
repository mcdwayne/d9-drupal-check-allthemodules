<?php
/**
 * @file
 * Contains \Drupal\merci\MerciTestBase.
 */

namespace Drupal\merci;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\Node;
use Drupal\merci_line_item\Entity\MerciLineItem;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests pager functionality.
 *
 */
class MerciTestBase extends WebTestBase {
  use TaxonomyTestTrait;
  protected $strictConfigSchema = FALSE;
  public $admin_user;
  public $term;


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('merci_reservation', 'field_ui', 'views_ui');

  function setUp() {
    // Enable the module.
    parent::setUp();

    // Create admin user. 
    $this->admin_user = $this->createUser(array(
      'administer nodes', // Required to set revision checkbox
      'administer views',
      'bypass node access',
      'administer content types',
      'access administration pages',
      'administer site configuration',
      'administer node fields',
    ));
    // Login the admin user.
    $this->drupalLogin($this->admin_user);

  }

  function merciCreateNode($type, $settings = NULL, $pass = TRUE) {
    $settings += array(
      'title' => $this->randomString(),
    );
    $node = Node::Create($settings);
    $node->save();
    return $node;

  }

  function merciCreateItem($merci_type, $type = NULL, $merci_settings = array()) {

    $type = $type ? $type : $merci_type;
    $settings = array (
      'type'  => $type,
    );
    $type = $this->merciCreateContentType($settings, $merci_type, $merci_settings);

    $item = $this->merciCreateNode($type, $settings);

    return $item;

  }

  function merciCreateContentType($settings, $merci_type, $merci_settings=NULL) {
    // Create resource content type
    // Disable the rating for this content type: 0 for Disabled, 1 for Enabled.
    if (node_type_load($settings['type'])) {
      return $settings['type'];
    }
    $content_type = $this->createContentType($settings);
    $this->verbose('settings ' . var_export($content_type, TRUE));
    $type = $content_type->get('type');
    $settings = array(
      'merci_type_setting' => $merci_type,
      'merci_max_hours_per_reservation' => 5,
    );
    if ($merci_settings) {
      $settings = $settings + $merci_settings;
    }
    return $type;

  }

  function merciCreateReservation($start_time, $end_time, $settings = array()) {
    // Test open.
    $start = new DrupalDateTime($start_time);
    $end = new DrupalDateTime($end_time);
    $start->setTimeZone(timezone_open('UTC'));
    $end->setTimeZone(timezone_open('UTC'));
    $date_start = $start->format('Y-m-d\TH:i:s');
    $date_end   = $end->format('Y-m-d\TH:i:s');

    $default_settings = array(
      'title' => $this->randomString(),
      'type' => 'merci_reservation',
      'merci_reservation_items' => array(
        'target_id' => $this->resource1->id(),
      ),
      'merci_reservation_date' => array(
        'value' => $date_start,
        'end_value' => $date_end,
      ),
    );

    $settings = array_merge($default_settings, $settings);

    $node = MerciLineItem::create($settings);

    return $node;
  }

}
