<?php

namespace Drupal\bigvideo\Form;

use Drupal\bigvideo\Entity\BigvideoSourceInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BigvideoSourceForm.
 *
 * @package Drupal\bigvideo\Form
 */
class BigvideoSourceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\bigvideo\Entity\BigvideoSource $bigvideo_source */
    $bigvideo_source = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bigvideo_source->label(),
      '#description' => $this->t("Label for the BigVideo Source."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bigvideo_source->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bigvideo\Entity\BigvideoSource::load',
      ],
      '#disabled' => !$bigvideo_source->isNew(),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        BigvideoSourceInterface::TYPE_FILE => $this->t('Files'),
        BigvideoSourceInterface::TYPE_LINK => $this->t('Links'),
      ],
      '#default_value' => $bigvideo_source->getType(),
      '#required' => TRUE,
    ];

    $form['files'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Files'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => BigvideoSourceInterface::TYPE_FILE],
        ],
      ],
    ];
    $form['files']['mp4_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('MP4'),
      '#upload_location' => 'public://bigvideo/',
      '#upload_validators' => [
        'file_validate_extensions' => ['mp4'],
      ],
    ];
    $form['files']['webm_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('WebM'),
      '#upload_location' => 'public://bigvideo/',
      '#upload_validators' => [
        'file_validate_extensions' => ['webm'],
      ],
    ];

    if ($bigvideo_source->getType() == BigvideoSourceInterface::TYPE_FILE) {
      $mp4_fid = $bigvideo_source->getMp4();
      $form['files']['mp4_file']['#default_value'] = $mp4_fid ? [$mp4_fid] : NULL;

      $webm_fid = $bigvideo_source->getWebM();
      $form['files']['webm_file']['#default_value'] = $webm_fid ? [$webm_fid] : NULL;
    }

    $form['links'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Links'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => BigvideoSourceInterface::TYPE_LINK],
        ],
      ],
    ];
    $form['links']['mp4_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MP4'),
    ];
    $form['links']['webm_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebM'),
    ];

    if ($bigvideo_source->getType() == BigvideoSourceInterface::TYPE_LINK) {
      $form['links']['mp4_link']['#default_value'] = $bigvideo_source->getMp4();
      $form['links']['webm_link']['#default_value'] = $bigvideo_source->getWebM();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\bigvideo\Entity\BigvideoSource $bigvideo_source */
    $bigvideo_source = $this->entity;

    switch ($bigvideo_source->getType()) {
      case BigvideoSourceInterface::TYPE_LINK:
        $bigvideo_source->setMp4($form_state->getValue('mp4_link'));
        $bigvideo_source->setWebM($form_state->getValue('webm_link'));
        break;

      case BigvideoSourceInterface::TYPE_FILE:
        if ($mp4_file = $form_state->getValue('mp4_file')) {
          $bigvideo_source->setMp4(reset($mp4_file));
        }

        if ($webm_file = $form_state->getValue('webm_file')) {
          $bigvideo_source->setWebM(reset($webm_file));
        }
        break;
    }

    $status = $bigvideo_source->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label BigVideo Source.', [
          '%label' => $bigvideo_source->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label BigVideo Source.', [
          '%label' => $bigvideo_source->label(),
        ]));
    }
    $form_state->setRedirectUrl($bigvideo_source->toUrl('collection'));
  }

}
