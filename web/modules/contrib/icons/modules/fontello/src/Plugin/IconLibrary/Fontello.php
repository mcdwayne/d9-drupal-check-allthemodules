<?php

namespace Drupal\icons_fontello\Plugin\IconLibrary;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\icons\IconLibraryPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

/**
 * Defines a icon library plugin to integrate Fontello with icons module.
 *
 * @IconLibrary(
 *   id = "fontello",
 *   label = @Translation("Fontello"),
 *   description = @Translation("Integration with Fontello for the icons module."),
 * )
 */
class Fontello extends IconLibraryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(array &$element, ConfigEntityInterface $entity, $name) {
    $prefix = $this->configuration['prefix'];
    $element['#attributes']['class'][] = $prefix . $name;
    $element['#attached']['library'] = 'icons_fontello/' . $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function iconLibraryForm(array $form, FormStateInterface $form_state) {
    return [
      'library_path' => [
        '#title' => $this->t('Library Path'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['library_path'],
        '#description' => $this->t("Library path for fontello."),
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function iconLibraryValidate(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('library_path');
    $path = $this->getLibraryFileBasePath($path);

    // Validate the folder.
    if (!$this->validateLibraryPath($path)) {
      $form_state->setErrorByName('library_path', $this->t('Given library path does not exist'));
    }

    // Validate the config.json.
    if (!$this->validateLibraryConfigJson($path)) {
      $form_state->setErrorByName('library_path', $this->t('Given library path does not valid config.json'));
    }

    // Validate the css file.
    if (!$this->validateLibraryFontelloCss($path)) {
      $form_state->setErrorByName('library_path', $this->t('Given library path does not contain valid style.css'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function iconLibrarySubmit(array &$form, FormStateInterface $form_state) {
    $this->configuration['library_path'] = $form_state->getValue('library_path');
    $this->processConfigJson();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'library_path' => '',
      'name' => 'fontello',
      'prefix' => 'icon-',
      'suffix' => FALSE,
      'icons' => [],
    ];
  }

  /**
   * Validate the existence of the file folder based on the given library path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return bool
   *   Indicating if the path validates.
   */
  public function validateLibraryPath($library_path) {
    $path = \Drupal::service('file_system')->realpath($library_path);
    if ($path) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate the existence of the config.json file in the given library path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return bool
   *   Indicating if the path validates.
   */
  public function validateLibraryConfigJson($library_path) {
    $json_uri = $library_path . '/config.json';
    $path = \Drupal::service('file_system')->realpath($json_uri);

    if ($path) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate the existence of the fontello css file in the given library path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return bool
   *   Indicating if the path validates.
   */
  public function validateLibraryFontelloCss($library_path) {
    $style_uri = $library_path . '/css/fontello.css';
    $path = \Drupal::service('file_system')->realpath($style_uri);

    if ($path) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Process information from config.json into the configuration settings.
   */
  public function processConfigJson() {
    $library_path = $this->getLibraryPath();
    $json_uri = $this->getLibraryFileBasePath($library_path) . '/config.json';
    $path = \Drupal::service('file_system')->realpath($json_uri);

    $name = 'fontello';
    $icons = [];
    $prefix = 'icon-';
    $suffix = FALSE;

    if ($path) {
      $json_string = file_get_contents($path);
      $config = Json::decode($json_string);

      foreach ($config['glyphs'] as $icon) {
        $icon_name = $icon['css'];
        $icon_source = $icon['src'];
        $icons[$icon_name] = [
          'name' => $icon_name,
          'src' => $icon_source,
        ];
      }

      if (!empty($config['name'])) {
        $name = $config['name'];
      }
      $prefix = $config['css_prefix_text'];
      $suffix = $config['css_use_suffix'];
    }

    $this->configuration['name'] = $name;
    $this->configuration['prefix'] = $prefix;
    $this->configuration['suffix'] = $suffix;
    $this->configuration['icons'] = $icons;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons() {
    $icons = array_keys($this->configuration['icons']);
    return array_combine($icons, $icons);
  }

  /**
   * Get library path from the configuration.
   *
   * @return string
   *   Library path.
   */
  public function getLibraryPath() {
    $library_path = $this->configuration['library_path'];
    return $library_path;
  }

  /**
   * Get file base path for given path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return string
   *   Library file path.
   */
  public function getLibraryFileBasePath($library_path) {
    $library_path = rtrim($library_path, '/');
    $library_path = ltrim($library_path, '/');
    return $library_path;
  }

  /**
   * Get library base path for given path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return string
   *   Library base path.
   */
  public function getLibraryBasePath($library_path) {
    $library_path = rtrim($library_path, '/');
    $library_path = ltrim($library_path, '/');
    $library_path = '/' . $library_path;
    return $library_path;
  }

}
