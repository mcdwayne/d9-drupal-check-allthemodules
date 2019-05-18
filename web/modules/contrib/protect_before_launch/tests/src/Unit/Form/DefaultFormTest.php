<?php

namespace Drupal\Tests\protect_before_launch\Unit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\protect_before_launch\Configuration;
use Drupal\protect_before_launch\Form\DefaultForm;
use Drupal\Tests\protect_before_launch\Unit\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DefaultFormTest.
 */
class DefaultFormTest extends UnitTestCase {

  /**
   * Test create instance with static create.
   */
  public function testCreate() {
    $configuration = $this->createMock(Configuration::class);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturn($configuration);

    $this->assertInstanceOf(DefaultForm::class, DefaultForm::create($container));
  }

  /**
   * Test get form id.
   */
  public function testGetFormId() {
    $configuration = $this->createMock(Configuration::class);

    $form = new DefaultForm($configuration);
    $this->assertEquals('protect_before_launch_form', $form->getFormId());
  }

  /**
   * Test build form.
   */
  public function testBuildForm() {
    $configuration = $this->createMock(Configuration::class);
    $formState = $this->createMock(FormStateInterface::class);
    $stringTranslation = $this->createMock(TranslationInterface::class);

    $form = new DefaultForm($configuration);
    $form->setStringTranslation($stringTranslation);

    $formData = $form->buildForm([], $formState);

    $this->assertArrayHasKey('protect', $formData);
    $this->assertArrayHasKey('username', $formData);
    $this->assertArrayHasKey('password', $formData);
    $this->assertArrayHasKey('advanced-section', $formData);
    $this->assertArrayHasKey('authentication_type', $formData['advanced-section']);
    $this->assertArrayHasKey('realm', $formData['advanced-section']);
    $this->assertArrayHasKey('content', $formData['advanced-section']);
    $this->assertArrayHasKey('exclude_paths', $formData['advanced-section']);
    $this->assertArrayHasKey('environment_key', $formData['advanced-section']);
    $this->assertArrayHasKey('environment_value', $formData['advanced-section']);
    $this->assertArrayHasKey('submit', $formData);
  }

  /**
   * Test submit form.
   */
  public function testSubmitForm() {
    $configuration = $this->createMock(Configuration::class);
    $configuration->expects($this->once())->method('setProtect')->with('protect')->willReturnSelf();
    $configuration->expects($this->once())->method('setUsername')->with('username')->willReturnSelf();
    $configuration->expects($this->once())->method('setPassword')->with('password')->willReturnSelf();
    $configuration->expects($this->once())->method('setAuthenticationType')->with('authentication_type')->willReturnSelf();
    $configuration->expects($this->once())->method('setRealm')->with('realm')->willReturnSelf();
    $configuration->expects($this->once())->method('setContent')->with('content')->willReturnSelf();
    $configuration->expects($this->once())->method('setExcludePaths')->with('exclude_paths')->willReturnSelf();
    $configuration->expects($this->once())->method('setEnvironmentKey')->with('environment_key')->willReturnSelf();
    $configuration->expects($this->once())->method('setEnvironmentValue')->with('environment_value')->willReturnSelf();

    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')->will($this->returnCallback(function ($value) {
      return $value;
    }));

    $formData = [];
    $form = new DefaultForm($configuration);
    $form->submitForm($formData, $formState);
  }

  /**
   * Test get editable config names.
   */
  public function testGetEditableConfigNames() {
    $class = new \ReflectionClass(DefaultForm::class);
    $method = $class->getMethod('getEditableConfigNames');
    $method->setAccessible(TRUE);

    $configuration = $this->createMock(Configuration::class);

    $form = new DefaultForm($configuration);
    $this->assertEquals([Configuration::CONFIG_KEY], $method->invokeArgs($form, []));
  }

}
