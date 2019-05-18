<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\fillpdf\Traits\TestFillPdfTrait;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\user\Entity\Role;

/**
 * Base class that can be inherited by FillPDF tests.
 */
abstract class FillPdfTestBase extends ImageFieldTestBase {

  use TestFileCreationTrait;
  use TestFillPdfTrait;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Modules to enable.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. It is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\BrowserTestBase::installDrupal()
   */
  public static $modules = ['fillpdf_test'];

  /**
   * The FillPdf backend service manager.
   *
   * @var \Drupal\fillpdf\FillPdfBackendManager
   */
  protected $backendServiceManager;

  /**
   * A test image.
   *
   * @var \stdClass
   */
  protected $testImage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add some roles to the logged in admin user.
    $existing_user_roles = $this->adminUser->getRoles(TRUE);
    $role_to_modify = Role::load(end($existing_user_roles));

    // Grant additional permissions to this user.
    $this->grantPermissions($role_to_modify, [
      'access administration pages',
      'administer pdfs',
      'use text format restricted_html'
    ]);

    $this->configureFillPdf();

    $this->backendServiceManager = $this->container->get('plugin.manager.fillpdf_backend_service');

    $this->createImageField('field_fillpdf_test_image', 'article');
    $files = $this->getTestFiles('image');
    $this->testImage = reset($files);
  }

}
