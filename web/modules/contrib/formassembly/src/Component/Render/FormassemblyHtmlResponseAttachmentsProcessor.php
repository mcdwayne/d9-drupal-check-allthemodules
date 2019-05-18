<?php

namespace Drupal\formassembly\Component\Render;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses with fa_form attachments.
 *
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 * @see \Drupal\formassembly\Entity\FormAssemblyEntityViewBuilder
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
 */
class FormassemblyHtmlResponseAttachmentsProcessor extends HtmlResponseAttachmentsProcessor {

  /**
   * The HTML response attachments processor service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $htmlResponseAttachmentsProcessor;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DfpResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $html_response_attachments_processor
   *   The HTML response attachments processor service.
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(AttachmentsResponseProcessorInterface $html_response_attachments_processor, AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer, ModuleHandlerInterface $module_handler) {
    $this->htmlResponseAttachmentsProcessor = $html_response_attachments_processor;
    $this->configFactory = $config_factory;
    parent::__construct($asset_resolver, $config_factory, $css_collection_renderer, $js_collection_renderer, $request_stack, $renderer, $module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    assert($response instanceof HtmlResponse, new \InvalidArgumentException('\Drupal\Core\Render\HtmlResponse instance expected.'));

    // First, render the actual placeholders. This may add attachments so this
    // is a bit of unfortunate but necessary duplication.
    // This is copied verbatim from
    // \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processAttachments.
    try {
      $response = $this->renderPlaceholders($response);
    }
    catch (EnforcedResponseException $e) {
      return $e->getResponse();
    }

    // Extract fa_form_attachments--HtmlResponseAttachmentsProcessor does not
    // know (nor need to know) how to process those.
    $attachments = $response->getAttachments();
    if (isset($attachments['fa_form_attachments'])) {
      uasort($attachments['fa_form_attachments'], '\Drupal\Component\Utility\SortArray::sortByWeightProperty');
      foreach ($attachments['fa_form_attachments'] as $attachment) {
        switch ($attachment['#type']) {
          case 'fa_form_external_js':
            $attachments['html_head'][] =
              [
                [
                  // Use a fake #type to prevent
                  // HtmlResponseAttachmentsProcessor::processHead() adding one.
                  '#type' => 'fa_form_external_js',
                  '#theme' => 'fa_form__external_js',
                  '#src' => $attachment['#src'],
                ],
                'fa-form-attachment-' . $attachment['#weight'],
              ];
            break;

          case 'fa_form_inline_js':
            $attachments['html_head'][] = [
              [
                // Use a fake #type to prevent
                // HtmlResponseAttachmentsProcessor::processHead() adding one.
                '#type' => 'fa_form_inline_js',
                '#theme' => 'fa_form__inline_js',
                '#value' => $attachment['#value'],
              ],
              'fa-form-attachment-' . $attachment['#weight'],
            ];
            break;

          case 'fa_form_external_css':
            $attachments['html_head'][] = [
              [
                // Use a fake #type to prevent
                // HtmlResponseAttachmentsProcessor::processHead() adding one.
                '#type' => 'fa_form_external_css',
                '#theme' => 'fa_form__external_css',
                '#rel' => $attachment['#rel'],
                '#href' => $attachment['#href'],
              ],
              'fa-form-attachment-' . $attachment['#weight'],
            ];
            break;
        }
      }
      unset($attachments['fa_form_attachments']);
    }
    $response->setAttachments($attachments);

    // Call HtmlResponseAttachmentsProcessor to process all other attachments.
    return $this->htmlResponseAttachmentsProcessor->processAttachments($response);
  }

}
