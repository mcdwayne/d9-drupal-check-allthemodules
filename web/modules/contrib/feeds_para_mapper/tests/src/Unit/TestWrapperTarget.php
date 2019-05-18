<?php
namespace Drupal\Tests\feeds_para_mapper\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds_para_mapper\Feeds\Target\WrapperTarget;
use Drupal\Tests\feeds_para_mapper\Unit\Helpers\Common;

/**
 * @group Feeds Paragraphs
 * @coversDefaultClass \Drupal\feeds_para_mapper\Feeds\Target\WrapperTarget
 */
class TestWrapperTarget extends FpmTestBase {
  use Common;
  /**
   * @var string
   */
  protected $class;
  /**
   * @var string
   */
  protected $type;

  protected function setUp()
  {
    $this->class        = Text::class;
    $this->type         = "text";
    $this->getInstanceMock();
    parent::setUp();
    $this->addServices($this->services);
  }
  /**
   * Mocks a form state object.
   * @return FormStateInterface
   *   The form state.
   */
  public function getFormStateMock(){
    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->any())
      ->method('getTriggeringElement')
      ->willReturn(array('#delta' => 0));

    $formState->expects($this->any())
      ->method('getValue')
      ->willReturn(array('format' => 'test format'));
    return $formState;
  }

  /**
   *
   * @covers ::createTargetInstance
   */
  public function testCreateTargetInstance()
  {
    $instance = $this->wrapperTarget->createTargetInstance();
    $this->assertTrue($instance instanceof Text);
  }

  /**
   *
   * @covers ::prepareTarget
   */
  public function testPrepareTarget(){
    $method = $this->getMethod(Text::class,'prepareTarget')->getClosure();
    $field = $this->fieldHelper->getBundleFields('bundle_two')[0]->reveal();
    $info = $this->getTargetInfo();
    $field->set('target_info', $info);
    $textDef = $method($field);
    $textPCount = count($textDef->getProperties());
    $method = $this->getMethod(WrapperTarget::class,'prepareTarget')->getClosure();
    $wrapperDef = $method($field);
    $wrapperPCount = count($wrapperDef->getProperties());
    $this->assertSame($textPCount,$wrapperPCount,'The wrapper has the target properties');
  }
  /**
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(){
    $textDefaultConfig = $this->target->defaultConfiguration();
    $wrapperDefaultConfig = $this->wrapperTarget->defaultConfiguration();
    $message = "Wrapper has the target's default configuration: ";
    foreach ($textDefaultConfig as $key => $configItem) {
      $this->assertArrayHasKey($key,$wrapperDefaultConfig, $message . $key);
    }
    $this->assertArrayHasKey('max_values', $wrapperDefaultConfig, $message . 'max_values');
  }

  /**
   *
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm(){
    $formState = $this->getFormStateMock();
    $textForm = $this->target->buildConfigurationForm(array(), $formState);
    $wrapperForm = $this->wrapperTarget->buildConfigurationForm(array(), $formState);
    $message = "Wrapper has the target's form element: ";
    foreach ($textForm as $field => $formMarkup) {
      $this->assertArrayHasKey($field,$wrapperForm, $message . $field);
    }
    $this->assertArrayHasKey('max_values', $wrapperForm, $message . 'max_values');
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummary(){
    $res = $this->wrapperTarget->getSummary();
    $res = $res->getUntranslatedString();
    $expected = "test summary<br>Maximum values: -1";
    $this->assertSame($res, $expected, "The target summary exists");
  }
  /**
   * @covers \Drupal\feeds_para_mapper\Feeds\Target\WrapperTarget::targets
   */
  public function testTargets(){
    $targets = array();
    $this->wrapperTarget->targets($targets,$this->feedType,array());
    $this->assertTrue(isset($targets['bundle_two_text']),'Target added');
    $field_type = $targets['bundle_two_text']->getFieldDefinition()->field_type;
    $this->assertSame('entity_reference_revisions', $field_type,'target field type is changed');
  }
}