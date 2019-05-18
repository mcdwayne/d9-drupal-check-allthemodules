<?php

/**
 * @file
 * Tests for po_translations_report.module.
 */

namespace Drupal\po_translations_report\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Url;

/**
 * Functional tests for module po_translations_report.
 *
 * @group Po Translations Report
 */
class PoTranslationsReportTest extends WebTestBase {

  /**
   * Name of the config that may be edited.
   */
  const CONFIGNAME = 'po_translations_report.admin_config';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('po_translations_report');

  /**
   * Tests po_translations_report results.
   */
  public function testPoTranslationsReportResults() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::configFactory()->getEditable(static::CONFIGNAME)
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to result page.
    $this->drupalGet('po_translations_report');
    // Get expected results.
    $expected = $this->getDefaultArrayResults();
    // Test if the results are as expected.
    for ($i = 1; $i <= 3; $i++) {
      $this->assertCategory('file_name', $i, $expected);
      $this->assertCategory('translated', $i, $expected);
      $this->assertCategory('untranslated', $i, $expected);
      $this->assertCategory('not_allowed_translations', $i, $expected);
      $this->assertCategory('total_per_file', $i, $expected);
    }
  }

  /**
   * Tests Admin form.
   */
  public function testPoTranslationsReportAdminForm() {
    // Create user with 'administer site configuration' permission.
    // 'access po translations report' permission is needed after redirection.
    $permissions = array(
      'administer site configuration',
      'access po translations report',
    );
    $this->userCreateAndLogin($permissions);
    $path = 'po_translations_report/admin/config/regional/po-translations-report';
    $this->drupalPostForm($path, array(
      'folder_path' => $this->getDataPath(),
        ), t('Save configuration')
    );
    // The form should redirect to po_translations_report page.
    $text_assert = t('Po Translations Report');
    $this->assertText($text_assert, 'Configure folder path');
  }

  /**
   * Test results per file for translate category.
   */
  public function testDetailsPerFileTranslated() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::configFactory()->getEditable(static::CONFIGNAME)
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to detail result page.
    $path = 'allowed_not_allowed.po/translated';
    $this->drupalGet('po_translations_report/' . $path);
    $source = 'Allowed HTML source string';
    $translation = 'Allowed HTML translation string';
    $raw_assert = '<td>' . $source . '</td>
                      <td>&lt;strong&gt;' . $translation . '&lt;/strong&gt;</td>';
    $this->assertRaw($raw_assert, 'Expected translated details results');
  }

  /**
   * Test results per file for untranslate category.
   */
  public function testDetailsPerFileUntranslated() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::configFactory()->getEditable(static::CONFIGNAME)
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to detail result page.
    $path = 'sample.po/untranslated';
    $this->drupalGet('po_translations_report/' . $path);
    $raw_assert = '<td>@count hours</td>
                      <td></td>';
    $this->assertRaw($raw_assert, 'Expected untranslated results');
  }

  /**
   * Test results per file for non allowed translation category.
   */
  public function testDetailsPerFileNonAllowedTranslations() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::configFactory()->getEditable(static::CONFIGNAME)
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to detail result page.
    $path = 'allowed_not_allowed.po/not_allowed_translations';
    $this->drupalGet('po_translations_report/' . $path);
    $source = 'Non allowed source string';
    $translation = 'Non allowed translation string should not be translated';
    $raw_assert = '<td>&lt;div&gt;' . $source . '&lt;/div&gt;</td>
                      <td>&lt;div&gt;' . $translation . '&lt;/div&gt;</td>';

    $this->assertRaw($raw_assert, 'Expected non allowed translations details');
  }

  /**
   * Test the results page in case of non configured module.
   */
  public function testNonConfiguredModuleCaseResults() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    // Go to result page without configuring anything.
    $this->drupalGet('po_translations_report');
    $url_path = Url::fromRoute('po_translations_report.admin_form');
    $url = \Drupal::l(t('configuration page'), $url_path);
    $raw = t('Please configure a directory in @url.', array('@url' => $url));
    $this->assertRaw($raw, 'Expected result with no configuration');
  }

  /**
   * Test detailed result page in case of non configured module.
   */
  public function testNonConfiguredModuleCaseDetailsPageResult() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    // Go to details result page without configuring anything.
    $file_name = 'sample.po';
    $this->drupalGet('po_translations_report/' . $file_name . '/translated');
    $raw = t('%file_name was not found', array('%file_name' => $file_name));
    $this->assertRaw($raw, 'Expected details result with no configuration');
  }

  /**
   * Create user with permissions and authenticate them.
   */
  public function userCreateAndLogin($permissions) {
    $access_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($access_user);
  }

  /**
   * Gets data folder path that contains po test files.
   */
  public function getDataPath() {
    $module_path = drupal_get_path('module', 'po_translations_report');
    $data_sub_path = '/src/Tests/data';
    return DRUPAL_ROOT . '/' . $module_path . $data_sub_path;
  }

  /**
   * Tests the result table with xpath for each of categories.
   *
   * @param string $category
   *   The category.
   * @param int $index
   *   The row number we are testing.
   * @param array $expected
   *   Expected result array to compare with.
   */
  public function assertCategory($category, $index, array $expected) {
    $value = $this->xpath("//table/tbody/tr[$index]/td[@class='" . $category . "']");
    $link = $this->xpath("//table/tbody/tr[$index]/td[@class='" . $category . "']/a");
    // Category value assert.
    if ($link) {
      $found_value = $link[0]->__toString();
    }
    else {
      $found_value = $value[0]->__toString();
    }
    $this->assertEqual($found_value, $expected[$index][$category]['value'], 'Line ' . $index . ' has the ' . $category . ' value: ' . $expected[$index][$category]['value']);
    // Category link assert.
    if ($link) {
      $found_href = $link[0]->attributes()['href']->__toString();
      // Tests on drupal.org expect links to start with /checkout/
      $found_href = str_replace('/checkout', '', $found_href);
      $this->assertEqual($found_href, $expected[$index][$category]['href'], 'Line ' . $index . ' has the ' . $category . ' href value: ' . $expected[$index][$category]['href']);
    }
  }

  /**
   * Gets default array results.
   *
   * @return array
   *   The array of expected results..
   */
  public function getDefaultArrayResults() {
    return array(
      '1' => array(
        'file_name' => array(
          'value' => 'allowed_not_allowed.po',
        ),
        'translated' => array(
          'value' => '1',
          'href' => '/po_translations_report/allowed_not_allowed.po/translated',
        ),
        'untranslated' => array(
          'value' => '0',
        ),
        'not_allowed_translations' => array(
          'value' => '1',
          'href' => '/po_translations_report/allowed_not_allowed.po/not_allowed_translations',
        ),
        'total_per_file' => array(
          'value' => '2',
        ),
      ),
      '2' => array(
        'file_name' => array(
          'value' => 'sample.po',
        ),
        'translated' => array(
          'value' => '3',
          'href' => '/po_translations_report/sample.po/translated',
        ),
        'untranslated' => array(
          'value' => '1',
          'href' => '/po_translations_report/sample.po/untranslated',
        ),
        'not_allowed_translations' => array(
          'value' => '0',
        ),
        'total_per_file' => array(
          'value' => '4',
        ),
      ),
      '3' => array(
        'file_name' => array(
          'value' => '2 files',
        ),
        'translated' => array(
          'value' => '4',
        ),
        'untranslated' => array(
          'value' => '1',
        ),
        'not_allowed_translations' => array(
          'value' => '1',
        ),
        'total_per_file' => array(
          'value' => '6',
        ),
      ),
    );
  }

}
