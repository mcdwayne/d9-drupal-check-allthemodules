<?php

namespace Drupal\shorten_cs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Settings form.
 */
class CustomServicesDeleteForm extends ConfirmFormBase {

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_cs_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the custom service %id?', array('%id' => $this->id));
  }

  /**
   * {@inheritdoc}
   */
    public function getCancelUrl() {
      return new Url('shorten_cs.theme_shorten_cs_admin');
  }

  /**
   * {@inheritdoc}
   */
    public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shorten.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, $service = NULL) {


    $item = db_select('shorten_cs', 's')
      ->fields('s')
      ->condition('sid', intval($service))
      ->execute()
      ->fetchAssoc();

    $this->id = $item['name'];

    $form['service'] = array(
      '#type' => 'value',
      '#value' => $item['name'],
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $service = $form_state->getValues()['service'];
    $config_factory = \Drupal::configFactory();

    if ($service == \Drupal::config('shorten.settings')->get('shorten_service') ) {
      if (\Drupal::config('shorten.settings')->get('shorten_service_backup') ) {
        $config_factory->getEditable('shorten.settings')->set('shorten_service', 'TinyURL')->save();
      }
      else {
        $config_factory->getEditable('shorten.settings')->set('shorten_service', 'is.gd')->save();
      }
      drupal_set_message(t('The default URL shortening service was deleted, so it has been reset to @service.', array('@service' => \Drupal::config('shorten.settings')->get('shorten_service') )));
    }

    if ($service == \Drupal::config('shorten.settings')->get('shorten_service_backup') ) {
        if (\Drupal::config('shorten.settings')->get('shorten_service') ) {
          $config_factory->getEditable('shorten.settings')->set('shorten_service_backup', 'is.gd')->save();
        }
        else {
          $config_factory->getEditable('shorten.settings')->set('shorten_service_backup', 'TinyURL')->save();
        }
        drupal_set_message(t('The backup URL shortening service was deleted, so it has been reset to @service.', array('@service' => \Drupal::config('shorten.settings')->get('shorten_service_backup') )));
      }

    db_delete('shorten_cs')
      ->condition('name', $service)
      ->execute();
    drupal_set_message(t('The service %service has been deleted.', array('%service' => $service)));
    $form_state->setRedirect('shorten_cs.theme_shorten_cs_admin');
    return;
  }

}
