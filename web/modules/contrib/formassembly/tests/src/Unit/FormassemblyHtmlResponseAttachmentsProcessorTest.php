<?php

namespace Drupal\Tests\formassembly\Unit;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\formassembly\Component\Render\FormassemblyHtmlResponseAttachmentsProcessor;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit test calls for FormassemblyHtmlResponseAttachmentsProcessor.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @coversDefaultClass \Drupal\formassembly\Component\Render\FormassemblyHtmlResponseAttachmentsProcessor
 * @group formassembly
 */
class FormassemblyHtmlResponseAttachmentsProcessorTest extends UnitTestCase {

  /**
   * A mock core html attachment processor.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $attachmentProcessor;

  /**
   * A mock asset resolver service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $assetResolver;

  /**
   * A mock CSS collection renderer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $cssCollectionRenderer;

  /**
   * A mock JS collection renderer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $jsCollectionRenderer;

  /**
   * A mock RequestStack.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $requestStack;

  /**
   * A mock renderer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $renderer;

  /**
   * A mock module handler.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Mock core attachment processor.
    $this->attachmentProcessor = $this->getMockBuilder(AttachmentsResponseProcessorInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->attachmentProcessor->method('processAttachments')
      ->willReturnArgument(0);
    $this->assetResolver = $this->prophesize(AssetResolverInterface::class);
    $this->cssCollectionRenderer = $this->prophesize(AssetCollectionRendererInterface::class);
    $this->jsCollectionRenderer = $this->prophesize(AssetCollectionRendererInterface::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->renderer = $this->prophesize(RendererInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
  }

  /**
   * @covers ::processAttachments
   */
  public function testProcessAttachments() {
    // Create a response with fa_form_attachments attachments and ensure that
    // they are properly converted to html_head attachments. Also ensure that
    // the other html_head elements are added to create the necessary
    // javascript in the right order.
    $response = new HtmlResponse();
    $attachments['fa_form_attachments'] =
      [
        0 =>
          [
            '#type' => 'fa_form_inline_js',
            '#value' => "console.log('test')",
            '#weight' => 6,
          ],
        1 =>
          [
            '#type' => 'fa_form_external_css',
            '#rel' => 'stylesheet',
            '#href' => 'http://example.com/example.css',
            '#weight' => 1,
          ],
        2 =>
          [
            '#type' => 'fa_form_external_js',
            '#src' => 'http://example.com/example.js',
            '#weight' => 0,
          ],
      ];
    $response->setAttachments($attachments);
    $config_factory = $this->getConfigFactoryStub(['formassembly.api.oauth' => ['instance' => NULL]]);
    $response = $this->getFormassemblyAttachmentProcessor($config_factory)->processAttachments($response);
    $processedAttachments = $response->getAttachments();
    $this->assertEquals('fa_form_external_js', $processedAttachments["html_head"][0][0]["#type"]);
    $this->assertEquals('fa_form_external_css', $processedAttachments["html_head"][1][0]["#type"]);
    $this->assertEquals('fa_form_inline_js', $processedAttachments["html_head"][2][0]["#type"]);
    $this->assertArrayNotHasKey('fa_form_attachments', $processedAttachments, 'The fa_form_attachments attachments are converted to html_head attachments.');
  }

  /**
   * @covers ::processAttachments
   */
  public function testProcessAttachmentsNoAdditions() {
    // Ensure that if there are no fa_form_attachments
    // nothing is added to the attachments.
    $response = new HtmlResponse();
    $config_factory = $this->getConfigFactoryStub();
    $response = $this->getFormassemblyAttachmentProcessor($config_factory)->processAttachments($response);
    $processedAttachments = $response->getAttachments();
    $this->assertEmpty($processedAttachments);
  }

  /**
   * Creates a FormassemblyHtmlResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A mock config factory that can contain 'dfp.settings' configuration.
   *
   * @return \Drupal\formassembly\Component\Render\FormassemblyHtmlResponseAttachmentsProcessor
   *   The DfpHtmlResponseAttachmentsProcessor object.
   */
  protected function getFormassemblyAttachmentProcessor(ConfigFactoryInterface $config_factory) {
    return new FormassemblyHtmlResponseAttachmentsProcessor(
      $this->attachmentProcessor,
      $this->assetResolver->reveal(),
      $config_factory,
      $this->cssCollectionRenderer->reveal(),
      $this->jsCollectionRenderer->reveal(),
      $this->requestStack->reveal(),
      $this->renderer->reveal(),
      $this->moduleHandler->reveal()
    );
  }

}
