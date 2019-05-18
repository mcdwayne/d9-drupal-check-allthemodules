<?php

namespace Drupal\custom_header_image\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Class HeaderImageForm.
 */
class HeaderImageForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\custom_header_image\Entity\HeaderImage $header_image */
    $header_image = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $header_image->label(),
      '#description' => $this->t("Label for the Header image."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $header_image->id(),
      '#machine_name' => [
        'exists' => '\Drupal\custom_header_image\Entity\HeaderImage::load',
      ],
      '#disabled' => !$header_image->isNew(),
      '#required' => TRUE,
    ];

    $form['header_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Header Image'),
      '#default_value' => $header_image->hasHeaderImage() ? ['fid' => $header_image->get('header_image')] : [],
      '#upload_location' => 'public://header-images',
      '#required' => TRUE,
    ];
    $options = [];
    foreach (ImageStyle::loadMultiple() as $image_style) {
      $options[$image_style->id()] = $image_style->label();
    }
    $form['image_styles'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style(s)'),
      '#default_value' => $header_image->hasImageStyle() ? $header_image->getImageStyleIds() : '',
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#options' => $options,
    ];
    $form['sizes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Responsive sizes'),
      '#default_value' => $header_image->hasSizes() ? implode(PHP_EOL, $header_image->getSizes()) : '',
      '#description' => $this->t('A list of breakpoints at which the browser should choose a new source image from the srcset.'),
    ];
    $form['alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alt Text'),
      '#maxlength' => 255,
      '#default_value' => $header_image->getAltText(),
      '#description' => $this->t("Alt text for the Header image."),
    ];
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#default_value' => $header_image->getPathsString(),
      '#description' => $this->t("Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
      '#required' => TRUE,
    ];

    /* You will need additional form elements for your custom properties. */
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('header_image')) {
      $form_state->setValue('header_image', $form_state->getValue('header_image')[0]);
    }
    $form_state->setValue('image_styles', array_values($form_state->getValue('image_styles')));
    $sizes = explode("\n", $form_state->getValue('sizes'));
    foreach ($sizes as $key => $size) {
      $sizes[$key] = trim($size);
    }
    $form_state->setValue('sizes', $sizes);
    $form_state->setValue('paths', explode("\n", $form_state->getValue('paths')));
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\custom_header_image\Entity\HeaderImageInterface $header_image */
    $header_image = $this->entity;
//    drupal_set_message(t('@foo', ['@foo' => print_r($header_image->toArray(), TRUE)]));
    $status = $header_image->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Header image.', [
          '%label' => $header_image->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Header image.', [
          '%label' => $header_image->label(),
        ]));
    }
    $form_state->setRedirectUrl($header_image->toUrl('collection'));
  }

}
