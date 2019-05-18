<?php

namespace Drupal\ebrsww\Plugin\EntityBrowser\Widget;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Reference a remote file using remote stream wrapper.
 *
 * @EntityBrowserWidget(
 *   id = "remote_media",
 *   label = @Translation("Remote media"),
 *   description = @Translation("Reference remote media using Remote stream wrapper.")
 * )
 */
class MediaEntityBrowser extends FileEntityBrowser {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'media_bundle' => NULL,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    if (!$this->configuration['media_bundle'] || !($bundle = $this->entityTypeManager->getStorage('media_bundle')->load($this->configuration['media_bundle']))) {
      return ['#markup' => $this->t('The media bundle is not configured correctly.')];
    }

    if ($bundle->getType()->getPluginId() != 'image') {
      return ['#markup' => $this->t('The configured bundle is not using image plugin.')];
    }

    $form = parent::getForm($original_form, $form_state, $aditional_widget_parameters);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $files = parent::prepareEntities($form, $form_state);

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $this->entityTypeManager
      ->getStorage('media_bundle')
      ->load($this->configuration['media_bundle']);

    $entities = [];
    foreach ($files as $file) {
      /** @var \Drupal\media_entity\MediaInterface $media */
      $media = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        $bundle->getTypeConfiguration()['source_field'] => $file,
      ]);
      $entities[] = $media;
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundle_options = $this->bundleOptions();

    if (empty($bundle_options)) {
      $url = Url::fromRoute('media.bundle_add')->toString();
      $form['media_bundle'] = [
        '#markup' => $this->t("You don't have media bundle of the Image type. You should <a href='!link'>create one</a>", ['!link' => $url]),
      ];
    }
    else {
      $form['media_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Media bundle'),
        '#default_value' => $this->configuration['media_bundle'],
        '#options' => $bundle_options,
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * @return string[]
   *   An array of bundle labels keyed by their id.
   */
  private function bundleOptions() {
    $bundle_options = [];
    $bundles = $this
      ->entityTypeManager
      ->getStorage('media_bundle')
      ->loadByProperties(['type' => 'image']);

    foreach ($bundles as $bundle) {
      $bundle_options[$bundle->id()] = $bundle->label();
    }

    return $bundle_options;
  }

}
