<?php

namespace Drupal\Tests\select2boxes\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class Select2BoxesTestsBase.
 *
 * Base class for the Select2Boxes tests.
 *
 * @package Drupal\Tests\select2boxes\FunctionalJavascript
 * @group Select2Boxes
 */
class Select2BoxesTestsBase extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Modules that needs to be enabled.
   *
   * @var array
   */
  protected static $modules = ['select2boxes', 'node', 'field', 'select2boxes_test_form'];

  /**
   * Plugin IDs list of the field widgets.
   *
   * @var array
   */
  protected static $pluginIds = [
    'select2boxes_autocomplete_list',
    'select2boxes_autocomplete_single',
    'select2boxes_autocomplete_multi',
  ];

  /**
   * Captures index.
   *
   * @var int
   */
  protected $capturesIndex = 0;

  /**
   * Manage form display path.
   *
   * @var string
   */
  protected static $manageFormDisplayPath = 'admin/structure/types/manage/article/form-display';

  /**
   * Node add form path.
   *
   * @var string
   */
  protected static $nodeAddPath = 'node/add/article';

  /**
   * The active Mink session object.
   *
   * @var \Behat\Mink\Session
   */
  protected $minkSession;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $user = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($user);
    // Create list field.
    $this->fieldUiAddNewField(
      'admin/structure/types/manage/article',
      'test_list',
      'Test list',
      'list_integer',
      ['settings[allowed_values]' => '0|1' . PHP_EOL . '1|2' . PHP_EOL . '2|3']
    );
    $this->minkSession = $this->getSession();
    $this->minkSession->getDriver()->getBrowser()->jsErrors(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Destroy the testing kernel.
    if (isset($this->kernel)) {
      $this->cleanupEnvironment();
      $this->kernel->shutdown();
    }

    // Ensure that internal logged in variable is reset.
    $this->loggedInUser = FALSE;

    if ($this->mink) {
      $this->mink->stopSessions();
    }

    // Restore original shutdown callbacks.
    if (function_exists('drupal_register_shutdown_function')) {
      $callbacks = &drupal_register_shutdown_function();
      $callbacks = $this->originalShutdownCallbacks;
    }
  }

  /**
   * Simply tests that site isn't broken after a module is being installed.
   */
  public function testModuleInstallation() {
    // Go to the front page.
    $this->drupalGet('<front>');
    // Check if the response status is 200(OK).
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Get field options.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   Field element.
   *
   * @return array
   *   Options array.
   */
  protected function getFieldOptions(NodeElement $select) {
    $options = [];
    foreach ($select->findAll('xpath', '//option') as $option) {
      /* @var NodeElement $option */
      $options[] = $option->getAttribute('value') ?: $option->getText();
    }
    return $options;
  }

  /**
   * Get field element by it's ID.
   *
   * @param string $id
   *   Element's ID.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   Element object if found, NULL otherwise.
   */
  protected function getFieldById($id) {
    return $this->minkSession
      ->getPage()
      ->findById($id);
  }

  /**
   * Generates a specified number of terms within a specific vocabulary.
   *
   * @param string $vid
   *   Taxonomy vocabulary ID.
   * @param int $count
   *   Number of terms that needs to be generated.
   *
   * @return \Drupal\taxonomy\Entity\Term[]
   *   Generated terms objects.
   */
  protected function generateDummyTerms($vid, $count) {
    $terms = [];
    for ($i = 0; $i < $count; $i++) {
      $terms[$i] = Term::create([
        'vid'  => $vid,
        'name' => $this->randomString(4),
      ]);
      $terms[$i]->save();
    }
    return $terms;
  }

  /**
   * Generates a specified number of nodes of Article content type.
   *
   * @param int $count
   *   Number of nodes that needs to be generated.
   *
   * @return \Drupal\node\Entity\Node[]
   *   Generated nodes objects.
   */
  protected function generateDummyArticles($count) {
    $nodes = [];
    for ($i = 0; $i <= $count; $i++) {
      $nodes[$i] = Node::create([
        'type'  => 'article',
        'title' => $this->randomString(4),
      ]);
      $nodes[$i]->save();
    }
    return $nodes;
  }

  /**
   * Perform clicking on the "Save" button.
   */
  protected function saveForm() {
    $this->click('input[value="Save"]');
  }

  /**
   * Creates a new field through the Field UI.
   *
   * @param string $bundle_path
   *   Admin path of the bundle that the new field is to be attached to.
   * @param string $field_name
   *   The field name of the new field storage.
   * @param string $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param string $field_type
   *   (optional) The field type of the new field storage. Defaults to
   *   'test_field'.
   * @param array $storage_edit
   *   (optional) $edit parameter for drupalPostForm() on the second step
   *   ('Storage settings' form).
   * @param array $field_edit
   *   (optional) $edit parameter for drupalPostForm() on the third step ('Field
   *   settings' form).
   */
  protected function fieldUiAddNewField($bundle_path, $field_name, $label = NULL, $field_type = 'test_field', array $storage_edit = [], array $field_edit = []) {
    $label = $label ?: $this->randomString();
    $initial_edit = [
      'new_storage_type' => $field_type,
      'label' => $label,
      'field_name' => $field_name,
    ];

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields/add-field";
    }

    // First step: 'Add field' page.
    $this->drupalPostForm($bundle_path, $initial_edit, t('Save and continue'));
    // Second step: 'Storage settings' form.
    $this->drupalPostForm(NULL, $storage_edit, t('Save field settings'));
    // Third step: 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save settings'));
  }

  /**
   * Make the screenshots.
   */
  protected function verboseCaptures() {
    $directory = DRUPAL_ROOT . '/sites/simpletest/captures/';
    if (!is_dir($directory)) {
      mkdir($directory);
    }
    $path = $directory
      . 'select2boxes_capture_'
      . $this->capturesIndex++
      . '.jpg';
    $this->createScreenshot($path);
  }

}
