<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Model\FetchUrl.
 */

namespace Drupal\collect\Plugin\collect\Model;

use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\SpecializedDisplayModelPluginInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;

/**
 * Model plugin for captured web pages.
 *
 * @Model(
 *   id = "collect_fetch_url",
 *   label = @Translation("Collect Fetch URL"),
 *   description = @Translation("Contains the response of a HTTP request, along with request and response headers."),
 *   patterns = {
 *     "http://schema.md-systems.ch/collect/0.0.1/url"
 *   }
 * )
 */
class FetchUrl extends Json implements SpecializedDisplayModelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(CollectDataInterface $collect_data) {
    $data = $collect_data->getParsedData();
    $output = array();
    if (isset($data['charset'])) {
      $output['charset'] = array(
        '#type' => 'container',
        '#markup' => $this->t('Web resource content has been converted from @charset to UTF-8 charset.', array('@charset' => $data['charset'])),
        '#attributes' => array(
          'class' => array('messages messages--warning'),
        ),
      );
    }

    // The request headers.
    $output['request'] = array(
      '#title' => $this->t('Request'),
      '#type' => 'fieldgroup',
    );
    $output['request']['headers'] = array(
      '#type' => 'table',
      '#header' => [$this->t('Field'), $this->t('Value')],
      '#empty' => $this->t('There is no data.'),
    );

    foreach ($data['request-headers'] as $key => $value) {
      $output['request']['headers'][$key] = array(
        ['#markup' => $key],
        ['#markup' => $value[0]],
      );
    }

    // The response headers.
    $output['response'] = array(
      '#title' => $this->t('Response'),
      '#type' => 'fieldgroup',
    );
    $output['response']['headers'] = array(
      '#type' => 'table',
      '#header' => [$this->t('Field'), $this->t('Value')],
      '#empty' => $this->t('There is no data.'),
    );

    foreach ($data['response-headers'] as $key => $value) {
      $output['response']['headers'][$key] = array(
        ['#markup' => $key],
        ['#markup' => $value[0]],
      );
    }

    // The response body.
    $url = \Drupal::url('collect.generate_page', ['collect_container' => $collect_data->getContainer()->id()], ['absolute' => TRUE]);
    $output['body'] = array(
      '#type' => 'link',
      '#attributes' => array(
        'class' => 'button',
        'target' => '_blank',
      ),
      '#title' => $this->t('Show content in a new window'),
      '#url' => Url::fromUri($url),
    );

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTeaser(CollectDataInterface $data) {
    $display = parent::buildTeaser($data);
    $display['summary'] = [
      '#markup' => $this->t('@content_type, @size', [
        '@content_type' => $this->getQueryEvaluator()->evaluate($data->getParsedData(), 'response-headers.Content-Type.0'),
        '@size' => format_size(Unicode::strlen($this->getQueryEvaluator()->evaluate($data->getParsedData(), 'body'))),
      ]),
    ];
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStaticPropertyDefinitions() {
    $properties = [
      'accept' => new PropertyDefinition('request-headers.Accept.0', DataDefinition::create('string')
        ->setLabel(t('Request header: Accept'))),
      'content-type' => new PropertyDefinition('response-headers.Content-Type.0', DataDefinition::create('string')
        ->setLabel(t('Response header: Content-Type'))),
      'body_raw' => new PropertyDefinition('body', DataDefinition::create('any')
        ->setLabel(t('Raw body content'))),
      'body_text' => new PropertyDefinition('body?filter', DataDefinition::create('string')
        ->setLabel(t('Body text'))),
    ];

    return $properties;
  }
}
