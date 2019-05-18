<?php

namespace Drupal\filefield_sources_jsonapi\Plugin\FilefieldSource;

use Drupal\filefield_sources\Plugin\FilefieldSource\Remote;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystem;
use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\WidgetInterface;
use Drupal\filefield_sources_jsonapi\Entity\FileFieldSourcesJSONAPI;
use Drupal\image\Entity\ImageStyle;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;

/**
 * FileField source plugin to allow downloading a file from JSON Rest API.
 *
 * @FilefieldSource(
 *   id = "remote_jsonapi",
 *   name = @Translation("JSON API remote URL"),
 *   label = @Translation("JSON API Library"),
 *   description = @Translation("Download a file from JSON Rest API.")
 * )
 */
class RemoteJSONAPI extends Remote {

  const REMOTE_JSONAPI_LISTER_MODAL_WIDTH = 1000;

  /**
   * {@inheritdoc}
   *
   * Based on Remote->value().
   */
  public static function value(array &$element, &$input, FormStateInterface $form_state) {
    if (isset($input['filefield_remote_jsonapi']['url']) && strlen($input['filefield_remote_jsonapi']['url']) > 0 && UrlHelper::isValid($input['filefield_remote_jsonapi']['url']) && $input['filefield_remote_jsonapi']['url'] != FILEFIELD_SOURCE_REMOTE_HINT_TEXT) {
      $field = FieldConfig::loadByName($element['#entity_type'], $element['#bundle'], $element['#field_name']);
      $url = $input['filefield_remote_jsonapi']['url'];

      // Check that the destination is writable.
      $temporary_directory = 'temporary://';
      if (!file_prepare_directory($temporary_directory, FILE_MODIFY_PERMISSIONS)) {
        \Drupal::logger('filefield_sources')->log(E_NOTICE, 'The directory %directory is not writable, because it does not have the correct permissions set.', ['%directory' => \Drupal::service('file_system')->realpath($temporary_directory)]);
        drupal_set_message(t('The file could not be transferred because the temporary directory is not writable.'), 'error');
        return;
      }

      // Check that the destination is writable.
      $directory = $element['#upload_location'];
      $mode = Settings::get('file_chmod_directory', FileSystem::CHMOD_DIRECTORY);

      // This first chmod check is for other systems such as S3, which don't
      // work with file_prepare_directory().
      if (!\Drupal::service('file_system')->chmod($directory, $mode) && !file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        \Drupal::logger('filefield_sources')->log(E_NOTICE, 'File %file could not be copied, because the destination directory %destination is not configured correctly.', ['%file' => $url, '%destination' => \Drupal::service('file_system')->realpath($directory)]);
        drupal_set_message(t('The specified file %file could not be copied, because the destination directory is not properly configured. This may be caused by a problem with file or directory permissions. More information is available in the system log.', ['%file' => $url]), 'error');
        return;
      }

      // Check the basicAuthentication config value and if it is checked, we get
      // the file with basic authentication.
      $source = $input['filefield_remote_jsonapi']['source'];
      $config = FileFieldSourcesJSONAPI::load($source);
      $basic_auth = $config->getBasicAuthentication();
      if ($basic_auth) {
        $myConfig = \Drupal::config('filefield_sources_jsonapi');
        $username = $myConfig->get('username');
        $password = $myConfig->get('password');
      }

      // @todo refactor the three curl function call.
      // Check the headers to make sure it exists and is within the allowed
      // size.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, TRUE);
      curl_setopt($ch, CURLOPT_NOBODY, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADERFUNCTION, [get_called_class(), 'parseHeader']);
      if ($basic_auth) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
      }
      // Causes a warning if PHP safe mode is on.
      @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

      curl_exec($ch);
      $info = curl_getinfo($ch);
      if ($info['http_code'] != 200) {
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        $file_contents = curl_exec($ch);
        $info = curl_getinfo($ch);
      }
      curl_close($ch);

      if ($info['http_code'] != 200) {
        switch ($info['http_code']) {
          case 403:
            $form_state->setError($element, t('The remote file could not be transferred because access to the file was denied.'));
            break;

          case 404:
            $form_state->setError($element, t('The remote file could not be transferred because it was not found.'));
            break;

          default:
            $form_state->setError($element, t('The remote file could not be transferred due to an HTTP error (@code).', ['@code' => $info['http_code']]));
        }
        return;
      }

      // Update the $url variable to reflect any redirects.
      $url = $info['url'];
      $url_info = parse_url($url);

      // Determine the proper filename by reading the filename given in the
      // Content-Disposition header. If the server fails to send this header,
      // fall back on the basename of the URL.
      //
      // We prefer to use the Content-Disposition header, because we can then
      // use URLs like http://example.com/get_file/23 which would otherwise be
      // rejected because the URL basename lacks an extension.
      $filename = static::filename();
      if (empty($filename)) {
        $filename = rawurldecode(basename($url_info['path']));
      }

      $pathinfo = pathinfo($filename);

      // Create the file extension from the MIME header if all else has failed.
      if (empty($pathinfo['extension']) && $extension = static::mimeExtension()) {
        $filename = $filename . '.' . $extension;
        $pathinfo = pathinfo($filename);
      }

      $filename = filefield_sources_clean_filename($filename, $field->getSetting('file_extensions'));
      $filepath = file_create_filename($filename, $temporary_directory);

      if (empty($pathinfo['extension'])) {
        $form_state->setError($element, t('The remote URL must be a file and have an extension.'));
        return;
      }

      // Perform basic extension check on the file before trying to transfer.
      $extensions = $field->getSetting('file_extensions');
      $regex = '/\.(' . preg_replace('/[ +]/', '|', preg_quote($extensions)) . ')$/i';
      if (!empty($extensions) && !preg_match($regex, $filename)) {
        $form_state->setError($element, t('Only files with the following extensions are allowed: %files-allowed.', ['%files-allowed' => $extensions]));
        return;
      }

      // Set progress bar information.
      $options = [
        'key' => $element['#entity_type'] . '_' . $element['#bundle'] . '_' . $element['#field_name'] . '_' . $element['#delta'],
        'filepath' => $filepath,
      ];
      static::setTransferOptions($options);

      $transfer_success = FALSE;
      // If we've already downloaded the entire file because the
      // header-retrieval failed, just ave the contents we have.
      if (isset($file_contents)) {
        if ($fp = @fopen($filepath, 'w')) {
          fwrite($fp, $file_contents);
          fclose($fp);
          $transfer_success = TRUE;
        }
      }
      // If we don't have the file contents, download the actual file.
      else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, [get_called_class(), 'curlWrite']);
        // Causes a warning if PHP safe mode is on.
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        if ($basic_auth) {
          curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
          curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        }
        $transfer_success = curl_exec($ch);
        curl_close($ch);
      }

      $file_size = $info['download_content_length'];

      // Transform image before saving it.
      $image_factory = \Drupal::service('image.factory');
      $image = $image_factory->get($filepath);
      $image_style = isset($element['#filefield_sources_settings']['source_remote_jsonapi']['image_style']) ? $element['#filefield_sources_settings']['source_remote_jsonapi']['image_style'] : NULL;
      if ($image->isValid() && $image_style) {
        $style = ImageStyle::load($image_style);
        if ($style->createDerivative($filepath, $filepath)) {
          $file_size = filesize($filepath);
        }
      }

      // Check file size based off of header information.
      if (!empty($element['#upload_validators']['file_validate_size'][0])) {
        $max_size = $element['#upload_validators']['file_validate_size'][0];
        if ($file_size > $max_size) {
          $form_state->setError($element, t('The remote file is %filesize exceeding the maximum file size of %maxsize.', ['%filesize' => format_size($file_size), '%maxsize' => format_size($max_size)]));
          return;
        }
      }

      if ($transfer_success && $file = filefield_sources_save_file($filepath, $element['#upload_validators'], $element['#upload_location'])) {
        if (!in_array($file->id(), $input['fids'])) {
          $input['fids'][] = $file->id();
        }

        // If the transfer was succesfull we set the CURLOPT_POSTFIELDS.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($basic_auth) {
          curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
          curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        }
        // Causes a warning if PHP safe mode is on.
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'download=1');
        curl_exec($ch);
        curl_close($ch);
      }

      // Delete the temporary file.
      @unlink($filepath);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!isset($element['#filefield_sources_settings']['source_remote_jsonapi']['sources'])) {
      return $element;
    }
    $routing_params = [
      'entity_type' => $element['#entity_type'],
      'bundle' => $element['#bundle'],
      'form_mode' => 'default',
      'field_name' => $element['#field_name'],
      'wrapper' => Html::getClass(implode('-', $element['#field_parents'])),
    ];
    $element['filefield_remote_jsonapi'] = [
      '#weight' => 100.5,
      '#theme' => 'filefield_sources_element',
      '#source_id' => 'remote_jsonapi',
      // Required for proper theming.
      '#filefield_source' => TRUE,
      '#filefield_sources_hint_text' => FILEFIELD_SOURCE_REMOTE_HINT_TEXT,
      '#filefield_sources_remote_jsonapi_routing_params' => $routing_params,
      '#filefield_sources_remote_jsonapi_settings' => $element['#filefield_sources_settings']['source_remote_jsonapi'],
    ];

    $element['filefield_remote_jsonapi']['url'] = [
      '#type' => 'textfield',
      '#description' => filefield_sources_element_validation_help($element['#upload_validators']),
      '#maxlength' => NULL,
      '#attributes' => ['class' => ['visually-hidden']],
    ];
    if (isset($element['#alt_field']) && $element['#alt_field']) {
      $element['filefield_remote_jsonapi']['alt'] = [
        '#type' => 'hidden',
        '#value' => '',
      ];
    }
    if (isset($element['#title_field']) && $element['#title_field']) {
      $element['filefield_remote_jsonapi']['title'] = [
        '#type' => 'hidden',
        '#value' => '',
      ];
    }
    if (isset($element['#description_field']) && $element['#description_field']) {
      $element['filefield_remote_jsonapi']['description'] = [
        '#type' => 'hidden',
        '#value' => '',
      ];
    }
    $element['filefield_remote_jsonapi']['source'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    $class = '\Drupal\file\Element\ManagedFile';
    $ajax_settings = [
      'callback' => [$class, 'uploadAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $element['upload_button']['#ajax']['wrapper'],
      'effect' => 'fade',
      'progress' => [
        'type' => 'bar',
        'path' => 'file/remote/progress/' . $element['#entity_type'] . '/' . $element['#bundle'] . '/' . $element['#field_name'] . '/' . $element['#delta'],
        'message' => t('Starting transfer...'),
      ],
    ];

    $element['filefield_remote_jsonapi']['transfer'] = [
      '#name' => implode('_', $element['#parents']) . '_transfer',
      '#type' => 'submit',
      '#value' => t('Transfer'),
      '#validate' => [],
      '#submit' => ['filefield_sources_field_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#attributes' => ['class' => ['visually-hidden']],
    ];

    return $element;
  }

  /**
   * Theme the output of the remote element.
   *
   * @todo - refactor.
   */
  public static function element($variables) {
    $element = $variables['element'];
    $element['url']['#field_suffix'] = \Drupal::service('renderer')
      ->render($element['transfer']);

    $width = isset($element['#filefield_sources_remote_jsonapi_settings']['modal_width']) ? $element['#filefield_sources_remote_jsonapi_settings']['modal_width'] : self::REMOTE_JSONAPI_LISTER_MODAL_WIDTH;

    $button = [
      '#type' => 'link',
      '#title' => t('Open JSON API browser'),
      '#url' => Url::fromRoute(
        'filefield_sources_jsonapi.modal_browser_form',
        $element['#filefield_sources_remote_jsonapi_routing_params'],
        [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => $width,
            ]),
          ],
        ]
      ),
      '#attributes' => ['class' => ['button']],
    ];

    $rendered_button = \Drupal::service('renderer')->render($button);

    $content = \Drupal::service('renderer')->render($element['url']) . \Drupal::service('renderer')->render($element['alt']) . \Drupal::service('renderer')->render($element['title']) . \Drupal::service('renderer')->render($element['description']) . \Drupal::service('renderer')->render($element['source']) . $rendered_button;

    return '<div class="filefield-source filefield-source-remote_jsonapi clear-block">' . $content . '</div>';
  }

  /**
   * Implements hook_filefield_source_settings().
   */
  public static function settings(WidgetInterface $plugin) {
    $settings = $plugin->getThirdPartySetting('filefield_sources', 'filefield_sources');

    $return['source_remote_jsonapi'] = [
      '#title' => t('JSON Api settings'),
      '#type' => 'details',
      '#description' => t('Enable JSON API browser'),
      '#weight' => 10,
    ];
    $return['source_remote_jsonapi']['sources'] = [
      '#type' => 'checkboxes',
      '#options' => FileFieldSourcesJSONAPI::getSettingsOptionList(),
      '#title' => t('JSON API settings'),
      '#description' => t('Defined JSON API settings at <a href=":url">manage JSON API sources</a> page.', [':url' => Url::fromRoute('entity.filefield_sources_jsonapi.collection')->toString()]),
      '#default_value' => isset($settings['source_remote_jsonapi']['sources']) ? $settings['source_remote_jsonapi']['sources'] : NULL,
      '#element_validate' => [[get_called_class(), 'jsoanApiSourceValidateRequired']],
    ];
    $return['source_remote_jsonapi']['image_style'] = [
      '#type' => 'select',
      '#title' => t('Image style'),
      '#options' => image_style_options(),
      '#description' => t('Transform image file before save.'),
      '#default_value' => isset($settings['source_remote_jsonapi']['image_style']) ? $settings['source_remote_jsonapi']['image_style'] : NULL,
    ];
    $return['source_remote_jsonapi']['modal_width'] = [
      '#type' => 'number',
      '#min' => 400,
      '#title' => t('Modal window width'),
      '#description' => t('Modal window initial width.'),
      '#default_value' => isset($settings['source_remote_jsonapi']['modal_width']) ? $settings['source_remote_jsonapi']['modal_width'] : self::REMOTE_JSONAPI_LISTER_MODAL_WIDTH,
    ];

    return $return;
  }

  /**
   * Custom validation for JSON API source.
   */
  public function jsoanApiSourceValidateRequired($element, FormStateInterface $form_state) {
    // Go 2 levels up.
    $parents = array_slice($element['#parents'], 0, count($element['#parents']) - 2, TRUE);
    $input = NestedArray::getValue($form_state->getValues(), $parents);
    if ($input['sources']['remote_jsonapi'] && empty(array_filter($input['source_remote_jsonapi']['sources']))) {
      $form_state->setError($element, 'JSON API settings are required.');
    }
  }

}
