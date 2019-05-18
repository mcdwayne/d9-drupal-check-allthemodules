<?php

namespace Drupal\bibcite\Plugin\BibCiteProcessor;

use AcademicPuma\CiteProc\CiteProc;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\bibcite\Plugin\BibCiteProcessorBase;
use Drupal\bibcite\Plugin\BibCiteProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a style provider based on citeproc-php library.
 *
 * @BibCiteProcessor(
 *   id = "citeproc-php",
 *   label = @Translation("Citeproc PHP"),
 * )
 */
class CiteprocPhp extends BibCiteProcessorBase implements BibCiteProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Render citation by citeproc-php library');
  }

  /**
   * {@inheritdoc}
   */
  public function render($data, $csl, $lang) {
    $cite_proc = new CiteProc($csl, $lang);

    if (!$data instanceof \stdClass) {
      $data = json_decode(json_encode($data));
    }

    return $cite_proc->render($data);
  }

}
