<?php

namespace Drupal\cision_notify_pull\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class CisionPullTestForm.
 *
 * @package Drupal\cision_notify_pull\Form
 */
class CisionPullTestForm extends ConfigFormBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'CisionPullTestForm.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cision_pull_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint url'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => 'http://' . $_SERVER['HTTP_HOST'] . '/cision-notified-pull',
    ];
    $form['xml_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Xml code'),
    ];
    $form['xml_code.pushrelease.ping'] = [
      '#type' => 'inline_template',
      '#template' => '<label>{{ label }}</label>{{ code }} ',
      '#context' => [
        'label' => $this->t('Sample Xml pushrelease.ping'),
        'code' => '<methodCall> <methodName>pushrelease.ping</methodName> <params> <param> <value>http://publish.ne.cision.com/v2.0/Release/GetDetail/EF242EB4C5C99992</value> </param> </params> </methodCall>',
      ],
    ];
    $form['xml_code.pushreleasedeleted.ping'] = [
      '#type' => 'inline_template',
      '#template' => '<label>{{ label }}</label>{{ code }} ',
      '#context' => [
        'label' => $this->t('Sample Xml pushreleasedeleted.ping'),
        'code' => '<methodCall>
        <methodName>pushreleasedeleted.ping</methodName>
        <params>
        <param>
        <value>EF242EB4C5C99992</value>
        </param>
        <itemid>2604454</itemid>
        <deletedurl>http://publish.ne.cision.com/Release/GetDetail/EF242EB4C5C99992</deletedurl>
        </params>
        </methodCall>',
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $post_data = $form_state->getValue('xml_code');
    $options = [
      'body' => $post_data,
      'timeout' => 25,
      'headers' => ['Content-Type' => 'text/xml'],
    ];
    $endpoint = $form_state->getValue('endpoint');
    $this->httpClient->request('POST', $endpoint, $options);
  }

}
