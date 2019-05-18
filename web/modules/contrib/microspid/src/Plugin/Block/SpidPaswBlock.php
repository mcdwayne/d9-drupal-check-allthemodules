<?php

namespace Drupal\microspid\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\microspid\Service\SpidPaswManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'SPID authentication status' block.
 *
 * @Block(
 *   id = "microspid_block",
 *   admin_label = @Translation("SPID Auth Status"),
 * )
 */
class SpidPaswBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * SimpleSAMLphp Authentication helper.
   *
   * @var SpidPaswManager
   */
  protected $simplesamlAuth;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('microspid.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Creates a LocalActionsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param SpidPaswManager $simplesaml_auth
   *   The SimpleSAML Authentication helper service.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SpidPaswManager $simplesaml_auth, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->simplesamlAuth = $simplesaml_auth;
    $this->config = $config_factory->get('microspid.settings');

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [
//      '#title' => $this->t('SimpleSAMLphp Auth Status'),
//      '#title' => $this->t('Stato dello SPID'),
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    if ($this->simplesamlAuth->isActivated()) {
      if (isset($_SESSION['spiduser']) 
        && $_SESSION['spiduser'] == \Drupal::currentUser()->getUsername()) { 
        $content['#markup'] = $this->t('Logged in as SPID user %authname<br /><a href=":logout">Log out</a>', [
          '%authname' => $_SESSION['spiduser'],
          ':logout' => Url::fromRoute('user.logout')->toString(),
        ]);
      }
      else {
        $login_link =  array (
          '#type' => 'inline_template',
          '#template' => _microspid_spidbutton(),
          '#context' => array(),
          '#weight' => 400,
        );
        $content['link'] = $login_link;
        $content['link']['#attached']['library'][] = 'microspid/spid-button';
      }
    }
    else {
      $content['#markup'] = $this->t('Warning: SPID module is not activated.');
    }

    return $content;
  }

}
