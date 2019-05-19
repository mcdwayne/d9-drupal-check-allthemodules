<?php

namespace Drupal\Tests\streamy\Functional;

use Drupal\streamy\StreamWrapper\FlySystemHelper;

/**
 * Tests the registered Streamy schemes and the main Streamy configuration
 * by also installing a local test plugin.
 *
 * @group streamy
 */
class SchemeTest extends StreamyFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy', 'node', 'entity_test'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   *
   */
  public function testDefaultStreamySchemesAreAvailable() {
    $enabledSchemes = \Drupal::service('stream_wrapper_manager')->getNames();
    $schemesToCheck = ['streamy', 'streamypvt'];

    foreach ($schemesToCheck as $scheme) {
      $this->assertTrue(array_key_exists($scheme, $enabledSchemes), 'Scheme "' . $scheme . '" is correctly enabled');
    }
  }

  /**
   *
   */
  public function testEnabledSchemasAndFakeOnes() {
    $this->setStreamyConfiguration();
    $streamyFactory = \Drupal::service('streamy.factory');
    $enabledSchemes = $streamyFactory->getSchemes();

    // Trying to initialise correct schemes
    foreach ($enabledSchemes as $scheme) {
      $fileSystem = $streamyFactory->getFilesystem($scheme);
      $this->assertTrue($fileSystem instanceof FlySystemHelper, 'Scheme "' . $scheme . '" is of the correct type');
    }

    // Trying to initialise wrong schemes
    $fakeSchemes = ['dream', 'chocolate', 'pizza', 'labam'];
    foreach ($fakeSchemes as $scheme) {
      $fileSystem = $streamyFactory->getFilesystem($scheme);
      $this->assertFalse($fileSystem instanceof FlySystemHelper, 'Scheme "' . $scheme . '" is not of the correct type');
    }
  }

  /**
   * Test with wrong and correct settings streamy and streamypvt schemes.
   */
  public function testSetSteramyAsDefaultDownloadStream() {
    $this->drupalLogin($this->user);
    // Go to the page file system
    // Check there is no error message from Streamy
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()
         ->responseNotContains('is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');
    $this->assertSession()->responseNotContains('please verify your settings and try again. Refer to the log system for further information.');

    // Trying to set streamy as default download schema, this should fail, we didn't configure it
    $scheme = 'streamy';
    $edit = [
      "file_default_scheme" => $scheme,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->assertSession()->pageTextContains('The selected stream ' . $scheme .
                                             ' is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');

    // Enabling the stream with no options so it should generate a further error message
    $schemes = [
      'streamy'    => [
        'master'  => '',
        'slave' => '',
        'cdn_wrapper'   => '',
        'enabled'       => 1,
      ],
      'streamypvt' => [
        'master'  => '',
        'slave' => '',
        'cdn_wrapper'   => '',
        'enabled'       => 1,
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $schemes)
           ->save();

    $this->drupalGet('admin/config/media/file-system');
    $edit = [
      "file_default_scheme" => $scheme,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->assertSession()->pageTextContains('The selected stream ' . $scheme .
                                             ' is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');
    $this->assertSession()->pageTextContains($scheme .
                                             '://), please verify your settings and try again. Refer to the log system for further information.');
    //

    // Trying to set streamypvt also as default download schema, this should fail, we didn't configure it
    $scheme = 'streamypvt';
    $this->drupalGet('admin/config/media/file-system');
    $edit = [
      "file_default_scheme" => $scheme,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The selected stream ' . $scheme .
                      ' is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');
    $this->assertSession()->pageTextContains($scheme . '://), please verify your settings and try again. Refer to the log system for further information.');

    // Now let's configure it and test again, everything should work
    $this->setStreamyConfiguration();
    $this->drupalGet('admin/config/media/file-system');
    $scheme = 'streamy';
    $edit = [
      "file_default_scheme" => $scheme,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->assertSession()->pageTextNotContains('The selected stream ' . $scheme .
                                                ' is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');
    $this->assertSession()->pageTextNotContains($scheme .
                                                '://), please verify your settings and try again. Refer to the log system for further information.');

    // Testing streamypvt that should also work
    $scheme = 'streamypvt';
    $this->drupalGet('admin/config/media/file-system');
    $edit = [
      "file_default_scheme" => $scheme,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextNotContains('The selected stream ' . $scheme .
                                                ' is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');
    $this->assertSession()->pageTextNotContains($scheme .
                                                '://), please verify your settings and try again. Refer to the log system for further information.');
  }

}
