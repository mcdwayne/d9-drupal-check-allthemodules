<?php

/**
 * @file
 * Contains Drupal\google_adwords_path\Form\GoogleAdwordsPathConfigForm.
 */

namespace Drupal\google_adwords_path\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_adwords_path\GoogleAdwordsPathTracker;

/**
 * Class GoogleAdwordsPathConfigForm.
 *
 * @package Drupal\google_adwords_path\Form
 */
class GoogleAdwordsPathConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /**
     * @var \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig $path_config
     */
    $path_config = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $path_config->label(),
      '#description' => $this->t("Label for the Google AdWords Path Config."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $path_config->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig::load',
      ),
      '#disabled' => !$path_config->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $path_config->get('enabled'),
    );

    $form['conversion_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Conversion ID'),
      '#default_value' => $path_config->get('conversion_id'),
      '#size' => 15,
      '#maxlength' => 64,
      '#required' => TRUE,
    );

   $form['language'] = array(
       '#type' => 'textfield',
       '#title' => t('Conversion Language'),
       '#default_value' => $path_config->get('language'),
       '#size' => 15,
       '#maxlength' => 64,
     );

   $form['format'] = array(
       '#type' => 'textfield',
       '#title' => t('Conversion Format'),
       '#default_value' => $path_config->get('format'),
       '#size' => 15,
       '#maxlength' => 64,
     );

   $form['colour'] = array(
       '#type' => 'textfield',
       '#title' => t('Conversion Colour'),
       '#default_value' => $path_config->get('colour'),
       '#size' => 15,
       '#maxlength' => 64,
     );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Conversion Label'),
      '#default_value' => $path_config->get('label'),
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
    );

    $form['paths'] = array(
      '#type' => 'textarea',
      '#title' => t('Paths'),
      '#default_value' => $path_config->get('paths'),
      '#rows' => 8,
      '#cols' => 128,
      '#required' => TRUE,
      '#description' => t('A list of paths, separated by a new line, where this conversion code should be inserted.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $path_config = $this->entity;
    $status = $path_config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Google AdWords Path Config.', [
          '%label' => $path_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Google AdWords Path Config.', [
          '%label' => $path_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($path_config->urlInfo('collection'));

    /**
     * @var \Drupal\google_adwords_path\GoogleAdwordsPathTracker $pathTracker
     *  The path tracker service, which will be used to invalidate the cache
     */
    $pathTracker = \DRUPAL::service('google_adwords_path.pathtracker');
    // re-build the tree
    if ($pathTracker instanceof GoogleAdwordsPathTracker) {
      $pathTracker->buildPathTree(TRUE);
    }
    else {
      drupal_set_message(__method__.'::'.__line__.':: CACHE CLEAR FAIL');
    }

  }

}
