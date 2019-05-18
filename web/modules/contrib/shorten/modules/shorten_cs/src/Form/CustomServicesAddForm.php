<?php

namespace Drupal\shorten_cs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Settings form.
 */
class CustomServicesAddForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_cs_add_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('shorten.settings');

    $table = $this->shorten_cs_services_table();
    if(!empty($table)) {
      $form['custom_services'] = [
        '#markup' => $table,
      ];
    }

    $form['#attached']['library'][] = 'shorten_cs/shorten_cs';

    if (!isset($form) || !is_array($form)) {
      $form = array();
    }
    $form['#attributes'] = array('class' => 'shorten-cs-apply-js');
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The name of the service'),
      '#required' => TRUE,
    );
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('API endpoint URL'),
      '#description' => t('The URL of the API endpoint, with parameters, such that the long URL can be appended to the end.') . ' ' .
        t('For example, the endpoint for TinyURL is') . ' <code>http://tinyurl.com/api-create.php?url=</code>. ' .
        t('Appending a long URL to the endpoint and then visiting that address will return data about the shortened URL.'),
      '#required' => TRUE,
    );
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Response type'),
      '#description' => t('The type of response the API endpoint returns.'),
      '#required' => TRUE,
      '#default_value' => 'text',
      '#options' => array(
        'text' => t('Text'),
        'xml' => 'XML',
        'json' => 'JSON',
      ),
    );
    $form['tag'] = array(
      '#type' => 'textfield',
      '#title' => t('XML tag or JSON key'),
      '#description' => t('The XML tag or JSON key that identifies the full short URL in the API response.') . ' ' .
        t('Only required for XML and JSON response types.') . '<br> ' .
        t('For multidimensional JSON responses, a path can be specified using '
          . 'dot notation in order to specify the element in containing the '
          . 'short url. For example, the path \'data.url\' would point to the '
          . 'url value in the following JSON response: <br>'
          . '{"data":{"url":"http://ex.am/ple"}}<br>'
          . 'If a JSON element name itself contains a dot character, it can be '
          . 'wrapped in double quotes.')
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $record = array();
    foreach (array('name', 'url', 'type', 'tag') as $key) {
      $record[$key] = $values[$key];
    }
    \Drupal::database()->insert('shorten_cs')->fields($record)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $v = $form_state->getValues();
    if (($v['type'] == 'xml' || $v['type'] == 'json') && empty($v['tag'])) {
      $form_state->setErrorByName('type', t('An XML tag or JSON key is required for services with a response type of XML or JSON.'));
    }
    $exists = db_query("SELECT COUNT(sid) FROM {shorten_cs} WHERE name = :name", array(':name' => $v['name']))->fetchField();
    if ($exists > 0) {
      $form_state->setErrorByName('name', t('A service with that name already exists.'));
    }
    else {
      $all_services = \Drupal::moduleHandler()->invokeAll('shorten_service');
      $all_services['none'] = t('None');
      foreach ($all_services as $key => $value) {
        if ($key == $v['name']) {
          $form_state->setErrorByName('name', t('A service with that name already exists.'));
          break;
        }
      }
    }
  }

  /**
   * Displays the table of existing custom services.
   */
  function shorten_cs_services_table() {
    $header = array(t('Name'), t('URL'), t('Type'), t('XML/JSON tag'), t('Actions'));
    $rows = array();
    $result = db_query("SELECT * FROM {shorten_cs} ORDER BY name ASC")->fetchAll();
    foreach ($result as $service) {
      $service = (array) $service;
      $service = array(
        'sid' => $service['sid'],
        'name' => \Drupal\Component\Utility\Html::escape($service['name']),
        'url' => \Drupal\Component\Utility\Html::escape($service['url']),
        'type' => $service['type'],
        'tag' => \Drupal\Component\Utility\Html::escape($service['tag']),
      );

      $options = ['absolute' => TRUE,];
      $actions = [
        '#markup' => \Drupal\Core\Link::createFromRoute('edit', 'shorten_cs.edit_form', ['service' => $service['sid']], $options)->toString() . ' ' . \Drupal\Core\Link::createFromRoute('delete', 'shorten_cs.delete_form', ['service' => $service['sid']], $options)->toString(),
      ];
      $service['actions'] = \Drupal::service('renderer')->render($actions);

      unset($service['sid']);
      $rows[] = $service;
    }
    if (!empty($rows)) {
      $table = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => array(
          'id' => 'shorten_custom_services',
        ),
      );
      return drupal_render($table);

    }
    return '';
  }
}
