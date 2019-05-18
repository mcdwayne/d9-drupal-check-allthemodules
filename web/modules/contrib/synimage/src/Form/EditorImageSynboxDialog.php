<?php

namespace Drupal\synimage\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\synimage\Controller\ImageRenderer;

/**
 * Provides an image dialog for text editors.
 */
class EditorImageSynboxDialog extends FormBase {

  protected $fileStorage;

  /**
   * Constructs a form object for image dialog.
   */
  public function __construct(EntityStorageInterface $file_storage) {
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synimage_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {

    $settings = $this->getEditorSettings($filter_format);
    $config = $this->getEditorConfig($filter_format);

    $defaults = [
      'fid' => NULL,
      'src' => '',
      'alt' => '',
      'align' => 'none',
      'caption' => FALSE,
      'style' => FALSE,
      'colorbox' => FALSE,
      'watermark' => FALSE,
    ];

    // Default values come from the client side, we cache data in form state.
    if (isset($form_state->getUserInput()['editor_object'])) {
      // Data from  text editor sends in the 'editor_object' key.
      // Editor dialog expects  data as <img> attributes.
      $image_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('image_element', $image_element);
      $form_state->setCached(TRUE);

      $synimage_string = FALSE;
      if ($image_element['synimage']) {
        $synimage_string = $image_element['synimage'];
      }
      $synimage = ImageRenderer::decodeSynimage($synimage_string);
      $defaults = [
        'fid' => $this->getFid($image_element),
        'src' => isset($image_element['src']) ? $image_element['src'] : $image_element['src'] = '',
        'alt' => isset($image_element['alt']) ? $image_element['alt'] : $image_element['alt'] = '',
        'align' => isset($image_element['align']) ? $image_element['align'] : $image_element['align']='',
        'caption' => isset($synimage['caption']) ? $synimage['caption'] : $synimage['caption'] = '',
        'style' => isset($synimage['style']) ? $synimage['style'] : $synimage['style'] = '',
        'colorbox' => isset($synimage['colorbox']) ? $synimage['colorbox'] : $synimage['colorbox']='',
        'watermark' => isset($synimage['watermark']) ? $synimage['watermark'] : $synimage['watermark'] ='',
      ];
    }

    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-image-dialog-form">';
    $form['#suffix'] = '</div>';

    if (!$config['upload']) {
      $form['image'] = [
        '#type' => 'details',
        '#title' => $this->t('General settings'),
        '#open' => FALSE,
        '#prefix' => '<div class="hidden">',
        '#suffix' => '</div>',
      ];
    }
    $form['image']['fid'] = [
      '#title' => $this->t('Image'),
      '#type' => 'managed_file',
      '#default_value' => $defaults['fid'],
      '#upload_location' => $settings['location'],
      '#upload_validators' => $settings['validators'],
      '#required' => TRUE,
    ];

    $form['image']['alt'] = [
      '#title' => $this->t('Alternative text'),
      '#placeholder' => $this->t('Short description for the visually impaired'),
      '#type' => 'textfield',
      '#required' => FALSE,
      '#default_value' => $defaults['alt'],
      '#maxlength' => 2048,
    ];

    $form['image']['align'] = [
      '#title' => $this->t('Align'),
      '#type' => 'radios',
      '#options' => [
        'none' => $this->t('None'),
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $defaults['align'],
      '#attributes' => ['class' => ['container-inline']],
    ];

    // When Drupal core's filter_caption is being used, the text editor may
    // offer the ability to in-place edit the image's caption: show a toggle.
    if ($filter_format->filters('filter_caption')->status) {
      $form['image']['caption'] = [
        '#title' => $this->t('Caption'),
        '#type' => 'checkbox',
        '#default_value' => $defaults['caption'],
      ];
    }

    $form['style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#empty_option' => t('None (original image)'),
      '#options' => $this->getStyles('synimage'),
      '#default_value' => $defaults['style'],
    ];
    $form['colorbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('По клику на изображение выводить в окне?'),
      '#required' => FALSE,
      '#default_value' => $defaults['colorbox'],
    ];
    if ($config['watermark']) {
      $form['watermark'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Использовать водяной знак в всплывающем окне?'),
        '#default_value' => $defaults['watermark'],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * Return EditorSettings.
   */
  public function getEditorSettings($filter_format) {
    $editor = editor_load($filter_format->id());
    $image_upload = $editor->getImageUploadSettings();
    $max_dimensions = 0;
    if (!empty($image_upload['max_dimensions']['width']) || !empty($image_upload['max_dimensions']['height'])) {
      $max_dimensions = $image_upload['max_dimensions']['width'] . 'x' . $image_upload['max_dimensions']['height'];
    }
    $max_filesize = min(Bytes::toInt($image_upload['max_size']), file_upload_max_size());
    $settings = [
      'location' => $image_upload['scheme'] . '://' . $image_upload['directory'],
      'validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [$max_filesize],
        'file_validate_image_resolution' => [$max_dimensions],
      ],
    ];

    return $settings;
  }

  /**
   * Return EditorSettings.
   */
  public function getEditorConfig($filter_format) {
    $editor = editor_load($filter_format->id());
    $config = [
      'upload' => FALSE,
      'watermark' => FALSE,
    ];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['synimage'])) {
      $sett = $settings['plugins']['synimage'];
      if (isset($sett['upload'])) {
        $config['upload'] = $sett['upload'];
      }
      if (isset($sett['watermark'])) {
        $config['watermark'] = $sett['watermark'];
      }
    }
    return $config;
  }

  /**
   * Return Styles.
   */
  public function getStyles($keyword) {
    $image_styles = image_style_options(FALSE);
    $styles = [];
    $keyword = '-^' . $keyword . '-';
    foreach ($image_styles as $key => $value) {
      if (preg_match($keyword, $key)) {
        $styles[$key] = $value;
      }
    }
    return $styles;
  }

  /**
   * Return fid.
   */
  public function getFid($image_element) {
    $fid = NULL;
    if (isset($image_element['data-entity-uuid'])) {
      $uuid = trim($image_element['data-entity-uuid']);
      $existing_file = \Drupal::entityManager()->loadEntityByUuid('file', $uuid);
      $fid = [$existing_file->id()];
    }
    return $fid;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(['fid', 0]);

    if (!empty($fid)) {
      $file = $this->fileStorage->load($fid);
      $file->setPermanent();
      $file->save();

      $synimage = [
        'style:' . $form_state->getValue('style'),
        'caption:' . $form_state->getValue('caption'),
        'colorbox:' . $form_state->getValue('colorbox'),
        'watermark:' . $form_state->getValue('watermark'),
      ];

      $form_state->setValue('file-id', $fid);
      $form_state->setValue('file-uuid', $file->uuid());
      $form_state->setValue('synimage', implode(';', $synimage));

      $image = ImageRenderer::render($form_state);
      $form_state->setValue('image_render', $image);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-image-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

}
