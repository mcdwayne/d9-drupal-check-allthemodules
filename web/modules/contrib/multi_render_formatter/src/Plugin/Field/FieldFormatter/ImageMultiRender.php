<?php

namespace Drupal\multi_render_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image_multi_render' formatter.
 *
 * @FieldFormatter(
 *   id = "image_multi_render",
 *   label = @Translation("Image Multi Render"),
 *   description = @Translation("Display the referenced images rendered based on a behavior field."),
 *   field_types = {
 *     "image"
 *   },
 * )
 */
class ImageMultiRender extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    $settings = [];
    $settings['image_link'] = '';
    $settings['behavior_field'] = '';
    $settings['image_style'] = [];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Get Current Field.
    $current_field_name = $this->fieldDefinition->getName();

    // Manages all field of bundle fields.
    $target_bundle = $form['#bundle'];
    $target_entity = $form['#entity_type'];

    // Get Compatible field list.
    $behavior_selectors = MultiFomatterHelper::getBehaviorFieldPossible($form['#fields'], $target_entity, $target_bundle, $current_field_name);

    // If no behaviors selector, print error message.
    if (count($behavior_selectors) == 0) {
      $form['item'] = [
        '#type' => 'fieldset',
      ];
      $form['item']['message'] = ['#markup' => t('No compatible behavior selector field detected (boolean or list). Please choose another formatter.')];
      return $form;
    }

    // Make Behavior field selector.
    $form['behavior_field'] = [
      '#type' => 'select',
      '#description' => $this->t('select'),
      '#title' => $this
        ->t('Choose the behavior selector field'),
      '#options' => $behavior_selectors,
      '#default_value' => $this->getSetting('behavior_field'),
    ];

    $link_types = [
      'content' => t('Content'),
      'file' => t('File'),
    ];
    $form['image_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    ];

    // If more than one possible behavior field, add AjaxCallback.
    if (count($behavior_selectors) > 1) {
      $form['behavior_field']['#ajax'] = [
        'wrapper' => 'view_mode_selectors',
        'callback' => [$this, 'ajaxCallback'],
      ];

      $form['view_modes'] = [
        '#prefix' => '<div id="view_mode_selectors">',
        '#suffix' => '</div>',
      ];
    }

    // Get Target Field.
    $target_field = NULL;
    if (count($behavior_selectors) == 1) {
      // If only one possible value, use it.
      $target_field = array_keys($behavior_selectors)[0];
    }
    else {

      // If more than One possible.
      $target_value = [
        'fields',
        $current_field_name,
        'settings_edit_form',
        'settings',
        'behavior_field',
      ];

      if ($form_state->getValue($target_value)) {
        // Listen Ajax.
        $target_field = $form_state->getValue($target_value);
      }
      else {
        // Search in settings.
        $target_field = $this->getSetting('behavior_field');
      }
    }

    // If a behavior field are selected.
    if ($target_field != NULL) {
      $target_bundle = $form['#bundle'];
      $target_entity = $form['#entity_type'];

      // Get list of possible behaviors.
      $values = MultiFomatterHelper::getBehaviorList($target_entity, $target_bundle, $target_field);

      if ($values != NULL) {

        $defaults = $this->getSetting('image_style');
        // Get list of possible view modes.
        $image_styles = image_style_options(FALSE);

        // For Each view, create a selectbox.
        foreach ($values as $key => $label) {
          $form['image_style'][$key] = [
            '#type' => 'select',
            '#options' => $image_styles,
            '#title' => t('View mode for %label behavior', ['%label' => $label]),
            '#default_value' => $defaults[$key] ?? 'default',
            '#required' => TRUE,
          ];
        }
      }
    }

    return $form;

  }

  /**
   * Use Ajax Callback for list of behaviors.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @return mixed
   *   Ajax output.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    $element_to_return = 'view_modes';

    return $form['fields'][$field_name]['plugin']['settings_edit_form']['settings'][$element_to_return];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $configs = $this->getSettings();

    // Get basic data for summary.
    $current_field_name = $this->fieldDefinition->getName();
    $bundle = $this->fieldDefinition->get('bundle');
    $entity_type = $this->fieldDefinition->get('entity_type');

    // Get Compatible field list.
    $fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    $possible_fields = MultiFomatterHelper::getBehaviorFieldPossible(array_keys($fields), $entity_type, $bundle, $current_field_name);

    // If no compatible fields, print error message.
    if (count($possible_fields) == 0) {
      $summary[] = t('No compatible behavior selector field detected (boolean or list). Please choose another formatter.');
      return $summary;
    }
    elseif ($configs['behavior_field'] == '') {
      // If no selection, invite user to configure formatter.
      $summary[] = t('Choose a behavior selector.');
      return $summary;
    }

    // Make summary message.
    $summary[] = t('Behavior source field :') . ' ' . $configs['behavior_field'];
    $summary[] = '';
    $summary[] = t('List of configured styles :');

    $list_options = $image_styles = image_style_options(FALSE);
    $list_behaviors = MultiFomatterHelper::getBehaviorList($entity_type, $bundle, $configs['behavior_field']);

    foreach ($configs['image_style'] as $key => $value) {
      $mode = $list_options[$value];
      $behavior = $list_behaviors[$key];
      $summary[] = t('Use %mode style for %behavior behavior', ['%behavior' => $behavior, '%mode' => $mode]);
    }

    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $items->getEntity();
    $position_field_name = $this->getSetting('behavior_field');
    $field_position = $paragraph->$position_field_name->value;
    $image_style_settings = $this->getSetting('image_style');
    $image_style_setting = $image_style_settings[$field_position] ?? NULL;

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    return $elements;
  }

}
