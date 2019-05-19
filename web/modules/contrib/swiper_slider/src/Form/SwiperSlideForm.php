<?php

namespace Drupal\swiper_slider\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Swiper slide edit forms.
 *
 * @ingroup swiper_slider
 */
class SwiperSlideForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\swiper_slider\Entity\SwiperSlider */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Swiper slide.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Swiper slide.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.swiper_slider.canonical', ['swiper_slider' => $entity->id()]);
  }

}
