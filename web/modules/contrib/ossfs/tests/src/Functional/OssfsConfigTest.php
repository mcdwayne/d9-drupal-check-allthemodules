<?php

namespace Drupal\Tests\ossfs\Functional;

use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * @group ossfs
 */
class OssfsConfigTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'image',
    'ossfs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates administrative user.
    $user = $this->createUser([
      'administer ossfs',
      'administer site configuration',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests the runtime requirement.
   */
  public function testKey() {
    $assert_session = $this->assertSession();

    $this->drupalGet(Url::fromRoute('ossfs.admin_settings'));
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('access_key', '');
    $assert_session->fieldValueEquals('secret_key', '');

    $this->drupalGet(Url::fromRoute('system.status'));
    $assert_session->pageTextContains('OSS File System access key ID or access key secret is not set.');
    $assert_session->linkExists('OSS File System module settings page');
    $assert_session->linkByHrefExists(Url::fromRoute('ossfs.admin_settings')->toString());
  }

  /**
   * Tests the configuration is stored successfully.
   */
  public function testConfigForm() {
    $assert_session = $this->assertSession();

    // Assert form get.
    $this->drupalGet(Url::fromRoute('ossfs.admin_settings'));
    $assert_session->statusCodeEquals(200);

    // Assert form post.
    $edit = $this->getConfigInput();
    $this->drupalPostForm(Url::fromRoute('ossfs.admin_settings'), $edit, t('Save configuration'));
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Assert config.
    $config = $this->config('ossfs.settings')->get();
    unset($config['_core']);
    $this->assertEquals([
      'access_key' => 'test_assess_key',
      'secret_key' => 'test_secret_key',
      'bucket' => 'test',
      'region' => 'oss-cn-shenzhen',
      'cname' => 'image.example.com',
      'prefix' => 'test/images',
      'internal' => FALSE,
      'styles' => [
        'large' => 'oss_large',
        'medium' => 'oss_medium',
        'thumbnail' => 'oss_thumbnail',
      ],
    ], $config);

    // Assert image settings.
    $this->assertFalse($this->config('image.settings')->get('suppress_itok_output'));

    $image_style = ImageStyle::load('thumbnail');
    $generated_url = $image_style->buildUrl('oss://abc.jpg');
    $this->assertTrue(strpos($generated_url, IMAGE_DERIVATIVE_TOKEN . '=') !== FALSE);

    $edit = $this->getConfigInput([
      'suppress_itok_output' => TRUE,
    ]);
    $this->drupalPostForm(Url::fromRoute('ossfs.admin_settings'), $edit, t('Save configuration'));
    $this->assertTrue($this->config('image.settings')->get('suppress_itok_output'));
    $generated_url = $image_style->buildUrl('oss://abc.jpg');
    $this->assertTrue(strpos($generated_url, IMAGE_DERIVATIVE_TOKEN . '=') === FALSE);
  }

  /**
   * Tests cname.
   */
  public function testConfigFormWithMalformedCName() {
    $assert_session = $this->assertSession();

    // Add the 'http' scheme and the trailing slash to CNAME.
    $edit = $this->getConfigInput([
      'cname' => 'http://image.example.com/',
    ]);
    $this->drupalPostForm(Url::fromRoute('ossfs.admin_settings'), $edit, t('Save configuration'));
    $assert_session->pageTextContains('CNAME is malformed.');

    // Set cname to empty spaces.
    $edit = $this->getConfigInput([
      'cname' => '   ',
    ]);
    $this->drupalPostForm(Url::fromRoute('ossfs.admin_settings'), $edit, t('Save configuration'));
    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertEquals('', $this->config('ossfs.settings')->get('cname'));
  }

  /**
   * Tests prefix.
   */
  public function testConfigFormWithMalformedPrefix() {
    $assert_session = $this->assertSession();

    // Add a trailing slash.
    $edit = $this->getConfigInput([
      'prefix' => 'test/images/',
    ]);
    $this->drupalPostForm(Url::fromRoute('ossfs.admin_settings'), $edit, t('Save configuration'));
    $assert_session->pageTextContains('Path prefix is malformed.');

    // Set prefix to empty spaces.
    $edit = $this->getConfigInput([
      'prefix' => '   ',
    ]);
    $this->drupalPostForm(Url::fromRoute('ossfs.admin_settings'), $edit, t('Save configuration'));
    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertEquals('', $this->config('ossfs.settings')->get('prefix'));
  }

  protected function getConfigInput(array $override = []) {
    return array_merge([
      'access_key' => 'test_assess_key',
      'secret_key' => 'test_secret_key',
      'bucket' => 'test',
      'region' => 'oss-cn-shenzhen',
      'cname' => 'image.example.com',
      'prefix' => 'test/images',
      'internal' => FALSE,
      'suppress_itok_output' => FALSE,
      'styles[large][oss]' => 'oss_large',
      'styles[medium][oss]' => 'oss_medium',
      'styles[thumbnail][oss]' => 'oss_thumbnail',
    ], $override);
  }

}
