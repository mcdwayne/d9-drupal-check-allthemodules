<?php

namespace Drupal\owlcarousel2\Form;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\owlcarousel2\Entity\OwlCarousel2;
use Drupal\owlcarousel2\OwlCarousel2Item;
use Drupal\owlcarousel2\Util;

/**
 * Class AddImageForm.
 *
 * @package Drupal\owlcarousel2\Form
 */
class AddImageForm extends AddItemForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'owlcarousel2_add_image_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $owlcarousel2 = NULL, $item_id = NULL) {
    $form['#title'] = $this->t('Carousel | Add Image');

    $form_state->set('owlcarousel2', $owlcarousel2);
    $carousel = OwlCarousel2::load($owlcarousel2);

    // Check if it is an edition.
    if ($item_id) {
      $item = $carousel->getItem($item_id);

      $default_file['fids'] = $item['file_id'];

      $form['item_id'] = [
        '#type'  => 'value',
        '#value' => $item_id,
      ];

      $form['weight'] = [
        '#type'  => 'value',
        '#value' => $item['weight'],
      ];
    }

    $form['image'] = [
      '#type'            => 'managed_image',
      '#title'           => $this->t('Image'),
      '#upload_location' => 'public://owlcarousel2',
      '#required'        => TRUE,

      '#default_value' => $item_id ? $default_file : '',

      '#multiple'           => FALSE,
      '#uploda_validators'  => [
        'file_validate_extensions' => ['png, gif, jpg, jpeg'],
      ],
      '#progress_indicator' => 'bar',
      '#progress_message'   => $this->t('Please wait...'),
    ];

    $image_styles_ids = \Drupal::entityQuery('image_style')
      ->execute();

    $image_styles = [];
    foreach ($image_styles_ids as $key => $value) {
      $image_style = ImageStyle::load($value);
      if ($image_style->status() === TRUE) {
        $image_styles[$key] = $image_style->label();
      }
    }

    $form['image_style'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Image style'),
      '#description'   => $this->t('Style to be used on the carousel.'),
      '#options'       => $image_styles,
      '#required'      => TRUE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['image_style']) && $item['image_style']) ? $item['image_style'] : 'owlcarousel2',
    ];

    $form['advanced'] = [
      '#type'  => 'details',
      '#title' => $this->t('Advanced configuration'),
    ];

    $form['advanced']['entity_configuration'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Entity link configuration'),
    ];

    $form['advanced']['entity_configuration']['entity_id'] = [
      '#type'          => 'entity_autocomplete',
      '#title'         => $this->t('Content to link the carousel item'),
      '#description'   => $this->t('The content to be displayed when the user clicks on the carousel image. Leave empty to not link to anything.'),
      '#default_value' => (isset($item['entity_id'])) ? Node::load($item['entity_id']) : NULL,
      '#required'      => FALSE,
      '#target_type'   => 'node',
    ];

    $form['advanced']['entity_configuration']['display_node_title'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Display node title'),
      '#description'   => $this->t('Check if you whant to display the node title on the carousel slide.'),
      '#default_value' => (isset($item['display_node_title']) && $item['display_node_title']) ? $item['display_node_title'] : FALSE,
    ];

    $view_modes_ids = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', 'node')
      ->execute();

    $view_modes = [];
    foreach ($view_modes_ids as $value) {
      $key       = substr($value, strlen('node.'), strlen($value));
      $view_mode = EntityViewMode::load($value);
      if ($view_mode->status() === TRUE) {
        $view_modes[$key] = $view_mode->label();
      }
    }

    $form['advanced']['entity_configuration']['view_mode'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Node view mode'),
      '#description'   => $this->t('The node view mode to be displayed with the image.'),
      '#options'       => $view_modes,
      '#required'      => FALSE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['view_mode']) && $item['view_mode']) ? $item['view_mode'] : '',
    ];

    $form['advanced']['navigation_configuration'] = [
      '#type'   => 'fieldset',
      '#title'  => $this->t('Navigation configuration'),
      '#prefix' => $this->t('Options bellow to be applied if you chose to display text or images as the navigation links.'),
    ];

    $form['advanced']['navigation_configuration']['item_label_type'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Label type'),
      '#description'   => $this->t('Which label do you want to use on the navigation.'),
      '#options'       => [
        'content_title' => $this->t('Content title'),
        'custom_title'  => $this->t('Custom title'),
      ],
      '#default_value' => (isset($item['item_label_type']) && $item['item_label_type']) ? $item['item_label_type'] : 'content_title',
    ];

    $form['advanced']['navigation_configuration']['item_label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Item label'),
      '#description'   => $this->t('Used if you configure the carousel to display custom text navigation.'),
      '#default_value' => (isset($item['item_label']) && $item['item_label']) ? $item['item_label'] : '',
      '#states'        => [
        'visible' => [':input[name="item_label_type"]' => ['value' => 'custom_title']],
      ],
    ];

    $form['advanced']['navigation_configuration']['navigation_image_id'] = [
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

    $form['advanced']['text_configuration'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Text configuration'),
    ];

    $form['advanced']['text_configuration']['text_to_display'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Text to display with the image'),
      '#description'   => $this->t('Text can be displayed under or over the image.'),
      '#required'      => TRUE,
      '#options'       => [
        'node_text'   => $this->t('Node content text'),
        'custom_text' => $this->t('Custom text'),
      ],
      '#default_value' => (isset($item['text_to_display']) && $item['text_to_display']) ? $item['text_to_display'] : 'node_text',
    ];

    $form['advanced']['text_configuration']['custom_text'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Custom text'),
      '#description'   => $this->t('Custom text to be displayed under or over the image. HTML allowed.'),
      '#default_value' => (isset($item['custom_text']) && $item['custom_text']) ? $item['custom_text'] : '',
      '#rows'          => 6,
      '#states'        => [
        'visible' => [':input[name="text_to_display"]' => ['value' => 'custom_text']],
      ],
    ];

    $form['advanced']['text_configuration']['content_over_image'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Display content text over image'),
      '#description'   => $this->t('Select "Yes" if you want to display the content linked to the image over the image.'),
      '#options'       => [
        'true'  => $this->t('Yes'),
        'false' => $this->t('No'),
      ],
      '#required'      => TRUE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['content_over_image']) && $item['content_over_image']) ? $item['content_over_image'] : 'false',
    ];

    $form['advanced']['text_configuration']['content_vertical_position'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Content vertical position'),
      '#description'   => $this->t('Vertical position in where the content will be shown over the image.'),
      '#options'       => [
        'vertical-top'    => $this->t('Top'),
        'vertical-center' => $this->t('Center'),
        'vertical-bottom' => $this->t('Bottom'),
      ],
      '#required'      => TRUE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['content_vertical_position']) && $item['content_vertical_position']) ? $item['content_vertical_position'] : 'vertical-bottom',
    ];

    $form['advanced']['text_configuration']['content_horizontal_position'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Content horizontal position'),
      '#description'   => $this->t('Horizontal position in where the content will be shown over the image.'),
      '#options'       => [
        'horizontal-left'   => $this->t('Left'),
        'horizontal-center' => $this->t('Center'),
        'horizontal-right'  => $this->t('Right'),
      ],
      '#required'      => TRUE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['content_horizontal_position']) && $item['content_horizontal_position']) ? $item['content_horizontal_position'] : 'horizontal-left',
    ];

    $form['advanced']['text_configuration']['content_position_unit'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Unit to be used in position'),
      '#description'   => $this->t('The content can be moved using position. Select here the unit of measure you want.'),
      '#options'       => [
        '%'  => $this->t('%'),
        'px' => $this->t('Pixels'),
      ],
      '#required'      => TRUE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['content_position_unit']) && $item['content_position_unit']) ? $item['content_position_unit'] : '%',
    ];

    $form['advanced']['text_configuration']['position'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => 'container-inline'],
    ];

    $form['advanced']['text_configuration']['position']['content_position_top'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Position top'),
      '#required'      => FALSE,
      '#step'          => .1,
      '#default_value' => (isset($item['content_position_top']) && $item['content_position_top']) ? $item['content_position_top'] : '',
    ];

    $form['advanced']['text_configuration']['position']['content_position_right'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Position right'),
      '#required'      => FALSE,
      '#step'          => .1,
      '#default_value' => (isset($item['content_position_right']) && $item['content_position_right']) ? $item['content_position_right'] : '',
    ];

    $form['advanced']['text_configuration']['position']['content_position_bottom'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Position bottom'),
      '#required'      => FALSE,
      '#step'          => .1,
      '#default_value' => (isset($item['content_position_bottom']) && $item['content_position_bottom']) ? $item['content_position_bottom'] : '',
    ];

    $form['advanced']['text_configuration']['position']['content_position_left'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Position left'),
      '#required'      => FALSE,
      '#step'          => .1,
      '#default_value' => (isset($item['content_position_left']) && $item['content_position_left']) ? $item['content_position_left'] : '',
    ];

    $form['advanced']['text_configuration']['title_color'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Node title text custom color'),
      '#description'   => $this->t('The note title text color, you can use hexadecimal value as #FFFFFF or rgba(0,0,0,1) for opacity. The last number in rgba is the opacity. Ex. 1 for 100% opaque, 0.5 for 50% opaque/transparent and so on.'),
      '#required'      => FALSE,
      '#default_value' => (isset($item['title_color']) && $item['title_color']) ? $item['title_color'] : '',
    ];

    $form['advanced']['text_configuration']['content_color'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Content text custom color'),
      '#description'   => $this->t('The note content text color, you can use hexadecimal value as #FFFFFF or rgba(0,0,0,1) for opacity. The last number in rgba is the opacity. Ex. 1 for 100% opaque, 0.5 for 50% opaque/transparent and so on.'),
      '#required'      => FALSE,
      '#default_value' => (isset($item['content_color']) && $item['content_color']) ? $item['content_color'] : '',
    ];

    $form['advanced']['text_configuration']['background_color'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Content background custom color'),
      '#description'   => $this->t('The note content background color, you can use hexadecimal value as #FFFFFF or rgba(0,0,0,1) for opacity. The last number in rgba is the opacity. Ex. 1 for 100% opaque, 0.5 for 50% opaque/transparent and so on.'),
      '#required'      => FALSE,
      '#default_value' => (isset($item['background_color']) && $item['background_color']) ? $item['background_color'] : '',
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
    $file_id             = $form_state->getValue('image')[0];
    $navigation_image_id = $form_state->getValue('navigation_image_id');
    $navigation_image_id = isset($navigation_image_id[0]) ? $navigation_image_id[0] : NULL;
    $carousel            = OwlCarousel2::load($owlcarousel2_id);
    $current_item        = $carousel->getItem($form_state->getValue('item_id'));
    $item                = new OwlCarousel2Item([]);
    $item_array          = $item->getArray();

    // Prepare item settings.
    $item_array['type']    = 'image';
    $item_array['file_id'] = $file_id;
    foreach ($item_array as $setting => $value) {
      if (!in_array($setting, ['type', 'file_id'])) {
        $item_array[$setting] = $form_state->getValue($setting);
      }
    }

    // Check if slide image file has changed.
    if ($current_item['file_id'] !== $file_id) {
      Util::changeFile($file_id, $carousel, $current_item['file_id']);
    }

    // Check if slide navigation image file has changed.
    if ($navigation_image_id && !isset($current_item['navigation_image_id']) || (isset($current_item['navigation_image_id']) && $current_item['navigation_image_id'] != $navigation_image_id) && $navigation_image_id) {
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

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (is_numeric($form_state->getValue('entity_id'))) {
      if ($form_state->getValue('view_mode') == '') {
        $form_state->setErrorByName('view_mode', $this->t('Node view mode is required id you what to display a content.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

}
