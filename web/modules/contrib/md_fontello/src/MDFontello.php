<?php

/**
 * @file
 * Contains \Drupal\md_fontello\MDFontello.
 */

namespace Drupal\md_fontello;


use Drupal\Core\Entity\EntityManager;

class MDFontello{

  /**
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * @var array of Entity MD Fontello;
   */
  protected $listEntityFontello;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
    $this->listEntityFontello = $this->entityManager->getStorage('md_fontello')->loadMultiple();
  }

  /**
   * @return array
   */
  public function getListFonts() {
    $list_font = [];
    foreach ($this->listEntityFontello as $index => $fontello) {
      $list['name'] = $fontello->id();
      $list['title'] = $fontello->get('label');
      $list_font[] = $list;
    }
    return $list_font;
  }

  /**
   * @param $font
   * @return array
   */
  public function getOptionFont($font) {
    $fontello = $this->entityManager->getStorage('md_fontello')->load($font);
    if($fontello) {
      $icons = unserialize($fontello->classes);
      return array_combine($icons, $icons);
    }
  }

  /**
   * @return array
   */
  public function getInfoFonts() {
    $info = [
      'css' => [],
      'icons' => []
    ];
    $destination_dir = 'public://md-icon';
    foreach ($this->listEntityFontello as $index => $fontello) {
      $files = unserialize($fontello->files);
      $icons = unserialize($fontello->classes);
      $info['icons'] = array_merge($info['icons'], $icons);
      foreach ($files as $index => $file) {
        $info['css'][] = file_create_url($destination_dir . '/' . $file);
      }
    }
    return $info;
  }

  /**
   * @return array libraries of font
   */
  public function getListLibraries() {
    $libraries = [];

    foreach ($this->listEntityFontello as $index => $fontello) {
      $libraries[] = 'md_fontello/md_fontello.' . $fontello->id();
    }
    return $libraries;
  }

  /**
   * @param $font
   * @return array list name and class of font.
   */
  public function getInfoFont($font) {
    $info = [];
    $fontello = $this->entityManager->getStorage('md_fontello')->load($font);
    if($fontello) {
      $files = unserialize($fontello->files);
      $destination_dir = 'public://md-icon';
      foreach ($files as $index => $file) {
        $info['css'][] = file_create_url($destination_dir . '/' . $file);
      }
      $icons = unserialize($fontello->classes);
      foreach ($icons as $index => $icon) {
        $data['name'] = $data['classes'] = $icon;
        $info['icons'][] = $data;
      }

      return $info;
    }
  }
}