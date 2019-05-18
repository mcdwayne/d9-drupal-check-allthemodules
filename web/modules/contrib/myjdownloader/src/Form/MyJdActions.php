<?php

namespace Drupal\myjdownloader\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\myjdownloader\MyJDAPI;

/**
 * Actions via API.
 */
class MyJdActions extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myjd_actions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions']['start'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start'),
      '#submit' => ['::submitStart'],
    ];

    $form['actions']['stop'] = [
      '#type' => 'submit',
      '#value' => $this->t('Stop'),
      '#submit' => ['::submitStop'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Start Downloads.
   */
  public function submitStart(array &$form, FormStateInterface $form_state) {

    $mydjapi = new MyJDAPI();
    $res = $mydjapi->callAction('/toolbar/startDownloads');
    $res = Json::decode($res)['data'];
    $this->showActionMessage("Stopp", $res);

  }

  /**
   * Stop Downloads.
   */
  public function submitStop(array &$form, FormStateInterface $form_state) {

    $mydjapi = new MyJDAPI();
    $res = $mydjapi->callAction('/toolbar/stopDownloads');
    $res = Json::decode($res)['data'];
    $this->showActionMessage("Stop", $res);

  }

  /**
   * Stop Downloads.
   */
  public function showActionMessage($action, $res) {

    if ($res) {
      $this->messenger()->addMessage("Success : $action");
    }
    else {
      $this->messenger()->addMessage("Fail : $action", 'error');
    };
  }

}
