<?php

namespace Drupal\Tests\images_optimizer\Unit\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\images_optimizer\Form\ConfigurationForm;
use Drupal\images_optimizer\Helper\OptimizerHelper;
use Drupal\images_optimizer\Optimizer\JpegoptimOptimizer;
use Drupal\images_optimizer\Optimizer\OptimizerInterface;
use Drupal\images_optimizer\Optimizer\PngquantOptimizer;
use Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Unit test class for the ConfigurationFormTest class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Form
 */
class ConfigurationFormTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * The optimizer service collector.
   *
   * @var \Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector
   */
  private $optimizerServiceCollector;

  /**
   * The optimizer helper.
   *
   * @var \Drupal\images_optimizer\Helper\OptimizerHelper
   */
  private $optimizerHelper;

  /**
   * Our pngquant optimizer.
   *
   * @var \Drupal\images_optimizer\Optimizer\PngquantOptimizer
   */
  private $pngquantOptimizer;

  /**
   * Out jpegoptim optimizer.
   *
   * @var \Drupal\images_optimizer\Optimizer\JpegoptimOptimizer
   */
  private $jpegoptimOptimizer;

  /**
   * The mocked string translation.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\StringTranslation\TranslationInterface
   */
  private $stringTranslation;

  /**
   * The configuration form to test.
   *
   * @var \Drupal\images_optimizer\Form\ConfigurationForm
   */
  private $configurationForm;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->optimizerServiceCollector = new OptimizerServiceCollector();

    $this->optimizerHelper = new OptimizerHelper($this->optimizerServiceCollector, $this->createMock(FileSystemInterface::class), $this->createMock(ConfigFactoryInterface::class));

    $this->pngquantOptimizer = new PngquantOptimizer($this->createMock(ConfigFactoryInterface::class));

    $this->jpegoptimOptimizer = new JpegoptimOptimizer($this->createMock(ConfigFactoryInterface::class));

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->configurationForm = new ConfigurationForm($this->configFactory, $this->optimizerHelper, $this->pngquantOptimizer, $this->jpegoptimOptimizer);
    $this->configurationForm->setStringTranslation($this->stringTranslation);
  }

  /**
   * Test ::create().
   */
  public function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $container
      ->expects($this->atLeast(4))
      ->method('get')
      ->withConsecutive(
        ['config.factory'],
        ['images_optimizer.helper.optimizer'],
        ['images_optimizer.optimizer.pngquant'],
        ['images_optimizer.optimizer.jpegoptim']
      )
      ->willReturnOnConsecutiveCalls(
        $this->configFactory,
        $this->optimizerHelper,
        $this->pngquantOptimizer,
        $this->jpegoptimOptimizer
      );

    $this->assertInstanceOf(ConfigurationForm::class, ConfigurationForm::create($container));
  }

  /**
   * Test getFormId().
   */
  public function testGetFormId() {
    $this->assertSame(
      'images_optimizer_settings_form',
      $this->configurationForm->getFormId()
    );
  }

  /**
   * Test buildForm() when there is no registered optimizers.
   */
  public function testBuildFormWhenThereIsNoRegisteredOptimizers() {
    $this->assertEquals([
      'message' => [
        '#type' => 'markup',
        '#markup' => 'There is no registered optimizers.',
      ],
    ], $this->configurationForm->buildForm([], $this->createMock(FormStateInterface::class)));
  }

  /**
   * Test buildForm().
   */
  public function testBuildForm() {
    $this->optimizerServiceCollector->add($this->pngquantOptimizer, PngquantOptimizer::SERVICE_ID);
    $this->optimizerServiceCollector->add($this->jpegoptimOptimizer, JpegoptimOptimizer::SERVICE_ID);
    $this->optimizerServiceCollector->add($this->getOptimizer(['image/jpeg', 'image/png'], 'Optimizer'), 'optimizer_1');
    $this->optimizerServiceCollector->add($this->getOptimizer(['image/png', 'mime/type'], 'Optimizer'), 'optimizer_2');

    $mainConfiguration = $this->getConfiguration([
      'image/png' => PngquantOptimizer::SERVICE_ID,
      'image/jpeg' => JpegoptimOptimizer::SERVICE_ID,
      'mime/type' => NULL,
    ]);

    $pngquantOptimizerConfiguration = $this->getConfiguration([
      'binary_path' => '/usr/bin/pngquant',
      'minimum_quality' => NULL,
      'maximum_quality' => 60,
      'timeout' => NULL,
    ]);

    $jpegoptimOptimizerConfiguration = $this->getConfiguration([
      'binary_path' => NULL,
      'quality' => 66,
      'timeout' => 4,
    ]);

    $this->configFactory
      ->expects($this->atLeast(3))
      ->method('getEditable')
      ->withConsecutive(
        [ConfigurationForm::MAIN_CONFIGURATION_NAME],
        [$this->pngquantOptimizer->getConfigurationName()],
        [$this->jpegoptimOptimizer->getConfigurationName()]
      )
      ->willReturnOnConsecutiveCalls(
        $mainConfiguration,
        $pngquantOptimizerConfiguration,
        $jpegoptimOptimizerConfiguration
      );

    $form = $this->configurationForm->buildForm([], $this->createMock(FormStateInterface::class));

    $this->assertSame(['#type' => 'vertical_tabs'], $form[ConfigurationForm::VERTICAL_TABS_GROUP]);

    $this->assertEquals([
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->stringTranslation->translate('Save configuration'),
        '#button_type' => 'primary',
      ],
    ], $form['actions']);

    $this->assertSame([
      '#type' => 'value',
      '#value' => [
        'image/jpeg',
        'image/png',
        'mime/type',
      ],
    ], $form['mime_types']);

    $this->assertEquals([
      '#type' => 'details',
      '#title' => $this->stringTranslation->translate('General settings'),
      '#group' => ConfigurationForm::VERTICAL_TABS_GROUP,
      'select_optimizers' => [
        '#type' => 'fieldset',
        '#title' => $this->stringTranslation->translate('Select the optimizers you want to use for each supported mime type'),
        'image/jpeg' => [
          '#type' => 'select',
          '#title' => 'image/jpeg',
          '#empty_option' => $this->stringTranslation->translate('- None -'),
          '#empty_value' => ConfigurationForm::GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE,
          '#options' => [
            'images_optimizer.optimizer.jpegoptim' => 'Jpegoptim',
            'optimizer_1' => 'Optimizer',
          ],
          '#default_value' => 'images_optimizer.optimizer.jpegoptim',
        ],
        'image/png' => [
          '#type' => 'select',
          '#title' => 'image/png',
          '#empty_option' => $this->stringTranslation->translate('- None -'),
          '#empty_value' => ConfigurationForm::GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE,
          '#options' => [
            'images_optimizer.optimizer.pngquant' => 'Pngquant',
            'optimizer_1' => 'Optimizer',
            'optimizer_2' => 'Optimizer (optimizer_2)',
          ],
          '#default_value' => 'images_optimizer.optimizer.pngquant',
        ],
        'mime/type' => [
          '#type' => 'select',
          '#title' => 'mime/type',
          '#empty_option' => $this->stringTranslation->translate('- None -'),
          '#empty_value' => ConfigurationForm::GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE,
          '#options' => [
            'optimizer_2' => 'Optimizer',
          ],
          '#default_value' => NULL,
        ],
      ],
    ], $form['general_settings']);

    $this->assertEquals([
      '#type' => 'details',
      '#title' => $this->stringTranslation->translate('%optimizer_name optimizer settings', ['%optimizer_name' => $this->pngquantOptimizer->getName()]),
      '#group' => ConfigurationForm::VERTICAL_TABS_GROUP,
      'images_optimizer_optimizer_pngquant_binary_path' => [
        '#default_value' => '/usr/bin/pngquant',
        '#type' => 'textfield',
        '#title' => $this->stringTranslation->translate('Binary path'),
        '#description' => $this->stringTranslation->translate('The full path to the pngquant binary on your server.'),
      ],
      'images_optimizer_optimizer_pngquant_minimum_quality' => [
        '#default_value' => NULL,
        '#type' => 'number',
        '#title' => $this->stringTranslation->translate('Minimum quality'),
        '#description' => $this->stringTranslation->translate('Corresponds to the "min" of the "--quality" option. Please check the pngquant documentation for more information.'),
        '#min' => 1,
        '#max' => 100,
      ],
      'images_optimizer_optimizer_pngquant_maximum_quality' => [
        '#default_value' => 60,
        '#type' => 'number',
        '#title' => $this->stringTranslation->translate('Maximum quality'),
        '#description' => $this->stringTranslation->translate('Corresponds to the "max" of the "--quality" option. Please check the pngquant documentation for more information.'),
        '#min' => 1,
        '#max' => 100,
      ],
      'images_optimizer_optimizer_pngquant_timeout' => [
        '#default_value' => NULL,
        '#type' => 'number',
        '#title' => $this->stringTranslation->translate('Timeout'),
        '#description' => $this->stringTranslation->translate('The process timeout in seconds.'),
        '#min' => 1,
      ],
    ], $form['images_optimizer_optimizer_pngquant']);

    $this->assertEquals([
      '#type' => 'details',
      '#title' => $this->stringTranslation->translate('%optimizer_name optimizer settings', ['%optimizer_name' => $this->jpegoptimOptimizer->getName()]),
      '#group' => ConfigurationForm::VERTICAL_TABS_GROUP,
      'images_optimizer_optimizer_jpegoptim_binary_path' => [
        '#default_value' => NULL,
        '#type' => 'textfield',
        '#title' => $this->stringTranslation->translate('Binary path'),
        '#description' => $this->stringTranslation->translate('The full path to the jpegoptim binary on your server.'),
      ],
      'images_optimizer_optimizer_jpegoptim_quality' => [
        '#default_value' => 66,
        '#type' => 'number',
        '#title' => $this->stringTranslation->translate('Quality'),
        '#description' => $this->stringTranslation->translate('Corresponds to the "max" option. Please check the jpegoptim documentation for more information.'),
        '#min' => 1,
        '#max' => 100,
      ],
      'images_optimizer_optimizer_jpegoptim_timeout' => [
        '#default_value' => 4,
        '#type' => 'number',
        '#title' => $this->stringTranslation->translate('Timeout'),
        '#description' => $this->stringTranslation->translate('The process timeout in seconds.'),
        '#min' => 1,
      ],
    ], $form['images_optimizer_optimizer_jpegoptim']);

    $this->assertCount(3, $form['#submit']);
    $this->assertContainsOnlyInstancesOf(\Closure::class, $form['#submit']);
  }

  /**
   * Get a mocked optimizer.
   *
   * @param array $supportedMimeTypes
   *   The supported mime types.
   * @param string $name
   *   The name.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked optimizer.
   */
  private function getOptimizer(array $supportedMimeTypes, $name) {
    $optimizer = $this->createMock(OptimizerInterface::class);
    $optimizer
      ->expects($this->atLeastOnce())
      ->method('getSupportedMimeTypes')
      ->willReturn($supportedMimeTypes);
    $optimizer
      ->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($name);

    return $optimizer;
  }

  /**
   * Get a configuration with the provided data.
   *
   * @param array $data
   *   The data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration.
   */
  private function getConfiguration(array $data) {
    $configuration = new Config('', $this->createMock(StorageInterface::class), $this->createMock(EventDispatcherInterface::class), $this->createMock(TypedConfigManagerInterface::class));
    $configuration->initWithData($data);

    return $configuration;
  }

}
