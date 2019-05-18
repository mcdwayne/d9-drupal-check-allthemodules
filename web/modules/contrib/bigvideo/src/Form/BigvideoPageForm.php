<?php

namespace Drupal\bigvideo\Form;

use Drupal\bigvideo\Entity\BigvideoSource;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class BigvideoPageForm.
 *
 * @package Drupal\bigvideo\Form
 */
class BigvideoPageForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\bigvideo\Entity\BigvideoPageInterface $bigvideo_page */
    $bigvideo_page = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bigvideo_page->label(),
      '#description' => $this->t("Label for the BigVideo Page."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bigvideo_page->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bigvideo\Entity\BigvideoPage::load',
      ],
      '#disabled' => !$bigvideo_page->isNew(),
    ];

    $sources = BigvideoSource::loadMultiple();
    $options = [];

    /** @var \Drupal\bigvideo\Entity\BigvideoSourceInterface $source */
    foreach ($sources as $source) {
      $options[$source->id()] = $source->label();
    }
    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Source'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $bigvideo_page->getSource(),
    ];
    if (!$options) {
      $form['source']['#suffix'] = Link::fromTextAndUrl($this->t("You need to create source first"), Url::fromRoute("entity.bigvideo_source.collection"))->toString();
    }

    $description_params = array(
      '%blog' => 'blog',
      '%blog-wildcard' => 'blog/*',
      '%front' => '<front>',
    );
    $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", $description_params);
    $form['path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#description' => $description,
      '#required' => TRUE,
      '#default_value' => $bigvideo_page->getPath(),
    ];

    $form['selector'] = array(
      '#type' => 'textfield',
      '#title' => t('Selector'),
      '#description' => t('BigVideo will be applied to this selector instead of "body".'),
      '#attributes' => [
        'placeholder' => 'body',
      ],
      '#default_value' => $bigvideo_page->getSelector(),
    );

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $bigvideo_page->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bigvideo_page = $this->entity;

    $status = $bigvideo_page->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label BigVideo Page.', [
          '%label' => $bigvideo_page->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label BigVideo Page.', [
          '%label' => $bigvideo_page->label(),
        ]));
    }
    $form_state->setRedirectUrl($bigvideo_page->toUrl('collection'));
  }

}
