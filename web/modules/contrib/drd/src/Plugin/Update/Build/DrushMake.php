<?php

namespace Drupal\drd\Plugin\Update\Build;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Update\PluginStorageInterface;

/**
 * Provides a Drush Make based update build plugin.
 *
 * @Update(
 *  id = "drushmake",
 *  admin_label = @Translation("Drush Make"),
 * )
 */
class DrushMake extends Base {

  /**
   * {@inheritdoc}
   */
  protected function implicitPatching() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'makefile' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['makefile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Make file'),
      '#default_value' => $this->configuration['makefile'],
      '#description' => $this->t('Provide path and file name of the make file relative to the project root, e.g. "develop/project.make".'),
      '#states' => [
        'required' => $this->condition,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['makefile'] = $this->getFormValue($form_state, 'makefile');
  }

  /**
   * {@inheritdoc}
   */
  public function build(PluginStorageInterface $storage, array $releases) {
    $makeFile = $storage->getWorkingDirectory() . DIRECTORY_SEPARATOR . $this->configuration['makefile'];
    $hash = @hash_file('md5', $makeFile);

    $make = file_get_contents($makeFile);

    foreach ($releases as $release) {
      $name = $release->getMajor()->getProject()->getName();
      $pattern = '/projects\[' . $name . '\]\[version\].*/i';
      $replacement = 'projects[' . $name . '][version] = ' . $release->getReleaseVersion();
      $make = preg_replace($pattern, $replacement, $make);
    }

    file_put_contents($makeFile, $make);

    if ($this->shell($storage, 'drush make --force-complete ' . $makeFile . ' .', $storage->getDrupalDirectory())) {
      throw new \Exception('Drush make failed.');
    }
    else {
      $this->changed = ($hash != @hash_file('md5', $makeFile));
    }

    return $this;
  }

}
