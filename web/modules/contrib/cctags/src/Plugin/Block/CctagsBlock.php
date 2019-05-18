<?php

namespace Drupal\cctags\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'cctags' block.
 *
 * @Block(
 *   id = "cctags_block",
 *   admin_label = @Translation("Cctags Block"),
 *   category = @Translation("Cctags"),
 *   derivative = "Drupal\cctags\Plugin\Derivative\CctagsBlock"
 * )
 */
class CctagsBlock extends BlockBase implements ContainerFactoryPluginInterface {
  use LinkGeneratorTrait;
  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $connection = Database::getConnection();

    $cctid = $connection->select('cctags', 'c')
      ->fields('c', array('cctid'))
      ->condition('block_id', $this->getConfiguration()['block_id'])->execute()->fetchField();

    $terms = cctags_get_level_tags($cctid);
    $content = [
      '#theme' => 'cctags_level',
      '#terms' => $terms,
    ];
    return array(
      '#theme' => 'cctags_block',
      '#content' => $content,
    );

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['level'] = array(
      '#type' => 'select',
      '#options' => _cctags_get_select_list('level'),
      '#title' => $this->t('Number of levels fonts metrics'),
      '#default_value' => isset($config['level']) ? $config['level'] : '',
      '#description' => $this->t('The number of levels between the least popular tags and the most popular ones. Different levels will be assigned a different class to be themed in cctags.css'),
    );
    $form['tags'] = array(
      '#type' => 'select',
      '#title' => 'Tags to show',
      '#options' => _cctags_get_select_list('numtags'),
      '#default_value' => isset($config['tags']) ? $config['tags'] : '',
      '#maxlength' => 3,
      '#description' => $this->t('The number of tags to show in this block.'),
    );
    $op_sort = array('level,asc' => $this->t('by level, ascending'), 'level,desc' => $this->t('by level, descending'), 'title,asc' => $this->t('by title, ascending'), 'title,desc' => $this->t('by title, descending'), 'random,none' => $this->t('random'));
    $form['tags_sort'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Tags sort order'),
      '#options' => $op_sort,
      '#default_value' => isset($config['tags_sort']) ? $config['tags_sort'] : '',
    );
    $form['extra_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Block wrapper extra class'),
      '#maxlength' => 64,
      '#description' => $this->t('Extra class for block wrapper.'),
      '#default_value' => isset($config['extra_class']) ? $config['extra_class'] : '',
    );

    $form['tags_more'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable more link of end block'),
      '#default_value' => isset($config['tags_more']) ? $config['tags_more'] : false,
    );

    return $form;
  }

}
