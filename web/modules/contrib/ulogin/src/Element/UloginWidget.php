<?php

namespace Drupal\ulogin\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\Html;
use Drupal\ulogin\UloginHelper;

/**
 * Provides Ulogin widget.
 *
 * @FormElement("ulogin_widget")
 */
class UloginWidget extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $enabled_providers = [
      'vkontakte',
      'odnoklassniki',
      'mailru',
      'facebook',
      'twitter',
      'google',
      'yandex',
      'livejournal',
      'openid'
    ];
    $enabled_providers = array_filter(\Drupal::config('ulogin.settings')
      ->get('providers_enabled') ?: array_combine($enabled_providers, $enabled_providers));
    $main_providers = ['vkontakte', 'odnoklassniki', 'mailru', 'facebook'];
    $main_providers = array_filter(\Drupal::config('ulogin.settings')
      ->get('providers_main') ?: array_combine($main_providers, $main_providers));
    $required_fields = [
      'first_name',
      'last_name',
      'email',
      'nickname',
      'bdate',
      'sex',
      'photo',
      'photo_big',
      'country',
      'city'
    ];
    $required_fields = array_filter(\Drupal::config('ulogin.settings')
      ->get('fields_required') ?: array_combine($required_fields, $required_fields));
    $optional_fields = array_filter(\Drupal::config('ulogin.settings')
      ->get('fields_optional') ?: array_combine(['phone'], ['phone']));

    $class = get_class($this);
    return [
      '#input' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderUloginWidget'],
      ],
      '#theme' => 'ulogin_widget',
      '#theme_wrappers' => ['form_element'],
      '#ulogin_id' => 'ulogin',
      '#ulogin_widget_id' => \Drupal::config('ulogin.settings')
        ->get('widget_id') ?: '',
      '#title' => \Drupal::config('ulogin.settings')->get('widget_title') ?: '',
      '#weight' => \Drupal::config('ulogin.settings')
        ->get('widget_weight') ?: -100,
      '#ulogin_display' => \Drupal::config('ulogin.settings')
        ->get('display') ?: 'panel',
      '#ulogin_fields_required' => implode(',', $required_fields),
      '#ulogin_fields_optional' => implode(',', $optional_fields),
      '#ulogin_providers' => implode(',', array_intersect_assoc($main_providers, $enabled_providers)),
      '#ulogin_hidden' => implode(',', array_diff_assoc($enabled_providers, $main_providers)),
      '#ulogin_destination' => \Drupal::config('ulogin.settings')
        ->get('destination') ?: '',
      '#ulogin_redirect' => \Drupal::config('ulogin.settings')
        ->get('redirect') ?: 0,
      '#ulogin_icons_path' => \Drupal::config('ulogin.settings')
        ->get('icons_path') ?: '',
      '#ulogin_icons' => [],
      '#attached' => [
        'library' => [
          'ulogin/async',
        ],
      ],
    ];
  }

  /**
   * Render API callback: Hides display of the upload or remove controls.
   *
   * Upload controls are hidden when a file is already uploaded. Remove controls
   * are hidden when there is no file attached. Controls are hidden here instead
   * of in \Drupal\file\Element\ManagedFile::processManagedFile(), because
   * #access for these buttons depends on the managed_file element's #value. See
   * the documentation of \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   * for more detailed information about the relationship between #process,
   * #value, and #access.
   *
   * Because #access is set here, it affects display only and does not prevent
   * JavaScript or other untrusted code from submitting the form as though
   * access were enabled. The form processing functions for these elements
   * should not assume that the buttons can't be "clicked" just because they are
   * not displayed.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   * @see \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   */
  public static function preRenderUloginWidget($element) {
    // If we already have a file, we don't want to show the upload controls.
    $element['#ulogin_id'] = Html::getId($element['#ulogin_id']);
    $element['#attached']['drupalSettings']['ulogin'][] = $element['#ulogin_id'];

    if ($element['#ulogin_widget_id']) {
      $element['#theme'] = 'ulogin_widget_id';
      return $element;
    }

    if ($element['#ulogin_redirect']) {
      $callback = 'Drupalulogintoken';
      $redirect = '';
      $element['#attached']['library'][] = 'ulogin/ulogin';
    }
    else {
      $callback = '';
      $redirect = UloginHelper::tokenUrl($element['#ulogin_destination']);
    }

    $element['#ulogin_data'] = 'display=' . $element['#ulogin_display'] .
      ';fields=' . $element['#ulogin_fields_required'] .
      ';optional=' . $element['#ulogin_fields_optional'] .
      ';callback=' . $callback .
      ';redirect_uri=' . $redirect;

    if ($element['#ulogin_display'] == 'window') {
      $element['#theme'] = 'ulogin_widget_window';
      return $element;
    }

    if ($element['#ulogin_display'] == 'buttons') {
      $element['#theme'] = 'ulogin_widget_buttons';

      $icons = [];
      if (!empty($element['#ulogin_icons_path'])) {
        foreach (file_scan_directory($element['#ulogin_icons_path'], '//') as $icon) {
          $icons[$icon->name] = $icon->uri;
        }
      }
      if (empty($icons)) {
        $icons = $element['#ulogin_icons'];
      }

      $element['icons'] = [];
      foreach ($icons as $key => $value) {
        $image_info = \Drupal::service('image.factory')->get($value);
        $element['icons'][] = [
          '#theme' => 'image',
          '#uri' => $value,
          '#alt' => $key,
          '#title' => $key,
          '#width' => $image_info->getWidth(),
          '#height' => $image_info->getHeight(),
          '#attributes' => [
            'data-uloginbutton' => $key,
            'class' => 'ulogin-icon-' . $key
          ],
        ];
      }

      return $element;
    }

    $element['#ulogin_data'] .= ';providers=' . $element['#ulogin_providers'] .
      ';hidden=' . $element['#ulogin_hidden'];

    return $element;
  }

}
