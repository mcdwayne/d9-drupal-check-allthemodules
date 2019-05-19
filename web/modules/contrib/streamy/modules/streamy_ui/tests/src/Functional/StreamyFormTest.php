<?php

namespace Drupal\Tests\streamy_ui\Functional;

/**
 * Tests the StreamyForm behaviors.
 *
 * @group streamy_ui
 */
class StreamyFormTest extends StreamyUITestBase {

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * @var
   */
  protected $publicFSfolder1;

  /**
   * @var
   */
  protected $publicFSfolder2;

  /**
   * @var
   */
  protected $privateFSfolder1;

  /**
   * @var
   */
  protected $privateFSfolder2;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Defining public and private folder names
    $this->publicFSfolder1 = 'testwebtest1';
    $this->publicFSfolder2 = 'testwebtest2';
    $this->privateFSfolder1 = 'testwebtestpvt1';
    $this->privateFSfolder2 = 'testwebtestpvt2';

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy']);
    $this->drupalLogin($this->user);
  }

  /**
   *
   */
  public function testStreamyFormBehaviors() {
    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->assertSession()->statusCodeEquals(200);

    // Trying to save with all the settings disabled
    $this->drupalPostForm(NULL, [], t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Checking config..
    $config = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    self::assertTrue(empty($config['streamy']['master']), 'master is not set');
    self::assertTrue(empty($config['streamy']['slave']), 'slave is not set');
    self::assertTrue(empty($config['streamy']['cdn_wrapper']), 'cdn_wrapper stream is not set');
    self::assertTrue($config['streamy']['disableFallbackCopy'] !== "1", 'disableFallbackCopy is not set');
    self::assertTrue($config['streamy']['enabled'] !== "1", 'The stream streamy is disabled');
    self::assertTrue(empty($config['streamypvt']['master']), 'master is not set');
    self::assertTrue(empty($config['streamypvt']['slave']), 'slave is not set');
    self::assertTrue($config['streamypvt']['disableFallbackCopy'] !== "1", 'disableFallbackCopy is not set');
    self::assertTrue($config['streamypvt']['enabled'] !== "1", 'The stream streamypvt is disabled');

    // Test scheme streamy://
    $edit = [
      'streamy[master]' => 'local',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('You must select both Master and Slave in order to get a stream working.');

    $edit = [
      'streamy[slave]' => 'local',
    ];

    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('You must select both Master and Slave in order to get a stream working.');

    // Set one plugin per time and try to save again

    // Plugin Local
    $pluginConfig = [
      'streamy'    => [
        'master' => [
          'root' => $this->getPublicFilesDirectory() . $this->publicFSfolder1,
        ],
      ],
      'streamypvt' => [
        'master' => [
          'root' => $this->getPublicFilesDirectory() . $this->privateFSfolder1,
        ],
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    $edit = [
      'streamy[master]' => 'local',
      'streamy[slave]'  => 'local',
    ];

    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()
         ->pageTextContains('seems to be not correctly configured. Please check the plugin settings and try to save this page again');

    // Plugin localtest
    $pluginConfig = [
      'streamy'    => [
        'master' => [
          'root' => $this->getPublicFilesDirectory() . $this->publicFSfolder1,
        ],
        'slave' => [
          'root' => $this->getPublicFilesDirectory() . $this->publicFSfolder2,
        ],
      ],
      'streamypvt' => [
        'master' => [
          'root' => $this->getPublicFilesDirectory() . $this->privateFSfolder1,
        ],
        'slave' => [
          'root' => $this->getPublicFilesDirectory() . $this->privateFSfolder2,
        ],
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    $edit = [
      'streamy[master]' => 'local',
      'streamy[slave]'  => 'local',
    ];
    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    self::assertTrue($config['streamy']['master'] === 'local', 'Master stream is properly set');
    self::assertTrue($config['streamy']['slave'] === 'local', 'Slave stream is properly set');

    // Checking the remaining checkboxes
    $edit = [
      'streamy[master]'                 => 'local',
      'streamy[slave]'                  => 'local',
      'streamy[disableFallbackCopy]'    => TRUE,
      'streamy[enabled]'                => TRUE,
      'streamypvt[master]'              => 'local',
      'streamypvt[slave]'               => 'local',
      'streamypvt[disableFallbackCopy]' => TRUE,
      'streamypvt[enabled]'             => TRUE,
    ];
    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    self::assertTrue($config['streamy']['master'] === 'local', 'master is not set');
    self::assertTrue($config['streamy']['slave'] === 'local', 'slave is not set');
    // todo must test with cdn wrapper test
    self::assertTrue(empty($config['streamy']['cdn_wrapper']), 'cdn_wrapper stream is not set');
    self::assertTrue($config['streamy']['disableFallbackCopy'] === "1", 'disableFallbackCopy is enabled');
    self::assertTrue($config['streamy']['enabled'] === "1", 'The stream streamy is enabled');
    self::assertTrue($config['streamypvt']['master'] === 'local', 'master is not set');
    self::assertTrue($config['streamypvt']['slave'] === 'local', 'slave is not set');
    self::assertTrue($config['streamypvt']['disableFallbackCopy'] === "1", 'disableFallbackCopy is not set');
    self::assertTrue($config['streamypvt']['enabled'] === "1", 'The stream streamypvt is enabled');
  }

}
