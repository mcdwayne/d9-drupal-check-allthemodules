<?php

/**
 * @file
 * Contains \Drupal\wisski_ckeditor\Plugin\CKEditorPlugin\QuickEntityPicker.
 */

namespace Drupal\wisski_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Defines the "wisski_ckeditor" plugin.
 *
 * @CKEditorPlugin(
 *   id = "wisski_quick_entity_picker",
 *   label = @Translation("WissKI Quick Entity Picker"),
 *   module = "wisski_ckeditor"
 * )
 */
class QuickEntityPicker extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * The QuickEntityPicker profile storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $linkit_profile_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkitProfileStorage = $linkit_profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('linkit_profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/entityLinkDialog/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'wisski_ckeditor_dialogTitleAdd' => t('Add link'),
      'wisski_ckeditor_dialogTitleEdit' => t('Edit link'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'wisski_apus/entity_picker',
      'core/jquery.ui.effects.slide',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
//    return array();
    return array(
      'EntityPicker' => array(
        'label' => t('WissKI Entity Picker'),
        'image' => drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/entityLinkDialog/entityLinkDialog.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $all_profiles = $this->linkitProfileStorage->loadMultiple();

    $options = array();
    foreach ($all_profiles as $profile) {
      $options[$profile->id()] = $profile->label();
    }

    $form['linkit_profile'] = array(
      '#type' => 'select',
      '#title' => t('Select a linkit profile'),
      '#options' => $options,
      '#default_value' => isset($settings['plugins']['wisski_quick_entity_picker']['linkit_profile']) ? $settings['plugins']['wisski_quick_entity_picker']['linkit_profile'] : '',
      '#empty_option' => $this->t('- Select profile -'),
      '#description' => $this->t('Select the linkit profile you wish to use with this text format.'),
      '#element_validate' => array(
        array($this, 'validateProfileSelection'),
      ),
    );

    return $form;
  }

  /**
   * #element_validate handler for the "wisski_ckeditor_profile" element in settingsForm().
   */
  public function validateProfileSelection(array $element, FormStateInterface $form_state) {
    $toolbar_buttons = $form_state->getValue(array('editor', 'settings', 'toolbar', 'button_groups'));
    if (strpos($toolbar_buttons, '"EntityPicker"') !== FALSE && empty($element['#value'])) {
      $form_state->setError($element, t('Please select the linkit profile you wish to use.'));
    }
  }
}
