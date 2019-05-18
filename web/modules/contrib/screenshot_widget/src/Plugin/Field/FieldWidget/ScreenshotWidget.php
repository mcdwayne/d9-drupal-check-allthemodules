<?php

namespace Drupal\screenshot_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\file\Entity\File;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Plugin implementation of the 'screenshot_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "screenshot_widget",
 *   label = @Translation("Screenshot widget"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ScreenshotWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'screenshot_selector' => '',
        'screenshot_controls' => FALSE,
        'time_limit' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['screenshot_selector'] = [
      '#title' => t('Screenshot Selector'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('screenshot_selector'),
      '#weight' => 15,
    ];
    $element['screenshot_controls'] = [
      '#title' => t('Screenshot Controls'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('screenshot_controls'),
      '#weight' => 15,
    ];
    $element['time_limit'] = [
      '#title' => t('Time limit in sec'),
      '#description' => $this->t('It is used as limit time for html2canvas processing. If html2canvas cann\'t generate screenshot it will submit form in any case.'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('time_limit'),
      '#weight' => 16,
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][screenshot_controls]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#process'][] = [get_class($this), 'processScreenshotWidget'];
    $element['#previous_value_callback'] = $element['#value_callback'];
    $element['#value_callback'] = [get_class($this), 'valueCallbackScreenshotWidget'];

    $element['#screenshot_controls'] = $this->getSetting('screenshot_controls');
    $element['screenshot_data'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'data-time-limit' => $this->getSetting('time_limit'),
        'data-screenshot-selector' => $this->getSetting('screenshot_selector'),
        'data-screenshot-controls' => $this->getSetting('screenshot_controls') ? '1' : '0',
        'class' => [
          'screenshot-element'
        ]
      ],
      '#attached' => [
        'library' => [
          'screenshot_widget/screenshot-widget'
        ]
      ]
    ];
    $form['#attributes']['class'][] = 'screenshot-form';
    $form['#attributes']['data-screenshot-progress-indicator'] = $this->getSetting('progress_indicator');

    return $element;
  }

  /**
   * Form API callback: Processes a screenshot widget element.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function processScreenshotWidget($element, FormStateInterface $form_state, $form) {
    if ($element['#screenshot_controls']) {
      $element['upload']['#attributes']['class'][] = 'hidden';
      unset($element['upload_button']['#attributes']['class']);
      $element['upload_button']['#attributes']['class'][] = 'make-screenshot-button';
      $element['upload_button']['#value'] = t('Make screenshot');
    }
    else {
      foreach (Element::children($element) as $key) {
        $element[$key]['#attributes']['class'][] = 'hidden';
      }
      $element['upload_button']['#access'] = FALSE;
      $element['#title_display'] = 'hidden';
      $element['#description_display'] = 'hidden';
    }

    return $element;
  }

  /**
   * Value callback for screenshot widget element.
   */
  public static function valueCallbackScreenshotWidget(&$element, $input, FormStateInterface $form_state) {
    if (($input !== FALSE) && !empty($input['screenshot_data'])) {
      if ($data = base64_decode(str_replace('data:image/jpeg;base64,', '', $input['screenshot_data']))) {

        //See file_save_data();
        //Code below from file_save_data function.
        $destination = isset($element['#upload_location']) ? $element['#upload_location'] : NULL;
        if (isset($destination) && !file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
          \Drupal::logger('file')->notice('The upload directory %directory for the file field %name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', ['%directory' => $destination, '%name' => $element['#field_name']]);
          $form_state->setError($element, t('The file could not be uploaded.'));
          return FALSE;
        }

        $destination = "{$destination}/screenshot.jpeg";
        $replace = FILE_EXISTS_RENAME;
        $user = \Drupal::currentUser();

        if (empty($destination)) {
          $destination = file_default_scheme() . '://';
        }
        if (!file_valid_uri($destination)) {
          \Drupal::logger('file')->notice('The data could not be saved because the destination %destination is invalid. This may be caused by improper use of file_save_data() or a missing stream wrapper.', ['%destination' => $destination]);
          \Drupal::messenger()->addError(t('The data could not be saved because the destination is invalid. More information is available in the system log.'));
          return FALSE;
        }

        if ($uri = file_unmanaged_save_data($data, $destination, $replace)) {
          // Create a file entity.
          $file = File::create([
            'uri' => $uri,
            'uid' => $user->id(),
            // We need files with permanent status.
            // Anonymous users do not have access to temporary ones.
            'status' => 1,
          ]);
          // If we are replacing an existing file re-use its database record.
          // @todo Do not create a new entity in order to update it. See
          //   https://www.drupal.org/node/2241865.
          if ($replace == FILE_EXISTS_REPLACE) {
            $existing_files = entity_load_multiple_by_properties('file', ['uri' => $uri]);
            if (count($existing_files)) {
              $existing = reset($existing_files);
              $file->fid = $existing->id();
              $file->setOriginalId($existing->id());
              $file->setFilename($existing->getFilename());
            }
          }
          // If we are renaming around an existing file (rather than a directory),
          // use its basename for the filename.
          elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
            $file->setFilename(drupal_basename($destination));
          }

          $file->save();
        }
      }


      $input['fids'] = ($file) ? $file->id() : '';
      $input['screenshot_data'] = '';
    }

    return call_user_func_array($element['#previous_value_callback'], [&$element, $input, &$form_state]);
  }

}
