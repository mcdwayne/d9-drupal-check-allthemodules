<?php

/**
 * @file
 * Definition of \Drupal\ckeditor_widgets\Plugin\CKEditorPlugin\AnchorLink.
 */
namespace Drupal\ckeditor_widgets\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "widgetcommon" plugin.
 *
 * @CKEditorPlugin(
 *   id = "widgetcommon",
 *   label = @Translation("CKEditor Common Widgets"),
 *   module = "ckeditor_widgets"
 * )
 */
class WidgetCommon extends CKEditorPluginBase {

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
     */
    function getFile() {
        return drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetcommon/plugin.js';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(Editor $editor) {
        return array();
    }
    /**
     * {@inheritdoc}
     */
    public function getLibraries(Editor $editor) {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function isInternal() {
        return FALSE;
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons().
     */
    function getButtons() {
        return array(
            'widgetcommonBox' => array(
                'label' => $this->t('Insert box'),
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetcommon/icons/widgetcommonBox.png',
            ),
            'widgetcommonQuotebox' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetcommon/icons/widgetcommonQuotebox.png',
                'label' => $this->t('Insert quote box'),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(Editor $editor) {
        return array();
    }
}
