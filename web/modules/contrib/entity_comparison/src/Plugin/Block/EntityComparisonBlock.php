<?php

namespace Drupal\entity_comparison\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

/**
 * Provides a generic entity comparison block.
 *
 * @Block(
 *   id = "entity_comparison_block",
 *   admin_label = @Translation("Comparison"),
 *   category = @Translation("Comparisons"),
 *   deriver = "Drupal\entity_comparison\Plugin\Derivative\EntityComparisonBlock"
 * )
 */
class EntityComparisonBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity comparison storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityComparison;

  /**
   * Link generator
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs new EntityComparisonBlock.
   *
   *   The entity comparison storage.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_comparison
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $entity_comparison, LinkGenerator $link_generator, Session $session, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityComparison = $entity_comparison;
    $this->linkGenerator = $link_generator;
    $this->session = $session;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('entity_comparison'),
      $container->get('link_generator'),
      $container->get('session'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $defaults = $this->defaultConfiguration();

    // Link text
    $form['link_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $config['link_text'],
      '#description' => $this->t("You can use the @count variable, which will be replaced with the count of the user's comparison list. You can also use the %comparison_label, which will be replaced with the comparison's label."),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['link_text'] = $form_state->getValue('link_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Load the related entity comparison
    $entity_comparison_id = $this->getDerivativeId();
    $entity_comparison = $this->entityComparison->load($entity_comparison_id);

    // Get the count of the items
    $count = $this->getNumberOfItems($entity_comparison);

    // Adjust the menu tree parameters based on the block's configuration.
    $link_text = $this->t($this->configuration['link_text'], array(
      '@count' => $count,
      '%comparison_label' => $entity_comparison->label(),
    ));

    $url = Url::fromRoute('entity_comparison.compare.' . $entity_comparison_id);

    return array(
      '#markup' => $this->linkGenerator->generate($link_text, $url),
      '#cache' => array(
        'max-age' => 0,
      ),
    );
  }

  /**
   * Get number of the items
   *
   * @param $entity_comparison
   * @return int
   */
  protected function getNumberOfItems($entity_comparison) {
    // Get current user's id
    $uid = $this->currentUser->id();

    // Get entity type and bundle type
    $entity_type = $entity_comparison->getTargetEntityType();
    $bundle_type = $entity_comparison->getTargetBundleType();

    // Get current entity comparison list
    $entity_comparison_list = $this->session->get('entity_comparison_' . $uid);

    if ( isset($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]) ) {
      return count($entity_comparison_list[$entity_type][$bundle_type][$entity_comparison->id()]);
    } else {
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link_text' => $this->t("Compare @count items", array('@count' => '@count')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the entity comparison block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the comparison list is changed, this
    // entity comparison block must also be re-rendered for that user.
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:entity_comparison.' . $this->getDerivativeId();
    return $cache_tags;
  }

}
