<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\FieldMapping\ImageFieldMapping;
use Drupal\fillpdf\FieldMapping\TextFieldMapping;
use Drupal\fillpdf_test\Plugin\FillPdfBackend\TestFillPdfBackend;
use Drupal\node\Entity\Node;

/**
 * Tests Core entity population and image stamping.
 *
 * @group fillpdf
 */
class PdfPopulationTest extends FillPdfTestBase {

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
  public static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configureFillPdf();
  }

  /**
   * Tests Core entity population and image stamping.
   */
  public function testPdfPopulation() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $this->assertSession()->pageTextContains('New FillPDF form has been created.');
    $fillpdf_form = FillPdfForm::load($this->getLatestFillPdfForm());


    $node = $this->createNode([
      'title' => 'Hello & how are you?',
      'type' => 'article',
      'body' => [[
        'value' => "<p>PDF form fields don't accept <em>any</em> HTML.</p>",
        'format' => 'restricted_html',
      ]],
      'type'      => 'page',
    ]);

    // Get the field definitions for the form that was created and configure
    // them.
    $this->mapFillPdfFieldsToNodeFields($fillpdf_form->getFormFields());

    // Hit the generation route, check the results from the test backend plugin.
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $fillpdf_form->id(),
        'entity_id' => "node:{$node->id()}",
      ],
    ]);
    $this->drupalGet($fillpdf_route);

    // We don't actually care about downloading the fake PDF. We just want to
    // check what happened in the backend.
    $populate_result = $this->container->get('state')
      ->get('fillpdf_test.last_populated_metadata');

    self::assertEquals(
      "Hello & how are you?",
      $populate_result['field_mapping']['fields']['TextField1'],
      'PDF is populated with the title of the node with all HTML stripped.'
    );

    self::assertEquals(
      "PDF form fields don't accept any HTML.\n",
      $populate_result['field_mapping']['fields']['TextField2'],
      'PDF is populated with the node body. HTML is stripped but a newline
       replaces the <p> tags.'
    );
  }

  /**
   * Tests Core image stamping.
   */
  public function testImageStamping() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $this->assertSession()->pageTextContains('New FillPDF form has been created.');
    $fillpdf_form = FillPdfForm::load($this->getLatestFillPdfForm());

    $node = Node::load(
      $this->uploadNodeImage(
        $this->testImage,
        'field_fillpdf_test_image',
        'article',
        'FillPDF Test Image'
      )
    );

    // Get the field definitions for the form that was created and configure
    // them.
    $this->mapFillPdfFieldsToNodeFields($fillpdf_form->getFormFields());

    // Hit the generation route, check the results from the test backend plugin.
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $fillpdf_form->id(),
        'entity_id' => "node:{$node->id()}",
      ],
    ]);
    $this->drupalGet($fillpdf_route);

    // We don't actually care about downloading the fake PDF. We just want to
    // check what happened in the backend.
    $populate_result = $this->container->get('state')
      ->get('fillpdf_test.last_populated_metadata');

    $node_file = File::load($node->field_fillpdf_test_image->target_id);
    self::assertEquals(
      $populate_result['field_mapping']['images']['ImageField']['data'],
      base64_encode(file_get_contents($node_file->getFileUri())),
      'Encoded image matches known image.'
    );

    $path_info = pathinfo($node_file->getFileUri());
    $expected_file_hash = md5($path_info['filename']) . '.' . $path_info['extension'];
    self::assertEquals(
      $populate_result['field_mapping']['images']['ImageField']['filenamehash'],
      $expected_file_hash,
      'Hashed filename matches known hash.'
    );

    self::assertEquals(
      $populate_result['field_mapping']['fields']['ImageField'],
      "{image}{$node_file->getFileUri()}",
      'URI in metadata matches expected URI.'
    );
  }

  /**
   * Test plugin APIs directly to make sure third-party consumers can use them.
   */
  public function testPluginApi() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $fillpdf_form = FillPdfForm::load($this->getLatestFillPdfForm());

    // Get the field definitions from the actually created form and sort.
    $actual_keys = [];
    foreach ($fillpdf_form->getFormFields() as $form_field) {
      $actual_keys[] = $form_field->pdf_key->value;
    }
    sort($actual_keys);

    // Get the fields from the fixture and sort.
    $expected_keys = [];
    foreach (TestFillPdfBackend::getParseResult() as $expected_field) {
      $expected_keys[] = $expected_field['name'];
    }
    sort($expected_keys);

    // Now compare. InputHelper::attachPdfToForm() filtered out the duplicate,
    // so the count differs, but not the actual values.
    $this->assertCount(5, $expected_keys);
    $this->assertCount(4, $actual_keys);
    $differences = array_diff($expected_keys, $actual_keys);
    self::assertEmpty($differences, 'Parsed fields are in fixture match.');

    // Now create an instance of the backend service and test directly.
    /** @var \Drupal\fillpdf_test\Plugin\BackendService\Test $backend_service */
    $backend_service = $this->backendServiceManager->createInstance('test');
    $original_pdf = file_get_contents($this->getTestPdfPath('fillpdf_test_v3.pdf'));

    // Get the fields from the backend service and sort.
    $actual_keys = [];
    foreach ($backend_service->parse($original_pdf) as $parsed_field) {
      $actual_keys[] = $parsed_field['name'];
    }
    sort($actual_keys);

    // Compare the values.
    $this->assertCount(5, $expected_keys);
    $this->assertCount(5, $actual_keys);
    $differences = array_diff($expected_keys, $actual_keys);
    self::assertEmpty($differences, 'Parsed fields from plugin are in fixture match.');

    // Test the merge method. We'd normally pass in values for $webform_fields
    // and $options, but since this is a stub anyway, there isn't much point.
    // @todo: Test deeper using the State API.
    $merged_pdf = $backend_service->merge($original_pdf, [
      'Foo' => new TextFieldMapping('bar'),
      'Foo2' => new TextFieldMapping('bar2'),
      'Image1' => new ImageFieldMapping(file_get_contents($this->testImage->uri), 'png'),
    ], []);
    self::assertEquals($original_pdf, $merged_pdf);

    $merge_state = $this->container->get('state')
      ->get('fillpdf_test.last_populated_metadata');

    // Check that fields are set as expected.
    self::assertInstanceOf(TextFieldMapping::class, $merge_state['field_mapping']['Foo'], 'Field "Foo" was mapped to a TextFieldMapping object.');
    self::assertInstanceOf(TextFieldMapping::class, $merge_state['field_mapping']['Foo2'], 'Field "Foo2" was mapped to a TextFieldMapping object.');
    self::assertInstanceOf(ImageFieldMapping::class, $merge_state['field_mapping']['Image1'], 'Field "Image1" was mapped to an ImageFieldMapping object.');
  }

  /**
   * Maps FillPdf fields to node fields.
   *
   * @param \Drupal\fillpdf\Entity\FillPdfFormField[] $fields
   *   Array of FillPdfFormFields.
   */
  protected function mapFillPdfFieldsToNodeFields(array $fields) {
    foreach ($fields as $field) {
      switch ($field->pdf_key->value) {
        case 'ImageField':
        case 'Button2':
          $field->value = '[node:field_fillpdf_test_image]';
          break;

        case 'TextField1':
        case 'Text1':
          $field->value = '[node:title]';
          break;

        case 'TextField2':
        case 'Text2':
          $field->value = '[node:body]';
          break;
      }
      $field->save();
    }
  }

  /**
   * Tests PDF population using local service.
   *
   * @throws \PHPUnit_Framework_SkippedTestError
   *   Thrown when test had to be skipped as FillPDF LocalServer is not
   *   available.
   */
  public function testMergeLocalService() {
    // For local container testing, we require the Docker container to be
    // running on port 8085. If http://127.0.0.1:8085/ping does not return a
    // 200, we assume that we're not in an environment where we can run this
    // test.
    $this->configureLocalServiceBackend();
    $config = $this->container->get('config.factory')->get('fillpdf.settings');
    if (!FillPdf::checkLocalServiceEndpoint($this->container->get('http_client'), $config)) {
      throw new \PHPUnit_Framework_SkippedTestError('FillPDF LocalServer unavailable, so skipping test.');
    }
    $this->backendTest();
  }

  /**
   * Tests PDF population using a local install of pdftk.
   *
   * @throws \PHPUnit_Framework_SkippedTestError
   *   Thrown when test had to be skipped as local pdftk install is not
   *   available.
   *
   * @todo Implementation missing.
   */
  public function testMergePdftk() {
    $this->configureFillPdf(['backend' => 'pdftk']);
    if (!FillPdf::checkPdftkPath()) {
      throw new \PHPUnit_Framework_SkippedTestError('pdftk not available, so skipping test.');
    }
    $this->backendTest();
  }

  /**
   * Tests a backend.
   */
  protected function backendTest() {
    // If we can upload a PDF, parsing is working.
    // Test with a node.
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $fillpdf_form = FillPdfForm::load($this->getLatestFillPdfForm());

    // Get the field definitions for the form that was created and configure
    // them.
    $fields = $fillpdf_form->getFormFields();
    $this->mapFillPdfFieldsToNodeFields($fields);

    // Set up a test node.
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
    ]);

    // Hit the generation route, check the results from the test backend plugin.
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $fillpdf_form->id(),
        'entity_id' => "node:{$node->id()}",
      ],
    ]);
    $this->drupalGet($fillpdf_route);
    $this->assertSession()->pageTextNotContains('Merging the FillPDF Form failed');

    // Check if what we're seeing really is a PDF file.
    $maybe_pdf = $this->getSession()->getPage()->getContent();
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    static::assertEquals('application/pdf', $finfo->buffer($maybe_pdf));
  }

}
