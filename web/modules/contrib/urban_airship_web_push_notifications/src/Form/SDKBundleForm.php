<?php

namespace Drupal\urban_airship_web_push_notifications\Form;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;
use Drupal\urban_airship_web_push_notifications\Helper\PushWorkerJs;

/**
 * Configure Airship Web Notifications SDK Bundle settings.
 */
class SDKBundleForm extends ConfigFormBase {

  protected $zip_upload_uri = 'public://urban-airship/';
  protected $allowed_files = ['push-worker.js', 'snippet.html', 'secure-bridge.html'];

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['urban_airship_web_push_notifications.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uawn_unzip_sdk_bundle_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $fid = \Drupal::keyValue('urban_airship_web_push_notifications')->get('sdk_bundle_fid');
    $form = parent::buildForm($form, $form_state);

    if (class_exists('\ZipArchive')) {
      $form['info'] = [
        '#markup' => $this->t('<p>Your SDK zip file can be found in your Airship dashboard. If you need to find this file after completing initial configuration or if you would like to make updates to your configuration, navigate to <em>Channels</em> under the Settings drop-down in the <a href="https://go.urbanairship.com/accounts/login/" target="_blank">Airship Dashboard</a> and then click <em>Web Browsers</em> to expose the <em>Download SDK Bundle</em> button.</p>
          <p>For more information about the contents of the SDK bundle, please see the <a href="https://docs.urbanairship.com/platform/web" target="_blank">Airship documentation</a>.</p>'),
      ];
      $form['bundle_sdk'] = [
        '#type'              => 'managed_file',
        '#title'             => $this->t('Zip File'),
        '#upload_location'   => $this->zip_upload_uri,
        '#description'       => $this->t('SDK Bundle Zip file.'),
        '#upload_validators' => [
          'file_validate_extensions' => ['zip'],
        ],
        '#default_value'     => !empty($fid) ? [$fid] : NULL,
        '#required'          => TRUE,
      ];
      $form['actions']['submit']['#value'] = $this->t('Save SDK Bundle');
    }
    else {
      $form['info'] = [
        '#markup' => $this->t("<p><em>ZipArchive</em> class couldn't be found. Please configure SDK Bundle in <em>settings.php</em>. Instructions can be found in the <em>Readme.md</em> file.</p>"),
      ];
      unset($form['actions']);
    }
    $push_worker_js = (new PushWorkerJs())->parse();
    if (!empty($push_worker_js)) {
      $visible_params = [
        'defaultTitle'     => [
          'label'       => $this->t('Default Title'),
          'description' => $this->t('Default notification title. The default title can be changed in your Airship dashboard or overridden in node forms.'),
        ],
        'defaultIcon'      => [
          'label'       => $this->t('Default Icon'),
          'description' => $this->t('Default notification icon. The default icon can be changed in your Airship dashboard or overridden in node forms.'),
        ],
      ];
      $default_values = [];
      foreach ($visible_params as $k => $info) {
        $v = ($k == 'defaultIcon' && !empty($push_worker_js[$k])) ? '<img src="' . $push_worker_js[$k] . '" style="max-wdith: 45px" border="0">' : $push_worker_js[$k];
        $default_values[] = '<div class="default-param">
            <div class="default-param-name"><strong>' . $info['label'] . '</strong></div>
            <div class="default-param-value">' . $v . '</div>
            <div class="description">' . $info['description'] . '</div>
          </div>';
      }
      if (!empty($default_values)) {
        $form['default_value'] = [
          '#type'     => 'inline_template',
          '#template' => '<div class="default-values">' . join("\n", $default_values) . '</div>'
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $config = $this->config('urban_airship_web_push_notifications.configuration');
    $zip_file = $form_state->getValue('bundle_sdk');
    if (!empty($zip_file[0])) {
      $fid = $zip_file[0];
      $file = File::load($fid);
      $file->setPermanent();
      $file->save();
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'urban_airship_web_push_notifications', 'sdk_bundle_fid', $fid);
      $items = $this->unzip(\Drupal::service('file_system')->realpath($file->getFileUri()));
      $config
        ->set('push-worker.js', (!empty($items['push-worker.js']) ? $items['push-worker.js'] : ''))
        ->set('snippet.html', (!empty($items['snippet.html']) ? $items['snippet.html'] : ''))
        ->set('secure-bridge.html', (!empty($items['secure-bridge.html']) ? $items['secure-bridge.html'] : ''))
        ->save();
      \Drupal::keyValue('urban_airship_web_push_notifications')->set('sdk_bundle_fid', $fid);
    }
    else {
      $fid = \Drupal::keyValue('urban_airship_web_push_notifications')->get('sdk_bundle_fid');
      $file = File::load($fid);
      $file->delete();
      $config
        ->set('push-worker.js', '')
        ->set('snippet.html', '')
        ->set('secure-bridge.html', '')
        ->save();
      \Drupal::keyValue('urban_airship_web_push_notifications')->delete('sdk_bundle_fid');
    }
    // Invalidate cache for the push-worker.js callback URL.
    Cache::invalidateTags(['urban_airship_web_push_notifications_assets']);
    parent::submitForm($form, $form_state);
  }

  /**
   * Getting contents of the file without unzipping archive file.
   */
  protected function unzip($path) {
    $items = [];
    $zip = new \ZipArchive;
    if ($zip->open($path) === TRUE) {
      for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $handle = fopen('zip://' . $path . '#' . $filename, 'r');
        $contents = '';
        while (!feof($handle)) {
          $contents .= fread($handle, 8192);
        }
        fclose($handle);
        $items[$filename] = $contents;
      }
      $zip->close();
    }
    return $items;
  }

}
