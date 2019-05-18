<?php

namespace Drupal\set_front_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\set_front_page\SetFrontPageManager;
use Drupal\Core\Url;

/**
 * The entity settings form for set_front_page.
 */
class SetFrontPageEntitySettings extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The set front page manager.
   *
   * @var \Drupal\set_front_page\SetFrontPageManager
   */
  protected $setFrontPageManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The route match.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\iset_front_page\SetFrontPageManager $setFrontPageManager
   *   The set_front_page manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(CurrentRouteMatch $routeMatch, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, SetFrontPageManager $setFrontPageManager, EntityTypeManagerInterface $entity_manager) {
    $this->routeMatch = $routeMatch;
    $this->pathValidator = $path_validator;
    $this->aliasManager = $alias_manager;
    $this->setFrontPageManager = $setFrontPageManager;
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('current_route_match'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('set_front_page.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'set_front_page_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->routeMatch->getParameter('node');
    if (!$entity) {
      $entity = $this->routeMatch->getParameter('taxonomy_term');
    }
    $config = $this->setFrontPageManager->getConfig();
    $frontpage = $this->setFrontPageManager->getFrontPage();
    $default = $config['default'];

    if (isset($entity) && $entity) {
      $current_page = $entity->toUrl()->toString();

      $form['set_front_page'] = [
        '#type' => 'table',
        '#header' => [$this->t('Pages'), $this->t('Path')],
      ];

      $form['set_front_page'][] = [
        'type' => [
          '#plain_text' => $this->t('Current front page'),
        ],
        'path' => [
          '#plain_text' => $frontpage,
        ],
      ];

      $form['set_front_page'][] = [
        'type' => [
          '#plain_text' => $this->t('Current page'),
        ],
        'path' => [
          '#plain_text' => $current_page,
        ],
      ];

      $form['set_front_page'][] = [
        'type' => [
          '#plain_text' => $this->t('Default front page'),
        ],
        'path' => [
          '#plain_text' => $default,
        ],
      ];

      $form['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Use this page as the front page'),
      ];

      // Disable the save button if the current page is already the home page.
      if ($frontpage == $current_page || $frontpage == $this->aliasManager->getAliasByPath($current_page)) {
        $form['save']['#attributes']['disabled'] = 'disabled';
      }

      // Revert to default button is displayed just when a
      // default path for the frontpage is defined.
      if (!empty($default)) {
        $form['change_to_default'] = [
          '#type' => 'submit',
          '#value' => $this->t('Revert to the default page'),
          '#submit' => ['::setDefault'],
        ];

        // Disable the revert to default button if this page
        // is already the frontpage.
        if ($frontpage == $default || $default == $current_page) {
          $form['change_to_default']['#attributes']['disabled'] = 'disabled';
        }
      }

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault(array &$form, FormStateInterface $form_state) {
    $config = $this->setFrontPageManager->getConfig();
    $this->setFrontPageManager->saveConfig($config['default']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->setFrontPageManager->getConfig();
    $frontpage = Url::fromRoute('<current>');
    if ($frontpage->isRouted()) {
      $node = \Drupal::routeMatch()->getParameter('node');
      $taxonomy_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
      $entity = $node ? $node : $taxonomy_term;
      if ($entity) {
        $frontpage = $entity->toUrl()->toString();
        $this->setFrontPageManager->saveConfig($frontpage);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->setFrontPageManager->getConfig();
    $frontpage = Url::fromRoute('<current>');

    if ($frontpage->isRouted()) {
      $node = \Drupal::routeMatch()->getParameter('node');
      $taxonomy_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
      if ($node) {
        if (!$this->setFrontPageManager->entityCanBeFrontPage($node)) {
          $form_state->setErrorByName('site_frontpage', $this->t("This node type is not allowed to be a frontpage."));
        }
      }
      if ($taxonomy_term) {
        if (!$this->setFrontPageManager->entityCanBeFrontPage($taxonomy_term)) {
          $form_state->setErrorByName('site_frontpage', $this->t("This vocabulary is not allowed to be a frontpage."));
        }
      }
    }
  }

}
