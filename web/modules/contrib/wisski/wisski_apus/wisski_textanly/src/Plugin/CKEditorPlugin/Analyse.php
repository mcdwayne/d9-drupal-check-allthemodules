<?php

/**
 * @file
 * Contains \Drupal\wisski_textanly\Plugin\CKEditorPlugin\Analyse.
 */

namespace Drupal\wisski_textanly\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "wisski_analyse" plugin.
 *
 * @CKEditorPlugin(
 *   id = "wisski_analyse",
 *   label = @Translation("Analyse"),
 *   module = "wisski_textanly"
 * )
 */
class Analyse extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, ContainerFactoryPluginInterface {


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
    return drupal_get_path('module', 'wisski_textanly') . '/js/plugins/analyse/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();
    $settings = $settings['plugins']['wisski_analyse'];

    return array(
      'wisski_analyse' => array(
        'automatic' => $settings['automatic'],
        'showButton' => $settings['show_button'],
        'pipe' => $settings['pipe'],
      ),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
//    return array();
    return array(
      'wisskiAnalyse' => array(
        'label' => t('Analyse'),
        'image' => drupal_get_path('module', 'wisski_textanly') . '/js/plugins/analyse/analyse.png',
      ),
      'wisskiAnalysisLog' => array(
        'label' => t('Show Analysis Log'),
        'image' => drupal_get_path('module', 'wisski_textanly') . '/js/plugins/analyse/delete.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $settings = $settings['plugins']['wisski_analyse'];

    $service = \Drupal::service('wisski_pipe.pipe');
    $pipes = $service->loadMultiple();
    $options_pipes = [];
    foreach ($pipes as $pipe) {
      $options_pipes[$pipe->id()] = $pipe->label();
    }
    natsort($options_pipes);
    
    $form['automatic'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Perform automatic analysis'),
      '#default_value' => $settings['automatic'],
    );
    $form['show_button'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show toolbar button'),
      '#default_value' => $settings['show_button'],
    );
    $form['pipe'] = array(
      '#type' => 'select',
      '#title' => $this->t('Pipe for analysis'),
      '#default_value' => $settings['pipe'],
      '#options' => $options_pipes,
    );


    return $form;
  }
}
