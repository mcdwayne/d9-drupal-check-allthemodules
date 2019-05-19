<?php

namespace Drupal\whatsnew_dashboard\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\whatsnew_dashboard\Entity\Site;
use Drupal\Component\Utility\UrlHelper;

/**
 * Builds the form to add/edit a Site.
 */
class SiteForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $site = $this->entity;

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#maxlength' => 255,
      '#default_value' => $site->id(),
      '#disabled' => !$site->isNew(),
      '#description' => $this->t("Unique site ID."),
      '#machine_name' => [
        'exists' => 'Drupal\whatsnew_dashboard\Entity\Site::load',
      ],
      '#required' => TRUE,
    ];

    $form['site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#default_value' => $site->getSiteUrl(),
      '#description' => $this->t("Site URL in the following format: http://www.example.com"),
      '#required' => TRUE,
    ];

    $form['site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#maxlength' => 255,
      '#default_value' => $site->getSiteKey(),
      '#description' => $this->t("Site access key."),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $site = $this->entity;
    $status = $site->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label site.', [
        '%label' => $site->getSiteUrl(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label site was not saved.', [
        '%label' => $site->getSiteUrl(),
      ]));
    }

    $form_state->setRedirect('entity.site.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();

    // Check the URL is in the valid format.
    if (!UrlHelper::isValid($values['site_url'], TRUE)) {
      $form_state->setErrorByName('site_url', t('Not a valid URL'));
    }

    // Check it can fetch a report from the site.
    $site = new Site([
      'site_url' => $values['site_url'],
      'site_key' => $values['site_key'],
    ], 'site');

    if (!$site->fetchReport()) {
      $form_state->setErrorByName('site_url', t('Site URL or Key is invalid'));
      $form_state->setErrorByName('site_key', t('Site URL or Key is invalid'));
    }

  }

}
