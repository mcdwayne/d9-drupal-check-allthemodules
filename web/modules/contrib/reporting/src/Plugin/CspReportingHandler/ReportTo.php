<?php

namespace Drupal\reporting\Plugin\CspReportingHandler;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\csp\Csp;
use Drupal\csp\Plugin\ReportingHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CSP Reporting Plugin for Report-To endpoints.
 *
 * @CspReportingHandler(
 *   id = "reporting",
 *   label = "Reporting Endpoint",
 *   description = @Translation("Reports will be sent to a Reporting Endpoint."),
 * )
 *
 * @see report-uri.com
 */
class ReportTo extends ReportingHandlerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Reporting Endpoint Storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $reportingEndpointStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $reportingEndpointStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->reportingEndpointStorage = $reportingEndpointStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entityStorage = $container->get('entity_type.manager')->getStorage('reporting_endpoint');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entityStorage
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form) {

    $form['endpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t('The <a href=":url">reporting endpoint</a> to use.', [
        ':url' => Url::fromRoute('entity.reporting_endpoint.collection')->toString(),
      ]),
      '#options' => [],
      '#default_value' => isset($this->configuration['endpoint']) ? $this->configuration['endpoint'] : '',
      '#states' => [
        'required' => [
          ':input[name="' . $this->configuration['type'] . '[enable]"]' => ['checked' => TRUE],
          ':input[name="' . $this->configuration['type'] . '[reporting][handler]"]' => ['value' => $this->pluginId],
        ],
      ],
    ];

    $query = $this->reportingEndpointStorage->getQuery();
    if (($result = $query->execute())) {
      $endpoints = $this->reportingEndpointStorage->loadMultiple($result);

      /** @var \Drupal\reporting\Entity\ReportingEndpointInterface $endpoint */
      foreach ($endpoints as $endpoint) {
        $form['endpoint']['#options'][$endpoint->id()] = $endpoint->label() . (!$endpoint->status() ? ' (' . $this->t('Disabled') . ')' : '');
      }
    }

    unset($form['#description']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPolicy(Csp $policy) {
    /** @var \Drupal\reporting\Entity\ReportingEndpointInterface $reportingEndpoint */
    $reportingEndpoint = $this->reportingEndpointStorage->load($this->configuration['endpoint']);

    if ($reportingEndpoint && $reportingEndpoint->status()) {
      $policy->setDirective('report-uri', $reportingEndpoint->toUrl('log', ['absolute' => TRUE])->toString());
      $policy->setDirective('report-to', $reportingEndpoint->id());
    }
  }

}
