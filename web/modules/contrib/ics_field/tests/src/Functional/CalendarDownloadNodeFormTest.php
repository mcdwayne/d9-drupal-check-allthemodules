<?php

namespace Drupal\Tests\ics_field\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Client;

/**
 * Tests that the add/edit Node Forms behaves properly.
 *
 * @group ics_field
 */
class CalendarDownloadNodeFormTest extends BrowserTestBase {

  /**
   * The admin user used in the tests.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Exempt from strict schema checking.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * A node created for testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $testNode;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'node',
    'datetime',
    'ics_field',
    'file',
  ];

  /**
   * {@inheritdoc}
   *
   * @expectedException \Drupal\Core\Config\Schema\SchemaIncompleteException
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([], NULL, 1);
    $this->adminUser->set('timezone', 'Europe/Zurich');
    $this->adminUser->save();

    $this->drupalLogin($this->adminUser);

    $bundle = 'ics_test';

    $nodeType = NodeType::create([
      'type'        => $bundle,
      'name'        => 'ics_test',
      'description' => "Use <em>ics_test</em> for  testing ics.",
    ]);
    $nodeType->save();

    entity_create('field_storage_config',
                  [
                    'field_name'    => 'field_dates',
                    'entity_type'   => 'node',
                    'type'          => 'datetime',
                    'datetime_type' => 'datetime',
                  ])->save();
    entity_create('field_config',
                  [
                    'field_name'  => 'field_dates',
                    'label'       => 'Dates',
                    'entity_type' => 'node',
                    'bundle'      => $bundle,
                  ])->save();
    // Need to set the widget type, otherwise the form will not contain it.
    entity_get_form_display('node', $bundle, 'default')
      ->setComponent('field_dates',
                     [
                       'type' => 'datetime_default',
                     ])
      ->save();

    $fieldIcsDownload = entity_create('field_storage_config',
                                      [
                                        'field_name'  => 'field_ics_download',
                                        'entity_type' => 'node',
                                        'type'        => 'calendar_download_type',
                                      ]);
    $fieldIcsDownload->setSettings([
      'date_field_reference' => 'field_dates',
      'is_ascii'             => FALSE,
      'uri_scheme'           => 'public',
      'file_directory'       => 'icsfiles',
    ]);
    $fieldIcsDownload->save();
    entity_create('field_config',
                  [
                    'field_name'  => 'field_ics_download',
                    'label'       => 'ICS Download',
                    'entity_type' => 'node',
                    'bundle'      => $bundle,
                  ])->save();
    // Need to set the widget type, otherwise the form will not contain it.
    entity_get_form_display('node', $bundle, 'default')
      ->setComponent('field_ics_download',
                     [
                       'type' => 'calendar_download_default_widget',
                     ])
      ->save();
    entity_get_display('node', $bundle, 'default')
      ->setComponent('field_ics_download',
                     [
                       'type'     => 'calendar_download_default_formatter',
                       'settings' => [],
                     ])
      ->save();

    entity_create('field_storage_config',
                  [
                    'field_name'  => 'field_body',
                    'entity_type' => 'node',
                    'type'        => 'text_with_summary',
                  ])->save();
    entity_create('field_config',
                  [
                    'field_name'  => 'field_body',
                    'label'       => 'Body',
                    'entity_type' => 'node',
                    'bundle'      => $bundle,
                  ])->save();
    // Need to set the widget type, otherwise the form will not contain it.
    entity_get_form_display('node', $bundle, 'default')
      ->setComponent('field_body',
                     [
                       'type'     => 'text_textarea_with_summary',
                       'settings' => [
                         'rows'         => '9',
                         'summary_rows' => '3',
                       ],
                       'weight'   => 5,
                     ])
      ->save();
  }

  /**
   * Test that we can add a node.
   */
  public function testCreateAndViewNode() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Create a random date in the coming week.
    $timestamp = REQUEST_TIME + random_int(0, 86400 * 7);
    $dateValue0Date = gmdate(DATETIME_DATE_STORAGE_FORMAT, $timestamp);
    $dateValue0Time = gmdate('H:i:s', $timestamp);

    $add = [
      'title[0][value]'                    => 'A calendar event',
      'field_dates[0][value][date]'        => $dateValue0Date,
      'field_dates[0][value][time]'        => $dateValue0Time,
      'field_body[0][value]'               => "Lorem ipsum.",
      'field_ics_download[0][summary]'     => '[node:title]',
      'field_ics_download[0][description]' => '[node:field_body]',
      'field_ics_download[0][url]'         => '[node:url:absolute]',
    ];
    $this->drupalPostForm('node/add/ics_test', $add, t('Save'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($add['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

    // Get the node's view.
    $this->drupalGet('node/' . $node->id());

    // Check if there is a link for downloading the ics file.
    $elements = $this->xpath('//a[@href and string-length(@href)!=0 and text() = :label]',
                             [':label' => t('iCal Download')->render()]);
    $el = reset($elements);
    $downloadUrl = $el->getAttribute('href');
    $icsString = file_get_contents($downloadUrl);

    $icalValidationUrl = $this->getExternalCalendalValidationService();

    if ($icalValidationUrl) {
      // Send a post to the ical_validation_url,
      // at http://severinghaus.org/projects/icv/
      $httpClient = new Client();
      $postArray = [
        'form_params' => ['snip' => $icsString],
      ];
      $response = $httpClient->post($icalValidationUrl, $postArray);
      $this->assertNotEquals(FALSE, strpos($response->getBody()->getContents(), 'Congratulations; your calendar validated!'));
    }
    else {
      // TODO Implement some local validation.
      // This would imply a need some local code iCal parsing library to
      // validate the generated string.
    }
  }

  /**
   * Check if ical_validation_url is available.
   *
   * @return string|bool
   *   Returns the ical validation service url, if the website is available,
   *   false otherwise.
   */
  private function getExternalCalendalValidationService() {
    $icalValidationUrl = 'http://severinghaus.org/projects/icv/';

    $curlInit = curl_init();
    curl_setopt($curlInit, CURLOPT_URL, $icalValidationUrl);
    curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curlInit, CURLOPT_HEADER, FALSE);
    curl_setopt($curlInit, CURLOPT_NOBODY, FALSE);
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($curlInit);
    curl_close($curlInit);
    if ($response) {
      return $icalValidationUrl;
    }
    return FALSE;
  }

}
