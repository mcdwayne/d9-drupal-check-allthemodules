<?php

namespace Drupal\cloudwords_interface_translation;

use Drupal\cloudwords\CloudwordsSourceControllerInterface;

class CloudwordsInterfaceSourceController implements CloudwordsSourceControllerInterface {

  protected $objectInfo = [];
  protected $stringInfo = [];
  protected $bundle = [];
  protected $type;

  /**
   * Implements CloudwordsSourceControllerInterface::__construct().
   */
  public function __construct($type) {
    //$this->type = $type;
  }

  /**
   * Implements CloudwordsSourceControllerInterface::typeLabel().
   */
  public function typeLabel() {
    return 'Interface';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::textGroup().
   */
  public function textGroup() {
    return 'interface';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::textGroupLabel().
   */
  public function textGroupLabel() {
    //TODO need to get the label of the entity bundle
    return 'interface Label';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::targetLabel().
   */
  public function targetLabel(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    return 'interface';
  }

  /*
  * Implements CloudwordsSourceControllerInterface::bundleLabel().
  */
  public function bundleLabel(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    return 'interface';
  }

  /**
   * Implements CloudwordsSourceControllerInterface::uri().
   */
  public function uri(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    return;
  }

  /**
   * Implements CloudwordsSourceControllerInterface::data().
   */
  public function data(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    $lid = $translatable->getObjectId();
    $source = \Drupal::service('locale.storage')->findString(['lid' => $translatable->getObjectId()]);
    $structure = array('#label' => 'Interface Strings: ' . $translatable->typeLabel());
    $structure[$lid] = array(
      '#label' => $source->source,
      '#text' => $source->source,
      '#translate' => TRUE,
    );

    return $structure;
  }

  /**
   * Implements CloudwordsSourceControllerInterface::save().
   */
  public function save(\Drupal\cloudwords\Entity\CloudwordsTranslatable $translatable) {
    foreach ($translatable->getData() as $lid => $item) {
      $translation = \Drupal::service('locale.storage')->findTranslation(['lid' => $lid, 'language' =>$translatable->getLanguage()]);
      if (isset($item['#translation']['#text']) && isset($translation->lid)) {

        $translation->translation = $item['#translation']['#text'];
        $translation->language = $translatable->getLanguage();

        $translation->save();
      }
    }
    return TRUE;
  }
}
