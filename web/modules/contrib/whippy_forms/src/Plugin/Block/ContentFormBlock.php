<?php
/**
 * Contains  Drupal\whippy_forms\Plugin\Block\ContentFormBlock\ContentFormBlock
 */
namespace Drupal\whippy_forms\Plugin\Block\ContentFormBlock;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ContentFormBlock
 *
 * @Block(
 *   id = "content_form_block",
 *   admin_label = @Translation("Content Form block"),
 * )
 *
 */
class ContentFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var EntityFormBuilderInterface $entityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * @var EntityDisplayRepositoryInterface $entityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a Block object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFormBuilderInterface $entityFormBuilder, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get config object.
    $config = $this->getConfiguration();

    // Retrieve required params.
    $type   = $config['type'];
    $bundle = $config['bundle'];
    $mode   = $config['mode'];

    // Get entity by type and bundle.
    $entity = $this->entityTypeManager->getStorage($type)->create(['type' => $bundle,]);

    // Get form object for entity in configured mode.
    $form = $this->entityFormBuilder->getForm($entity, $mode);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $values = $form_state->getValues();

    // Prepare content entity type options.
    $entityDefinitions = $this->entityTypeManager->getDefinitions();
    $contentEntityDefinitions = array_filter($entityDefinitions, function($item) {
      return ($item instanceof \Drupal\Core\Entity\ContentEntityType) && $item->getBundleEntityType();
    });

    $typeOptions = [
      '' => '--None--',
    ];
    foreach ($contentEntityDefinitions as $key => $item) {
      $typeOptions[$key] = $item->getLabel();
    }

    // Entity type select.
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t('Please, choose entity type.'),
      '#options' => $typeOptions,
      '#required' => TRUE,
      '#default_value' => isset($config['type']) ? $config['type'] : '',
      '#ajax' => [
        'callback' => [$this, 'addBundleInputCallback'],
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'bundle-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    // Prepare bundle options for selected entity type.
    if (isset($values['settings']['type'])) {
      $type = $values['settings']['type'];
    }
    else {
      $type = isset($config['type']) ? $config['type'] : '';
    }
    if($type && array_key_exists($type, $contentEntityDefinitions)) {
      $bundleEntityType = $contentEntityDefinitions[$type]->getBundleEntityType();

      $contentTypes = $this->entityTypeManager->getStorage($bundleEntityType)->loadMultiple();
      $bundleOptions = [
        '' => '--None--',
      ];
      foreach ($contentTypes as $key => $item) {
        $bundleOptions[$key] = $item->label();
      }

      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundleOptions,
        '#required' => TRUE,
        '#description' => $this->t('Please, choose bundle.'),
        '#default_value' => isset($config['bundle']) ? $config['bundle'] : '',
        '#ajax' => [
          'callback' => [$this, 'addModeInputCallback'],
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => 'mode-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ],
      ];
    }
    $form['bundle']['#prefix'] = '<div id="bundle-wrapper">';
    $form['bundle']['#suffix'] = '</div>';

    // Prepare form mode options.
    if (isset($values['settings']['bundle'])) {
      $bundle = $values['settings']['bundle'];
    }
    else {
      $bundle = isset($config['bundle']) ? $config['bundle'] : '';
    }
    if ($type && $bundle) {
      $formDisplayModes = $this->entityDisplayRepository->getFormModeOptionsByBundle($type, $bundle);

      $formModeOptions = [];
      foreach ($formDisplayModes as $key => $item) {
        $formModeOptions[$key] = $item;
      }

      $form['mode'] = [
        '#type' => 'select',
        '#title' => $this->t('Mode'),
        '#required' => TRUE,
        '#options' => $formModeOptions,
        '#description' => $this->t('Please, choose form mode.'),
        '#default_value' => isset($config['mode']) ? $config['mode'] : 'default'
      ];
    }
    else {

    }
    $form['mode']['#prefix'] = '<div id="mode-wrapper">';
    $form['mode']['#suffix'] = '</div>';

    return $form;
  }

  /*
   * Ajax callback for type select element.
   * Rebuilds type and mode selects.
   */
  public function addBundleInputCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $form_state->setRebuild(true);
    $response->addCommand(new ReplaceCommand('#bundle-wrapper', $form['settings']['bundle']));
    $response->addCommand(new ReplaceCommand('#mode-wrapper', $form['settings']['mode']));

    return $response;
  }

  /*
   * Ajax callback for bundle select element.
   * Rebuilds mode select.
   */
  public function addModeInputCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $form_state->setRebuild(true);
    $response->addCommand(new ReplaceCommand('#mode-wrapper', $form['settings']['mode']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('type', $form_state->getValue('type'));
    $this->setConfigurationValue('bundle', $form_state->getValue('bundle'));
    $this->setConfigurationValue('mode', $form_state->getValue('mode'));
  }

}
