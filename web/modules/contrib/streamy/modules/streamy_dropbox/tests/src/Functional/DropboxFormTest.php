<?php

namespace Drupal\Tests\streamy_dropbox\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Dropbox form behaviors.
 *
 * @group streamy_dropbox
 */
class DropboxForm extends BrowserTestBase {

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy_dropbox'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy dropbox']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests if the form can display settings that have been
   * programmatically set through the configuration service.
   */
  public function testLocalInverseFormBehaviors() {
    // Going to the page first
    $this->drupalGet('/admin/config/media/file-system/streamy/streams/dropbox');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldValueEquals("streamy[master][accesstoken]", ''); // accesstoken element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][secret]", ''); // secret element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][prefix]", ''); // prefix element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy-master' . '-slow-stream', FALSE); // slow_stream checkbox has the correct value

    $this->assertSession()->fieldValueEquals("streamypvt[master][accesstoken]", ''); // accesstoken element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][secret]", ''); // secret element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][prefix]", ''); // prefix element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt-master' . '-slow-stream', FALSE); // slow_stream checkbox has the correct value

    // Checking the config
    $config = \Drupal::configFactory()->get('streamy_dropbox.dropbox')->get('plugin_configuration');
    self::assertTrue(empty($config), 'config is empty');

    $pluginConfig = [
      'streamy'    => [
        'master' => [
          'accesstoken' => 'abcde',
          'secret'      => 'fghilm',
          'prefix'      => 'nopqrst',
          'slow_stream' => TRUE,
        ],
      ],
      'streamypvt' => [
        'master' => [
          'accesstoken' => 'hyhyh',
          'secret'      => 'aaddd',
          'prefix'      => 'ghf',
          'slow_stream' => TRUE,
        ],
      ],
    ];

    // Saving the configuration
    \Drupal::configFactory()->getEditable('streamy_dropbox.dropbox')
           ->set('plugin_configuration', $pluginConfig)
           ->save();

    $this->drupalGet('/admin/config/media/file-system/streamy/streams/dropbox');
    $this->assertSession()->statusCodeEquals(200);

    // Checking that the form actually displays the values previously set
    $this->assertSession()->fieldValueEquals("streamy[master][accesstoken]", 'abcde'); // 'accesstoken element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][secret]", 'fghilm'); // 'secret element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][prefix]", 'nopqrst'); // 'prefix element contains the correct value
    $this->assertSession()->fieldValueEquals("streamy[master][slow_stream]", TRUE); // 'slow_stream element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamy-master' . '-slow-stream', TRUE); // 'slow_stream checkbox has the correct value

    $this->assertSession()->fieldValueEquals("streamypvt[master][accesstoken]", 'hyhyh'); // 'accesstoken element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][secret]", 'aaddd'); // 'secret element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][prefix]", 'ghf'); // 'prefix element contains the correct value
    $this->assertSession()->fieldValueEquals("streamypvt[master][slow_stream]", TRUE); // 'slow_stream element contains the correct value
    $this->assertSession()->fieldValueEquals('edit-' . 'streamypvt-master' . '-slow-stream', TRUE); // 'slow_stream checkbox has the correct value

  }

}
