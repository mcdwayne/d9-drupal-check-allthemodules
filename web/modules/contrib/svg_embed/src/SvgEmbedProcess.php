<?php

/**
 * @file
 * Contains \Drupal\svg_embed\SvgEmbedProcess.
 */

namespace Drupal\svg_embed;

use Drupal\file\Entity\File;

/**
 * Class SvgEmbedProcess.
 *
 * @package Drupal\svg_embed
 */
class SvgEmbedProcess implements SvgEmbedProcessInterface {
  /**
   * @inheritDoc
   */
  public function translate($uuid, $langcode) {
    $xml = $this->loadFile($uuid);

    //TODO: Go through the DOM and translate all relevant strings, see _svg_embed_translate() in D7

    return str_replace('<?xml version="1.0"?>', '', $xml->asXML());
  }

  /**
   * @inheritDoc
   */
  public function extract($uuid) {
    $xml = $this->loadFile($uuid);

    //TODO: Go through the DOM and extract all relevant strings, see _svg_embed_extract() in D7

    return array();
  }

  /**
   * @param string $uuid
   * @return \SimpleXMLElement
   * @throws \Exception
   */
  private function loadFile($uuid) {
    /** @var File $file */
    $file = \Drupal::entityManager()->loadEntityByUuid('file', $uuid);
    if (!$file) {
      throw new \Exception('File entity not found');
    }

    $text = file_get_contents($file->getFileUri());
    return new \SimpleXMLElement($text);
  }

}
