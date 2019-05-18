<?php

/**
 * @file
 * Contains \Drupal\searchcloud_block\Plugin\Block\SearchCloudBlockBlock.
 */

namespace Drupal\searchcloud_block\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\searchcloud_block\Services\SearchCloudServiceProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a searchcloudblock block.
 *
 * @Block(
 *   id = "searchcloudblock",
 *   subject = @Translation("Searchcloud"),
 *   admin_label = @Translation("Searchcloud Block")
 * )
 */
class SearchCloudBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $searchPage;

  protected $searchcloudService;

  /**
   * Creates a SearchCloudBlockBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, SearchCloudServiceProvider $searchcloud_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory      = $config_factory;
    $this->searchPage         = $entity_manager->getStorageController('search_page');
    $this->searchcloudService = $searchcloud_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'), $container->get('entity.manager'), $container->get('searchcloud_block.serviceProvider'));
  }

  /**
   * Overrides \Drupal\block\BlockBase::defaultConfiguration().
   */
  public function defaultConfiguration() {
    return array(
      'order'       => 'RAND',
      'stylecount'  => 5,
      'count'       => 10,
      'search_page' => 'node_search',
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['order']      = array(
      '#type'          => 'select',
      '#title'         => t('Sort order of the search terms in this block'),
      '#default_value' => $this->configuration['order'],
      '#options'       => array(
        'RAND' => t('Random'),
        'DESC' => t('Descending'),
        'ASC'  => t('Ascending'),
      ),
    );
    $form['count']      = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Number of terms shown'),
      '#size'          => 6,
      '#maxlength'     => 6,
      '#default_value' => $this->configuration['count'],
    );
    $form['stylecount'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Number of styles to use'),
      '#size'          => 6,
      '#maxlength'     => 6,
      '#default_value' => $this->configuration['stylecount'],
    );
    $searchpages        = $this->searchPage->loadMultiple();
    $pages              = array();
    foreach ($searchpages as $page) {
      $pages[$page->id] = $page->label;
    }
    $form['search_page'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Select the searchpage'),
      '#default_value' => $this->configuration['search_page'],
      '#options'       => $pages,
    );

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['order']       = $form_state['values']['order'];
    $this->configuration['stylecount']  = $form_state['values']['stylecount'];
    $this->configuration['count']       = $form_state['values']['count'];
    $this->configuration['search_page'] = $form_state['values']['search_page'];
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $items = $this->getKeys();
    if (!empty($items)) {
      return array(
        '#theme' => 'item_list',
        '#items' => $items,
      );
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get a array of keys.
   *
   * @return array|bool
   *   An array of items, if any. Or FALSE.
   */
  protected function getKeys() {
    $sort       = $this->configuration['order'];
    $count      = $this->configuration['count'];

    $result = $this->searchcloudService->getResult(FALSE, FALSE, $count, $sort);
    $items  = array();

    $max  = 0;
    $min  = 9999;
    $rows = array();

    foreach ($result as $row) {
      $rows[] = $row;
      if ($row->count > $max) {
        $max = $row->count;
      }
      if (!isset($min) || $row->count < $min) {
        $min = $row->count;
      }
    }

    if (!empty($rows)) {
      return $this->getItems($rows, $min, $max);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get all the items from rows.
   *
   * @param array $rows
   *   The rows.
   * @param int $min
   *   Minimum value.
   * @param int $max
   *   Maximum value.
   *
   * @return array
   *   All the items ready for the render array.
   */
  protected function getItems($rows, $min, $max) {
    $sort       = $this->configuration['order'];
    $stylecount = $this->configuration['stylecount'];

    $stepsize = 1;
    if (count($rows) > 1) {
      $stepsize = ceil(($max - $min) / $stylecount);
      if ($sort == 'RAND') {
        shuffle($rows);
      }
    }
    if ($stepsize < 1) {
      $stepsize = 1;
    }

    foreach ($rows as $row) {
      $ratio   = ceil($row->count / $stepsize);
      $items[] = array(
        '#markup' => $this->getSearchLink($row->keyword),
        '#wrapper_attributes' => array(
          'class' => array('cloud-' . $ratio),
        ),
      );

    }
    return $items;

  }

  /**
   * Return the link to the searchpage with the keyword.
   *
   * @param string $keyword
   *   The keyword to search for.
   *
   * @return string
   *   The link to the searchpage.
   */
  protected function getSearchLink($keyword) {
    $config     = $this->configFactory->get('searchcloud_block.settings');
    $override_search = $config->get('searchcloud_block_overridepath');
    $use_param = $config->get('searchcloud_block_useparam');
    $param = $config->get('searchcloud_block_paramname');

    if (!empty($override_search)) {
      $root_search_path = $override_search;
    }
    else {
      $searchpage       = entity_load('search_page', $this->configuration['search_page']);
      $root_search_path = 'search/' . $searchpage->getPath();
    }

    if (!empty($use_param)) {
      return l($keyword, $root_search_path, array(
        'query' => array(
          $param => $keyword,
        ),
      ));
    }
    else {
      return l($keyword, $root_search_path . '/' . $keyword);
    }
  }

}
