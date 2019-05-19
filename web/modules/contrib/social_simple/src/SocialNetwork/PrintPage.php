<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * The Printer button.
 */
class PrintPage implements SocialNetworkInterface {

  use StringTranslationTrait;

  /**
   * The social network base share link.
   */
  const PRINTER = '#';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'print';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Print');
  }

  /**
   * {@inheritdoc}
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []) {
    $options = [];

    if ($additional_options) {
      foreach ($additional_options as $id => $value) {
        $options['query'][$id] = $value;
      }
    }

    $url = Url::fromUserInput(self::PRINTER, $options);
    $link = [
      'url' => $url,
      'title' => ['#markup' => '<i class="fa fa-print"></i><span class="visually-hidden">' . $this->getLabel() . '</span>'],
      'attributes' => $this->getLinkAttributes($this->getLabel()),
    ];

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkAttributes($network_name) {
    $attributes = [
      'title' => $network_name,
      'data-popup-open' => 'false',
      'onClick' => 'window.print();',
    ];
    return $attributes;
  }

}
