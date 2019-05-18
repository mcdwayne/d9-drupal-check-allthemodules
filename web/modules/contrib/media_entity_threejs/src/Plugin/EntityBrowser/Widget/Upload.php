<?php

namespace Drupal\media_entity_threejs\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\Upload as FileUpload;
use Drupal\media_entity\MediaInterface;
use Drupal\Core\Url;

/**
 * Uses upload to create media entity threejs
 *
 * @EntityBrowserWidget(
 *   id = "media_entity_threejs_upload",
 *   label = @Translation("Upload threejs files"),
 *   description = @Translation("Upload widget that creates media entity threejs.")
 * )
 */
class Upload extends FileUpload {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'extensions' => 'mp3 wav ogg',
      'media bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    if (!$this->configuration['media bundle'] || !($bundle = $this->entityTypeManager->getStorage('media_bundle')->load($this->configuration['media bundle']))) {
      return ['#markup' => $this->t('The media bundle is not configured correctly.')];
    }

    if ($bundle->getType()->getPluginId() != 'threejs') {
      return ['#markup' => $this->t('The configured bundle is not using threejs plugin.')];
    }

    $form = parent::getForm($original_form, $form_state, $aditional_widget_parameters);
    $form['upload']['#upload_validators']['file_validate_extensions'] = [$this->configuration['extensions']];

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
      ->load($this->configuration['media bundle']);

    $threejss = [];
    foreach ($files as $file) {
      /** @var \Drupal\media_entity\MediaInterface $threejs */
      $threejs = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        $bundle->getTypeConfiguration()['source_field'] => $file,
      ]);

      $threejss[] = $threejs;
    }

    return $threejss;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      $threejss = $this->prepareEntities($form, $form_state);
      array_walk($threejss, function (MediaInterface $media) {
        $media->save();
      });

      $this->selectEntities($threejss, $form_state);
      $this->clearFormValues($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundle_options = [];
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed extensions'),
      '#default_value' => $this->configuration['extensions'],
      '#required' => TRUE,
    ];

    $bundles = $this->entityTypeManager
      ->getStorage('media_bundle')
      ->loadByProperties(['type' => 'threejs']);

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    foreach ($bundles as $bundle) {
      $bundle_options[$bundle->id()] = $bundle->label();
    }

    if (empty($bundle_options)) {
      $url = Url::fromRoute('media.bundle_add')->toString();
      $form['media bundle'] = [
        '#markup' => $this->t("You don't have media bundle of the ThreeJS type. You should <a href='!link'>create one</a>", ['!link' => $url]),
      ];
    }
    else {
      $form['media bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Media bundle'),
        '#default_value' => $this->configuration['media bundle'],
        '#options' => $bundle_options,
      ];
    }

    return $form;
  }

}
