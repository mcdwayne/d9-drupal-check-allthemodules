<?php

namespace Drupal\layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;

class HeroImageLayout extends DefaultConfigLayout {

  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['height'] = 500;
    $config['unit'] = 'px';
    $config['style'] = '';
    $config['parallax'] = TRUE;
    return $config;
  }

  public function build(array $regions) {
    $build = parent::build($regions);
    if (!empty($this->configuration['media'])) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($this->configuration['media']);
      if ($media) {
        if ($media->bundle() == 'image') {
          $height = '500px';
          if (!empty($this->configuration['height'])) {
            $height = $this->configuration['height'] . $this->configuration['unit'];
          }
          $src = ImageStyle::load($this->configuration['style'])->buildUrl($media->field_media_image->first()->entity->getFileUri());
          $build['#hero_attributes'] = new Attribute();
          if (!empty($this->configuration['parallax'])) {
            $build['#hero_attributes']['style'] = "background-image: url('$src');
      height: $height;
      width:100%;
      background-attachment: fixed;
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;";
          }
          else {
            $build['#hero_attributes']['style'] = "background-image: url('$src');
      height: $height;
      width:100%;
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;";
          }
        }
      }
    }
    return $build;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['media'] = [
      '#title' => $this->t('Background Media'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['image', 'video'],
      ],
      '#required' => TRUE,
    ];
    if (!empty($this->configuration['media'])) {
      $form['media']['#default_value'] = \Drupal::entityTypeManager()->getStorage('media')->load($this->configuration['media']);
    }
    $form['style'] = [
      '#title' => $this->t('Image Style'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'image_style',
      '#selection_handler' => 'default',
      '#required' => TRUE,
    ];
    if (!empty($this->configuration['style'])) {
      $form['style']['#default_value'] = \Drupal::entityTypeManager()->getStorage('image_style')->load($this->configuration['style']);
    }
    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['height'],
      '#required' => TRUE,
    ];
    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Height Unit'),
      '#default_value' => $this->configuration['unit'],
      '#required' => TRUE,
      '#options' => [
        'px' => $this->t('Pixels'),
        'vh' => $this->t('Vertical Height'),
      ],
    ];
    $form['parallax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Parallax Effect'),
      '#default_value' => $this->configuration['parallax'],
    ];
    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if (!is_numeric($form_state->getValue('height'))) {
      $form_state->setError($form['height'], $this->t('The height must be numeric'));
    }
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['media'] = $form_state->getValue('media');
    $this->configuration['style'] = $form_state->getValue('style');
    $this->configuration['height'] = $form_state->getValue('height');
    $this->configuration['unit'] = $form_state->getValue('unit');
    $this->configuration['parallax'] = $form_state->getValue('parallax');
  }

}
