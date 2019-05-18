<?php

namespace Drupal\owlcarousel2\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\owlcarousel2\Entity\OwlCarousel2;
use Drupal\owlcarousel2\OwlCarousel2Item;
use Drupal\owlcarousel2\Util;

/**
 * Class addVideoForm.
 *
 * @package Drupal\owlcarousel2\Form
 */
class AddVideoForm extends AddItemForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'owlcarousel2_add_video_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $owlcarousel2 = NULL, $item_id = NULL) {
    $form['#title'] = $this->t('Carousel | Add Video');

    $form_state->set('owlcarousel2', $owlcarousel2);

    // Check if it is an edition.
    if ($item_id) {
      $carousel = OwlCarousel2::load($owlcarousel2);
      $item     = $carousel->getItem($item_id);

      $form['item_id'] = [
        '#type'  => 'value',
        '#value' => $item_id,
      ];

      $form['weight'] = [
        '#type'  => 'value',
        '#value' => $item['weight'],
      ];
    }

    $form['video_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Video Url'),
      '#description'   => $this->t('Youtube or Vimeo Url'),
      '#default_value' => (isset($item['video_url'])) ? $item['video_url'] : '',
      '#required'      => TRUE,
    ];

    $form['item_label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Item label'),
      '#description'   => $this->t('Used if you configure the carousel to display text navigation.'),
      '#default_value' => (isset($item['item_label']) && $item['item_label']) ? $item['item_label'] : '',
    ];

    $form['navigation_image_id'] = [
      '#type'            => 'managed_image',
      '#title'           => $this->t('Navigation Image'),
      '#description'     => $this->t('Image to be used on the navigation.'),
      '#upload_location' => 'public://owlcarousel2',
      '#required'        => FALSE,

      '#default_value' => isset($item['navigation_image_id']) && is_numeric($item['navigation_image_id']) ? ['fids' => $item['navigation_image_id']] : '',

      '#multiple'           => FALSE,
      '#uploda_validators'  => [
        'file_validate_extensions' => ['png, gif, jpg, jpeg'],
      ],
      '#progress_indicator' => 'bar',
      '#progress_message'   => $this->t('Please wait...'),
    ];

    $form['youtube_settings'] = [
      '#type'  => 'details',
      '#title' => $this->t('Youtube video configuration'),
    ];

    $form['youtube_settings']['youtube_controls'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Display video controls'),
      '#description'   => $this->t('Hide or show video controls.'),
      '#default_value' => (isset($item['youtube_controls'])) ? $item['youtube_controls'] : TRUE,
    ];

    $form['youtube_settings']['youtube_showinfo'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show info'),
      '#description'   => $this->t('Show youtube header bar.'),
      '#default_value' => (isset($item['youtube_showinfo'])) ? $item['youtube_showinfo'] : TRUE,
    ];

    $form['youtube_settings']['youtube_rel'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show related videos'),
      '#description'   => $this->t('Show related videos in the end of the video.'),
      '#default_value' => (isset($item['youtube_rel'])) ? $item['youtube_rel'] : FALSE,
    ];

    $form['youtube_settings']['youtube_loop'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Loop'),
      '#description'   => $this->t('Repeat the video after it ends.'),
      '#default_value' => (isset($item['youtube_loop'])) ? $item['youtube_loop'] : FALSE,
    ];

    $form += parent::buildForm($form, $form_state, $owlcarousel2, $item_id);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, OwlCarousel2 $carousel = NULL) {
    $operation           = $form_state->getValue('operation');
    $owlcarousel2_id     = $form_state->getStorage()['owlcarousel2'];
    $carousel            = OwlCarousel2::load($owlcarousel2_id);
    $item                = new OwlCarousel2Item([]);
    $item_array          = $item->getArray();
    $current_item        = $carousel->getItem($form_state->getValue('item_id'));
    $navigation_image_id = $form_state->getValue('navigation_image_id');
    $navigation_image_id = isset($navigation_image_id[0]) ? $navigation_image_id[0] : NULL;

    // Prepare item settings.
    $item_array['type']            = 'video';
    $item_array['item_label_type'] = 'custom_title';
    foreach ($item_array as $setting => $value) {
      if (!in_array($setting, ['type', 'item_label_type'])) {
        $item_array[$setting] = $form_state->getValue($setting);
      }
    }

    // Check if slide navigation image file has changed.
    if ($navigation_image_id && !isset($current_item['navigation_image_id']) || ($current_item['navigation_image_id'] != $navigation_image_id) && $navigation_image_id) {
      $previous = isset($current_item['navigation_image_id']) ? $current_item['navigation_image_id'] : 0;
      Util::changeFile($navigation_image_id, $carousel, $previous);
    }

    if ($operation == 'add') {
      $item = new OwlCarousel2Item($item_array);
      $carousel->addItem($item);
    }
    else {
      $item_array['id']     = $form_state->getValue('item_id');
      $item_array['weight'] = $form_state->getValue('weight');
      $item                 = new OwlCarousel2Item($item_array);
      $carousel->updateItem($item);
    }

    parent::submitForm($form, $form_state, $carousel);
  }

}
