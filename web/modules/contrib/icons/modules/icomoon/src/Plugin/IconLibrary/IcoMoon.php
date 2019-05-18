<?php

namespace Drupal\icons_icomoon\Plugin\IconLibrary;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\icons\IconLibraryPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

/**
 * Defines a icon library plugin to integrate Icomoon with icons module.
 *
 * @IconLibrary(
 *   id = "icomoon",
 *   label = @Translation("Icomoon"),
 *   description = @Translation("Integration with Icomoon for the icons module."),
 * )
 */
class IcoMoon extends IconLibraryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(array &$element, ConfigEntityInterface $entity, $name) {
    $prefix = $this->configuration['prefix'];
    $element['#attributes']['class'][] = $prefix . $name;
    $element['#attached']['library'] = 'icons_icomoon/' . $entity->id();
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
        '#description' => $this->t("Library path for icomoon."),
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function iconLibraryValidate(array &$form, FormStateInterface $form_state) {
    $library_path = $form_state->getValue('library_path');
    $library_path = $this->getLibraryFileBasePath($library_path);

    // Validate the folder.
    if (!$this->validateLibraryPath($library_path)) {
      $form_state->setErrorByName('library_path', $this->t('Given library path does not exist'));
    }

    // Validate the selection.json.
    if (!$this->validateLibrarySelectionJson($library_path)) {
      $form_state->setErrorByName('library_path', $this->t('Given library path does not valid config.json'));
    }

    // Validate the css file.
    if (!$this->validateLibraryStyleCss($library_path)) {
      $form_state->setErrorByName('library_path', $this->t('Given library path does not contain valid style.css'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function iconLibrarySubmit(array &$form, FormStateInterface $form_state) {
    $this->configuration['library_path'] = $form_state->getValue('library_path');
    $this->processSelectionJson();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'library_path' => '',
      'name' => 'icomoon',
      'prefix' => 'icon-',
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
   * Validate that the selection.json file exists in the given library path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return bool
   *   Indicating if the path validates.
   */
  public function validateLibrarySelectionJson($library_path) {
    $json_uri = $library_path . '/selection.json';
    $path = \Drupal::service('file_system')->realpath($json_uri);

    if ($path) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate the existence of the style css file in the given library path.
   *
   * @param string $library_path
   *   Path to the library folder of the icomoon files.
   *
   * @return bool
   *   Indicating if the path validates.
   */
  public function validateLibraryStyleCss($library_path) {
    $style_uri = $library_path . '/style.css';
    $path = \Drupal::service('file_system')->realpath($style_uri);

    if ($path) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Process information from selection.json into the configuration settings.
   */
  public function processSelectionJson() {
    $path = $this->getLibraryPath();
    $json_uri = $this->getLibraryFileBasePath($path) . '/selection.json';
    $path = \Drupal::service('file_system')->realpath($json_uri);

    $name = 'icomoon';
    $icons = [];
    $prefix = 'icon-';

    if ($path) {
      $json_string = file_get_contents($path);
      $config = Json::decode($json_string);

      foreach ($config['icons'] as $icon) {
        $icon_name = $icon['properties']['name'];
        $icon_title = $icon['icon']['tags'][0];
        $icons[$icon_name] = $icon_title;
      }

      $name = $config['metadata']['name'];
      $prefix = $config['preferences']['fontPref']['prefix'];
    }

    $this->configuration['name'] = $name;
    $this->configuration['prefix'] = $prefix;
    $this->configuration['icons'] = $icons;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons() {
    return $this->configuration['icons'];
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
