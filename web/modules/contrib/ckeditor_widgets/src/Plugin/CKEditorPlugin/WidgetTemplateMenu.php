<?php

/**
 * @file
 * Definition of \Drupal\ckeditor_widgets\Plugin\CKEditorPlugin\AnchorLink.
 */
namespace Drupal\ckeditor_widgets\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "widgettemplatemenu" plugin.
 *
 * @CKEditorPlugin(
 *   id = "widgettemplatemenu",
 *   label = @Translation("CKEditor Template Menu Widgets"),
 *   module = "ckeditor_widgets"
 * )
 */
class WidgetTemplateMenu extends CKEditorPluginBase {

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
     */
    function getFile() {
        return drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgettemplatemenu/plugin.js';
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
            'oembed' => array(
                'label' => $this->t('Insert media'),
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgettemplatemenu/extraIcons/oembed.png',
            ),
            'codeSnippet' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgettemplatemenu/extraIcons/codesnippet.png',
                'label' => $this->t('Insert code snippet'),
            ),
            'leaflet' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgettemplatemenu/extraIcons/leaflet.png',
                'label' => $this->t('Insert leaflet map'),
            ),
            'FontAwesome' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgettemplatemenu/extraIcons/fontawesome.png',
                'label' => $this->t('Insert Font Awesome icon'),
            ),
            'WidgetTemplateMenu' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgettemplatemenu/icons/widgettemplatemenu.png',
                'label' => $this->t('Add Template Menu'),
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
