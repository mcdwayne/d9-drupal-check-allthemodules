<?php

/**
 * @file
 * Contains \Drupal\wisski_ckeditor\Plugin\CKEditorPlugin\Annotation.
 */

namespace Drupal\wisski_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "wisski_ckeditor" plugin.
 *
 * @CKEditorPlugin(
 *   id = "wisski_annotation",
 *   label = @Translation("Annotation"),
 *   module = "wisski_ckeditor"
 * )
 */
class Annotation extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, ContainerFactoryPluginInterface {


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/annotation/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'wisski_annotation' => array(
      ),
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'wisski_apus/annotation',
      'wisski_apus/infobox',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
//    return array();
    return array(
      'wisskiAddAnnotation' => array(
        'label' => t('Add Annotation'),
        'image' => drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/annotation/annotation.png',
      ),
      'wisskiDeleteAnnotation' => array(
        'label' => t('Delete Annotation'),
        'image' => drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/annotation/delete.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {

    return $form;
  }
}
