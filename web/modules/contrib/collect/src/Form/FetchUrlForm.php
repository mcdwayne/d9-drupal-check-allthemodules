<?php
/**
 * @file
 * Contains \Drupal\collect\Form\FetchUrlForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\FetchWebResource;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for collecting web pages.
 */
class FetchUrlForm extends FormBase {

  /**
   * The fetch web resource service.
   *
   * @var \Drupal\collect\FetchWebResource
   */
  protected $fetchWebResource;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('collect.fetch_web_resource')
    );
  }

  /**
   * Constructs an FetchUrlForm object.
   *
   * @param \Drupal\collect\FetchWebResource $fetch_web_resource
   *    The fetch web resource service.
   */
  public function __construct(FetchWebResource $fetch_web_resource) {
    $this->fetchWebResource = $fetch_web_resource;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#size' => 60,
      '#description' => $this->t('Enter a web resource\'s URL.'),
    );
    $options = [
      'text/html' => 'text/html',
      'application/json' => 'application/json',
      'custom' => $this->t('Custom'),
    ];
    $form['accept_header'] = array(
      '#type' => 'select',
      '#title' => $this->t('Accept header'),
      '#options' => $options,
      '#empty_option' => $this->t('- Any -'),
      '#default_value' => $form_state->getValue('accept_header'),
      '#description' => $this->t('You can select web resource\'s content type.'),
    );
    $form['custom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom Accept header'),
      '#size' => 60,
      '#description' => $this->t('Enter a custom request Accept header. The header format is defined in <a href="@reference" target="_blank">RFC 7231</a>.', array(
        '@reference' => 'http://tools.ietf.org/html/rfc7231#section-5.3.2',
      )),
      '#states' => array(
        'visible' => array(
          ':input[name="accept_header"]' => array('value' => 'custom'),
        ),
        'required' => array(
          ':input[name="operation"]' => array('value' => 'single'),
        ),
      ),
    );
    $form['schema_uri'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Schema URI'),
      '#default_value' => $form_state->getValue('schema_uri', FetchWebResource::SCHEMA_URI),
      '#description' => $this->t('Defining a custom Schema URI allows you to separate models.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Get page'),
      '#validate' => array('::validateUrl'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collect_fetch_url';
  }

  /**
   * Validates URL.
   */
  public function validateUrl(array $form, FormStateInterface $form_state) {
    $url = trim($form_state->getValue('url'));
    if (!empty($url)) {
      if (!UrlHelper::isValid($url, TRUE)) {
        $form_state->setErrorByName('url', t('Invalid URL %url.', array('%url' => $url)));
      }
    }
    else {
      $form_state->setErrorByName('url', t('Missing URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');
    // Get request headers.
    $accept_header = $form_state->getValue('accept_header');
    if ($accept_header == 'custom') {
      $accept_header = $form_state->getValue('custom');
    }
    // Try to fetch a web resource and save it into collect container.
    try {
      $container = $this->fetchWebResource->fetch($url, $accept_header, $form_state->getValue('schema_uri'));
      drupal_set_message($this->t('Web resource from %url has been saved. You can access it <a href="@view_url">here</a>.', array(
          '%url' => $url,
          '@view_url' => $container->url(),
        )));
      $form_state->setRedirect('entity.collect_container.collection');
    }
    catch (RequestException $re) {
      drupal_set_message($re->getMessage(), 'error');
    }
  }
}
