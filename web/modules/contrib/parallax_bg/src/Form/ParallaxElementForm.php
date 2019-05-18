<?php

namespace Drupal\parallax_bg\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ParallaxElementForm.
 *
 * @package Drupal\parallax_bg\Form
 */
class ParallaxElementForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\parallax_bg\Entity\ParallaxElementInterface $parallax_element */
    $parallax_element = $this->entity;

    $form['selector'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Valid jQuery selector'),
      '#maxlength'     => 255,
      '#default_value' => $parallax_element->getSelector(),
      '#required'      => TRUE,
    ];

    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $parallax_element->id(),
      '#machine_name'  => [
        'exists' => '\Drupal\parallax_bg\Entity\ParallaxElement::load',
      ],
      '#disabled'      => !$parallax_element->isNew(),
    ];

    $position = $parallax_element->getPosition();

    $form['position'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Position'),
      '#default_value' => isset($position) ? $position : '50%',
      '#options'       => [
        '0'    => $this->t('Left'),
        '50%'  => $this->t('Center'),
        '100%' => $this->t('Right'),
      ],
    ];

    $speed = $parallax_element->getSpeed();

    $form['speed'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Relative speed'),
      '#default_value' => isset($speed) ? $speed : '0.1',
      '#options'       => [
        '0'    => 0,
        '0.1'  => 0.1,
        '0.2'  => 0.2,
        '0.3'  => 0.3,
        '0.4'  => 0.4,
        '0.5'  => 0.5,
        '0.6'  => 0.6,
        '0.7'  => 0.7,
        '0.8'  => 0.8,
        '0.9'  => 0.9,
        '1'    => 1,
        '1.25' => 1.25,
        '1.5'  => 1.5,
        '1.75' => 1.75,
        '2'    => 2,
        '2.5'  => 2.5,
        '3'    => 3,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\parallax_bg\Entity\ParallaxElementInterface $parallax_element */
    $parallax_element = $this->entity;
    $status = $parallax_element->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Parallax element.', [
          '%label' => $parallax_element->getSelector(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Parallax element.', [
          '%label' => $parallax_element->getSelector(),
        ]));
    }

    $form_state->setRedirectUrl($parallax_element->toUrl('collection'));
  }

}
