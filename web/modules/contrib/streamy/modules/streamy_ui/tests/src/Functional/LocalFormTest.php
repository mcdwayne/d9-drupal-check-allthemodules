<?php

namespace Drupal\Tests\streamy_ui\Functional;

/**
 * Tests the Local form behaviors.
 *
 * @group streamy_ui
 */
class LocalFormTest extends StreamyUITestBase {

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

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy local']);
    $this->drupalLogin($this->user);
  }

  /**
   *
   */
  public function testLocalFormBehaviors() {
    $this->drupalGet('/admin/config/media/file-system/streamy/streams/local');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'streamy[master][root]' => '/',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('Failed to retrieve the file list on streamy');

    $edit = [
      'streamypvt[master][root]' => '/',
    ];
    $this->drupalGet('/admin/config/media/file-system/streamy/streams/local');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('Failed to retrieve the file list on streamypvt');

    $edit = [
      'streamy[master][root]'    => $this->getPublicFilesDirectory() . 'writablefolderlocal1',
      'streamypvt[master][root]' => $this->getPublicFilesDirectory() . 'writablefolderpvtlocal1',
    ];
    $this->drupalGet('/admin/config/media/file-system/streamy/streams/local');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $config = \Drupal::configFactory()->get('streamy.local')->get('plugin_configuration');
    self::assertTrue($config['streamy']['master']['root'] === $this->getPublicFilesDirectory() . 'writablefolderlocal1', 'streamy root is properly set');
    self::assertTrue($config['streamy']['master']['slow_stream'] !== "1", 'streamy slow_stream is properly set');
    self::assertTrue($config['streamypvt']['master']['root'] === $this->getPublicFilesDirectory() . 'writablefolderpvtlocal1',
                      'streamypvt root is properly set');
    self::assertTrue($config['streamypvt']['master']['slow_stream'] !== "1", 'streamypvt slow_stream is properly set');

    // Setting the slow stream and trying again
    $edit = [
      'streamy[master][root]'           => $this->getPublicFilesDirectory() . 'writablefolderlocal1',
      'streamy[master][slow_stream]'    => TRUE,
      'streamypvt[master][root]'        => $this->getPublicFilesDirectory() . 'writablefolderpvtlocal1',
      'streamypvt[master][slow_stream]' => TRUE,
    ];
    $this->drupalGet('/admin/config/media/file-system/streamy/streams/local');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');

    $config = \Drupal::configFactory()->get('streamy.local')->get('plugin_configuration');
    self::assertTrue($config['streamy']['master']['root'] === $this->getPublicFilesDirectory() . 'writablefolderlocal1', 'streamy root is properly set');
    self::assertTrue($config['streamy']['master']['slow_stream'] === "1", 'streamy slow_stream is properly set');
    self::assertTrue($config['streamypvt']['master']['root'] === $this->getPublicFilesDirectory() . 'writablefolderpvtlocal1',
                      'streamypvt root is properly set');
    self::assertTrue($config['streamypvt']['master']['slow_stream'] === "1", 'streamypvt slow_stream is properly set');
  }

}
