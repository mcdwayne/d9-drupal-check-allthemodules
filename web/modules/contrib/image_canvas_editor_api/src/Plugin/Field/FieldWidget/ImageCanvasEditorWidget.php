<?php

namespace Drupal\image_canvas_editor_api\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\image_canvas_editor_api\Plugin\EditorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'image_canvas_editor' field widget.
 *
 * @FieldWidget(
 *   id = "image_canvas_editor",
 *   label = @Translation("Image canvas editor"),
 *   field_types = {"image"},
 * )
 */
class ImageCanvasEditorWidget extends ImageWidget {

  /**
   * Image editor plugin manager.
   *
   * @var \Drupal\image_canvas_editor_api\Plugin\EditorPluginManager
   */
  protected $pluginManager;

  /**
   * ImageCanvasEditorWidget constructor.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, EditorPluginManager $plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('element_info'),
      $container->get('plugin.manager.image_editor_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $editors = $this->pluginManager->getDefinitions();
    $opts = [];
    foreach ($editors as $editor) {
      $opts[$editor['id']] = $editor['label'];
    }
    $form['editor'] = [
      '#title' => $this->t('Editor'),
      '#type' => 'select',
      '#options' => $opts,
      '#default_setting' => $this->getSetting('editor'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // Add the part about the editor.
    $editor_setting = $this->getSetting('editor');
    $editors = $this->pluginManager->getDefinitions();
    if (isset($editor_setting)) {
      if (!empty($editors[$editor_setting])) {
        $editor_label = $editors[$editor_setting]['label'];
      }
      else {
        $editor_label = t('Broken/Missing.');
      }
    }
    else {
      // Use the first one.
      $editor = reset($editors);
      $editor_label = $editor['label'];
    }
    $summary[] = t('Editor: @editor', [
      '@editor' => $editor_label,
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);
    if (!empty($element['#files']) && $element['#preview_image_style']) {
      $file = reset($element['#files']);
      $element['preview']['#attributes'] = [
        'data-fid' => $file->id(),
      ];
      $field_name = $element['#field_name'];
      // Find the form mode.
      // @todo: This seems a bit hacky.
      $form_mode = 'default';
      $is_inline = FALSE;
      foreach ($element['#array_parents'] as $parent_name) {
        if ($parent_name === 'inline_entity_form') {
          $is_inline = TRUE;
          break;
        }
      }
      if ($is_inline) {
        $inline_form = NestedArray::getValue($form, array_slice($element['#array_parents'], 0, 4));
        $form_mode = $inline_form['#form_mode'];
      }
      $bundle = self::getBundle($element, $form_state);
      $link = [
        '#attached' => ['library' => ['core/drupal.ajax']],
        '#type' => 'link',
        '#url' => Url::fromRoute('image_canvas_editor_api.editor', [
          'bundle' => $bundle,
          'field_name' => $field_name,
          'form_mode' => $form_mode,
          'entity_type' => $element['#entity_type'],
          'fid' => $file->id(),
        ]),
        '#title' => t('Edit image'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
          ],
          'data-dialog-type' => 'dialog',
          'data-dialog-options' => Json::encode([
            'width' => 1048,
            'height' => 1048,
            'classes' => ["ui-dialog-titlebar-close" => "image-canvas-editor-api"],
          ]),
        ],
        '#access' => \Drupal::currentUser()->hasPermission('use image canvas editors'),
      ];
      \Drupal::moduleHandler()
        ->alter('image_canvas_editor_api_edit_link', $link);
      $element['edit'] = $link;
    }
    return $element;
  }

  /**
   * Get the bundle from the element, somehow.
   */
  public static function getBundle($element, FormStateInterface $form_state) {
    $bundle = NULL;
    if (isset($element['#bundle'])) {
      return $element['#bundle'];
    }
    if ($form_object = $form_state->getFormObject()) {
      if ($form_object instanceof ContentEntityForm) {
        return $form_object->getEntity()->bundle();
      }
    }
    return $bundle;
  }

}
