<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/7/16
 * Time: 7:25 PM
 */

namespace Drupal\forena_ui\Tests;


use Drupal\simpletest\WebTestBase;

/**
 * Class ReportDefinitionTest
 *
 * @group Forena
 * @ingroup Forena
 */
class ReportDefinitionTest extends WebTestBase{
  public static $modules = [ 'forena', 'forena_ui'];

  protected $profile = 'minimal';

  private $privileged_user;

  public static function getInfo() {
    return array(
      'name' => 'Forena Reports UI',
      'description' => 'Report definition tests for forena',
      'group' => t('Forena'),
    );
  }

  public function setup() {
    parent::setup();
    // Create and log in our privileged user.
    $this->privileged_user = $this->drupalCreateUser(
      [
        'administer forena',
        'list forena reports',
      ],
      'test_admin',
      TRUE
    );
  }

  public function testReportDisplay() {
    $this->drupalGet('admin/structure/forena');
    $this->assertLink('Sample Reports');
  }
}