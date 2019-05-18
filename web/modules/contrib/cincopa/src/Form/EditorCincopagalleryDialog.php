<?php

namespace Drupal\cincopa\Form;

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
use Drupal\cincopa\Controller\CincopaGallery;

/**
 * Provides an image dialog for text editors.
 */
class EditorCincopagalleryDialog extends FormBase {

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
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
    return 'editor_cincopa_gallery_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
   

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['cincopa_iframe'] = array(
      '#type' => 'inline_template ',
      '#template' => '<iframe src="{{ url }}"></iframe>',
      '#context' => array('url' => 'www.google.com')
    );

    
    $form['custom_iframe'] = array(
      '#title' => t('Popup Iframe'),
      '#type' => 'iframe',
      '#url' => 'www.cincopa.com',
    );

    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(array('fid', 0));
    if (!empty($fid)) {
      $file = $this->fileStorage->load($fid);
      $file_url = file_create_url($file->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = file_url_transform_relative($file_url);
      $form_state->setValue(array('attributes', 'src'), $file_url);
      $form_state->setValue(array('attributes', 'data-entity-uuid'), $file->uuid());
      $form_state->setValue(array('attributes', 'data-entity-type'), 'file');
    }

    // When the alt attribute is set to two double quotes, transform it to the
    // empty string: two double quotes signify "empty alt attribute". See above.
    if (trim($form_state->getValue(array('attributes', 'alt'))) === '""') {
      $form_state->setValue(array('attributes', 'alt'), '');
    }



    $image_style = $form_state->getValue('image_style');
    $image_style_popup = $form_state->getValue('image_style_popup');
    $config_name = "image.style." . $image_style_popup;
    $image_style_popup_settings = \Drupal::config($config_name)->getRawData();
    $popup_width = 750;
    foreach ($image_style_popup_settings['effects'] as $key => $effect) {
      if ($effect['id'] == 'image_scale') {
        $popup_width = $effect['data']['width'];
      }
    }
    $display_image = ImagePopup::render($fid,$image_style);
    $absolute_path = $display_image['#url_popup'];
    global $base_url;
    $align = $form_state->getValue('align');
    $data_img_popup = $file->uuid() . ':' . $form_state->getValue(array('attributes', 'alt')) .':' . $image_style . ':' . $image_style_popup . ':' . $align;
    $img = "<img class='display_image' data-img-popup='" . $data_img_popup . "'  src='" . $absolute_path . "'><img>";
    $img_link = "<a href='" . $base_url . "/image_popup/render/" . $fid. "/" .  $image_style_popup. "' class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":". $popup_width ."}'>" . $img . "</a>";
    $align_class = '';
    if (!empty($align) && $align != 'none') {
      $align_class = 'align-' . $align;
    }
    $image_render = '<span class="' . $align_class . '">' . $img_link . '</span>';
    $form_state->setValue('image_render', $image_render);
    $test = $form_state->getValues();

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