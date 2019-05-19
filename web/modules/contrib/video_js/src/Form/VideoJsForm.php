<?php

namespace Drupal\video_js\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\video_js\Entity\VideoJsInterface;


class VideoJsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['link']['#states'] = [
      'visible' => [
        ':input[name="type"]' => ['value' => VideoJsInterface::TYPE_LINK],

      ],
    ];
    $form['file']['#states'] = [
      'visible' => [
        ':input[name="type"]' => ['value' => VideoJsInterface::TYPE_FILE],

      ],
    ];

    $form['file']['widget'][0]['#upload_location'] = 'public://video_js/';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $video_js = $this->entity;

    $status = $video_js->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VideoJs Source.', [
          '%label' => $video_js->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VideoJs Source.', [
          '%label' => $video_js->label(),
        ]));
    }

    drupal_set_message(t('The source has been saved.'));
    $form_state->setRedirect('video_js.list');
  }
}
