<?php

namespace Drupal\canto_connector\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

class CantoConnector extends CKEditorPluginBase   {


  public function isInternal( ) {
    return FALSE;
  }

  public function getFile() {
    return drupal_get_path('module', 'canto_connector') . '/js/plugins/cantoConnector/cantoConnectorPlugin.js';
  }

  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  public function getConfig(Editor $editor) {
    return array(
      'cantoConnector_dialogTitleAdd' => t('Canto Connector'),
      'cantoConnector_dialogTitleEdit' => t('Canto Connector'),
    );
  }

  public function getButtons() {
    return array(
      'CantoConnector' => array(
        'label' => t('Canto Connector'),
        'image' => drupal_get_path('module', 'canto_connector') . '/js/plugins/cantoConnector/icons/cantoconnector.png',
      ),
    );
  }

}
