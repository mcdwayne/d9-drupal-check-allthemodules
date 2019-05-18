<?php

namespace Drupal\drd\Plugin\Update\Build;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Plugin\Update\Base as UpdateBase;
use Drupal\drd\Update\PluginBuildInterface;
use Drupal\drd\Update\PluginStorageInterface;
use GuzzleHttp\Client;

/**
 * Abstract DRD Update plugin to implement general build functionality.
 */
abstract class Base extends UpdateBase implements PluginBuildInterface {

  protected $changed = FALSE;

  /**
   * Determine if plugin includes a patching component itself.
   *
   * @return bool
   *   TRUE if plugin handles patching automatically.
   */
  protected function implicitPatching() {
    return FALSE;
  }

  /**
   * Convert patch list from configuration into editable string.
   *
   * @return string
   *   Properly formatted string for editing.
   */
  private function editablePatches() {
    $items = [];
    foreach ($this->configuration['patches'] as $patch) {
      $items[] = implode('|', $patch);
    }
    return implode(PHP_EOL, $items);
  }

  /**
   * Converted edited patch string into structured array for configuration.
   *
   * @param string $value
   *   The edited patch configuration.
   *
   * @return array
   *   The config array containing patching information.
   */
  private function unpackPatches($value) {
    $patches = [];
    foreach (explode(PHP_EOL, $value) as $item) {
      $parts = explode('|', trim($item));
      if (count($parts) > 1) {
        $patches[] = [
          'path' => $parts[0],
          'patch' => $parts[1],
        ];
      }
    }
    return $patches;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'patches' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['patches'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Patches'),
      '#default_value' => $this->editablePatches(),
      '#description' => $this->t('One patch per line in the format <em>path|patchfile</em> where path is relative to the Drupal root and patchfile is a URL.'),
      '#access' => !$this->implicitPatching(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['patches'] = $this->unpackPatches($this->getFormValue($form_state, 'patches'));
  }

  /**
   * {@inheritdoc}
   */
  final public function hasChanged() {
    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  final public function patch(PluginStorageInterface $storage) {
    if (!$this->hasChanged() || $this->implicitPatching()) {
      return $this;
    }
    foreach ($this->configuration['patches'] as $item) {
      $path = $storage->getWorkingDirectory() . DIRECTORY_SEPARATOR . $item['path'];
      if (!file_exists($path)) {
        throw new \Exception('Can not patch ' . $path . ', directory doesn\'t exist.');
      }

      $options = [
        'sink' => \Drupal::service('file_system')->tempnam('temporary://', 'patch'),
      ];
      $client = new Client(['base_uri' => $item['patch']]);
      $response = $client->request('get', NULL, $options);
      if ($response->getStatusCode() != 200) {
        throw new \Exception('Can\'t download patch ' . $item['patch']);
      }

      if ($this->shell($storage, 'patch -p1 <' . $options['sink'], $path)) {
        throw new \Exception('Patch ' . $item['patch'] . ' failed.');
      }
    }
    return $this;
  }

}
