<?php

/**
 * @file
 * Definition of \Drupal\ckeditor_widgets\Plugin\CKEditorPlugin\AnchorLink.
 */
namespace Drupal\ckeditor_widgets\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "widgetbootstrap" plugin.
 *
 * @CKEditorPlugin(
 *   id = "widgetbootstrap",
 *   label = @Translation("CKEditor Bootstrap Widgets"),
 *   module = "ckeditor_widgets"
 * )
 */
class WidgetBootstrap extends CKEditorPluginBase {

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
     */
    function getFile() {
        return drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/plugin.js';
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
            'widgetbootstrapLeftCol' => array(
                'label' => $this->t('Insert left column box'),
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapLeftCol.png',
            ),
            'widgetbootstrapRightCol' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapRightCol.png',
                'label' => $this->t('Insert right column box'),
            ),
            'widgetbootstrapTwoCol' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapTwoCol.png',
                'label' => $this->t('Insert two column box'),
            ),
            'widgetbootstrapThreeCol' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapThreeCol.png',
                'label' => $this->t('Insert three column box'),
            ),
            'widgetbootstrapAlert' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapAlert.png',
                'label' => $this->t('Insert alert box'),
            ),
            'widgetbootstrapAccordion' => array(
                'image' => drupal_get_path('module', 'ckeditor_widgets') . '/js/plugins/widgetbootstrap/icons/widgetbootstrapAccordion.png',
                'label' => $this->t('Insert accordion box'),
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
