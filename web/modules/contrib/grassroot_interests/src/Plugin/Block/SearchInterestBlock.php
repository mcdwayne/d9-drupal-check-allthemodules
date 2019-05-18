<?php

/**
 * @file
 * Contains \Drupal\grassroot_interests\Plugin\Block\SearchInterestBlock.
 */

namespace Drupal\grassroot_interests\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\grassroot_interests\GrassrootInterestManagerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'Example: empty block' block.
 *
 * @Block(
 *   id = "search_interest",
 *   subject = @Translation("GI - Your Interests"),
 *   admin_label = @Translation("GI - Your Interests")
 * )
 */
class SearchInterestBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The search interest manager.
   *
   * @var \Drupal\grassroot_interests\GrassrootInterestManagerInterface
   */
  protected $grassrootManager;

  /**
   * Constructs a new SearchInterestBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\grassroot_interests\GrassrootInterestManagerInterface $search_interest_manager
   *   The search interest manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, GrassrootInterestManagerInterface $search_interest_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request;
    $this->grassrootManager = $search_interest_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('grassroot_interests.grassroot_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'number_of_result' => 0,
      'display_text' => 'You might be interested in',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['number_of_result'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of results to display'),
      '#size' => 50,
      // Nine results are to be displayed by default.
      '#description' => t('Number of results to display (by default it is set to Show All)'),
      '#default_value' => $this->configuration['number_of_result'],
      '#prefix' => '<div class="clear-block no-float">',
      '#suffix' => '</div>',
    );
    $form['display_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Display text before url title'),
      '#size' => 50,
      '#description' => t('Something like "You might be interested in - (title)"'),
      '#default_value' => $this->configuration['display_text'],
      '#prefix' => '<div class="clear-block no-float">',
      '#suffix' => '</div>',
    );
      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $number_of_result = $config['number_of_result'];
    $display_text = $config['display_text'];

    $content = array();
    if ($this->request->query->has('keys')) {
      $keyword = trim($this->request->get('keys'));
      $query = $this->grassrootManager->getAll()
        ->condition('keyword', $keyword);

      if ($number_of_result != 0) {
        $query->range(0, $number_of_result);
      }
      $output = $query->execute()->fetchAll();

      if (!empty($output)) {
        $data = array();

        foreach ($output as $key => $value) {
          $title = t('@title', array('@title' => $value->kw_title));
          $grassroot_interests_url = \Drupal::urlGenerator()->generateFromPath($value->root_url, array('absolute' => TRUE));
          $uri = Url::fromUri($grassroot_interests_url, array('attributes' => array('class' => 'search-interest-link')));

          $interests_link = \Drupal::l($title, $uri);
          $interests = t('@text', array('@text' => $display_text)) . ' ' . $interests_link . '.';
          // Set custom attributes for a list item.
          $data[] = array(
            '#markup' => $interests,
            '#wrapper_attributes' => array(
              'id' => Html::getId($value->kw_title),
              'class' => array(Html::getClass($value->kw_title), 'grassroot-interest-item'),
            ),
          );
        }
        $content = array(
          '#theme' => 'item_list',
          '#items' => $data,
          '#type' => 'ul',
          '#attributes' => array('id' => 'my-interests-listing', 'class' => array('list-messages')),
          '#prefix' => '<div class="message-specialty" id="search-results-visit-specialty">',
          '#suffix' => '</div>',
        );
      }
    }
    return $content;
  }
}
