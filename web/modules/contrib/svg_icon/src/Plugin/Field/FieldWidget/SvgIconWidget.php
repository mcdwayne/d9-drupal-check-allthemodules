<?php

namespace Drupal\svg_icon\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\file\Element\ManagedFile;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\svg_icon\Svg;

/**
 * Plugin implementation of the 'svg_icon_widget' widget.
 *
 * @FieldWidget(
 *   id = "svg_icon_widget",
 *   label = @Translation("Svg Icon"),
 *   field_types = {
 *     "svg_icon"
 *   }
 * )
 */
class SvgIconWidget extends FileWidget {

  /**
   * An empty selection.
   */
  const EMPTY_SELECTION = '_none';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + ['allow_existing' => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['allow_existing'] = [
      '#title' => $this->t('Allow user to attach existing SVGs'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('allow_existing'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#svg_id'] = $items[$delta]->svg_id;

    if ($this->getSetting('allow_existing') && empty($element['#default_value']['target_id'])) {
      $element['attach_existing'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Use Existing Icon Set'),
      ];
      $selection = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance([
        'target_type' => 'file',
        'handler' => 'svg_selection',
        'target_type' => $this->getFieldSetting('target_type'),
      ]);
      // @todo, possibly allow user to choose autocomplete vs select.
      // $element['attach_existing']['selection'] = [
      //   '#type' => 'entity_autocomplete',
      //   '#selection_handler' => 'svg_selection',
      //   '#target_type' => $this->getFieldSetting('target_type'),
      //   '#title' => $this->t('Search'),
      // ];
      $selection = $selection->getReferenceableEntities();
      $options = $selection['file'] ?? [];
      $element['attach_existing']['selection'] = [
        '#title' => $this->t('Search'),
        '#type' => 'select',
        '#empty_option' => $this->t('Select file'),
        '#empty_value' => static::EMPTY_SELECTION,
        '#options' => $options,
        '#ajax' => [
          'callback' => [static::class, 'attachExistingAjaxCallback'],
          'method' => 'replace',
        ],
      ];
    }
    return $element;
  }

  /**
   * Ajax callback for the attach_existing form element.
   */
  public static function attachExistingAjaxCallback($form, FormStateInterface $form_state) {
    $element_path = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($element_path);
    array_pop($element_path);
    $element = NestedArray::getValue($form, $element_path);
    unset($element['attach_existing']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {

    // Share an AJAX wrapper with the file upload button.
    $element['attach_existing']['selection']['#ajax']['wrapper'] = $element['upload_button']['#ajax']['wrapper'];

    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];
    $element = parent::process($element, $form_state, $form);

    // @todo, properly evaluate and address security concerns instead of
    // wrapping in a trusted permission.
    if (!\Drupal::currentUser()->hasPermission('use svg icon upload widget')) {
      $element['upload']['#disabled'] = TRUE;
      $element['upload']['#suffix'] = t('Insufficient permissions to upload a new SVG, please use an existing one instead.');
    }

    if (!empty($item['fids'][0])) {
      $file = File::load($item['fids'][0]);

      if (!$file || !file_exists($file->getFileUri())) {
        $element['missing']['#markup'] = t('Missing SVG file.');
        return $element;
      }

      $svg_string = file_get_contents($file->getFileUri());
      $svg = new Svg($svg_string);

      $element['preview'] = [
        '#type' => 'fieldset',
        '#title' => $svg->isSprite() ? t('Select icon') : t('SVG Icon preview'),
        '#attached' => ['library' => ['svg_icon/svg_icon.form']],
        '#attributes' => ['class' => ['svg-icon-preview']]
      ];

      if ($svg->isSprite()) {
        $name = array_shift($element['#parents']);
        $selector = $name . '[' . implode('][', $element['#parents']) . '][svg_id]';
        array_unshift($element['#parents'], $name);
        foreach ($svg->getChildren() as $fragment) {
          $wrapper_attributes = new Attribute([
            'data-svg-id-selector' => $selector,
            'data-svg-id' => $fragment->getId(),
            'class' => 'svg-wrapper',
            'title' => $fragment->getTitle(),
          ]);
          if ($element['#svg_id'] == $fragment->getId()) {
            $wrapper_attributes->addClass('svg-default');
          }

          // We implement some default heights in the admin in case we have
          // naughty SVG's.
          $svg_attributes = new Attribute([
            'height' => $fragment->getHeight() ?: '50',
            'width' => $fragment->getWidth() ?: '50',
          ]);

          $element['preview'][$fragment->getId()] = [
            '#theme' => 'svg_icon_admin',
            '#label' => $fragment->getTitle(),
            '#wrapper_attributes' => $wrapper_attributes,
            '#svg_attributes' => $svg_attributes,
            '#icon_url' => file_url_transform_relative($file->url()) . '#' . $fragment->getId(),
          ];
        }
      }
      else {
        $element['preview'][$svg->getId()] = [
          '#theme' => 'image',
          '#uri' => file_url_transform_relative($file->getFileUri()),
          '#width' => '50px',
        ];
      }

      $element['svg_id'] = [
        '#type' => 'hidden',
        '#title' => 'Svg Id',
        '#default_value' => $element['#svg_id'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state) {
    // For some reason the form values have not been processed and the ID must
    // be extracted from the raw user input.
    $raw_selection = NestedArray::getValue($form_state->getUserInput(), array_merge($element['#parents'], ['attach_existing', 'selection']));
    if (empty($input['fids']) && $raw_selection && $raw_selection !== static::EMPTY_SELECTION) {
      $input['fids'] = $raw_selection;
    }
    // We depend on the managed file element to handle uploads.
    return ManagedFile::valueCallback($element, $input, $form_state) + [
      'svg_id' => !empty($input['svg_id']) ? $input['svg_id'] : ''
    ];
  }

}
