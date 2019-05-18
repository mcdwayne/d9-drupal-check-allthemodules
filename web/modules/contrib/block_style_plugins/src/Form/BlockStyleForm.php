<?php

namespace Drupal\block_style_plugins\Form;

use Drupal\block_style_plugins\Plugin\BlockStyleManager;
use Drupal\block_style_plugins\IncludeExcludeStyleTrait;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a form for applying styles to a block.
 *
 * @internal
 */
class BlockStyleForm extends FormBase {

  use AjaxFormHelperTrait;
  use IncludeExcludeStyleTrait;

  /**
   * The Block Styles Manager.
   *
   * @var \Drupal\block_style_plugins\Plugin\BlockStyleManager
   */
  protected $blockStyleManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Instance of the Entity Repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The layout section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The uuid of the block component.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Constructs a BlockStylesForm object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\block_style_plugins\Plugin\BlockStyleManager $blockStyleManager
   *   The Block Style Manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   An Entity Repository instance.
   */
  public function __construct(FormBuilderInterface $form_builder, BlockStyleManager $blockStyleManager, EntityRepositoryInterface $entityRepository) {
    $this->formBuilder = $form_builder;
    $this->blockStyleManager = $blockStyleManager;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('plugin.manager.block_style.processor'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_style_plugins_layout_builder_styles';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;

    $component = $block_styles = $section_storage->getSection($delta)->getComponent($uuid);
    $block_styles = $component->getThirdPartySettings('block_style_plugins');

    // Get the component/block ID and then replace it with a block_content_type
    // if this is a reusable "block_content" block.
    $block_id = $component->getPluginId();
    preg_match('/^block_content:(.+)/', $block_id, $matches);
    if ($matches) {
      $plugin = $this->entityRepository->loadEntityByUuid('block_content', $matches[1]);

      if ($plugin) {
        $block_id = $plugin->bundle();
      }
    }

    // Retrieve a list of style plugin definitions.
    $style_plugins = [];
    foreach ($this->blockStyleManager->getDefinitions() as $plugin_id => $definition) {
      // Check to see if this should only apply to includes or if it has been
      // excluded.
      if ($this->allowStyles($block_id, $definition)) {
        $style_plugins[$plugin_id] = $definition['label'];
      }
    }

    // Create a list of applied styles with operation links.
    $items = [];
    foreach ($block_styles as $style_id => $configuration) {
      $options = [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
          'data-outside-in-edit' => TRUE,
        ],
      ];

      // Create links to edit and delete.
      $links = [
        'edit' => [
          '#title' => $this->t('Edit'),
          '#type' => 'link',
          '#url' => Url::fromRoute('block_style_plugins.layout_builder.add_styles', $this->getParameters($style_id), $options),
        ],
        'delete' => [
          '#title' => $this->t('Delete'),
          '#type' => 'link',
          '#url' => Url::fromRoute('block_style_plugins.layout_builder.delete_styles', $this->getParameters($style_id), $options),
        ],
        '#attributes' => ['class' => 'operations'],
      ];

      // If there is no plugin for the set block style then we should only allow
      // deleting. This could be due to a plugin being removed.
      if (!isset($style_plugins[$style_id])) {
        unset($links['edit']);
      }

      $plugin_label = !empty($style_plugins[$style_id]) ? $style_plugins[$style_id] : $this->t('Missing Style Plugin');

      $items[] = [
        ['#markup' => $plugin_label],
        $links,
      ];
    }
    if ($items) {
      $form['applied_styles_title'] = [
        '#markup' => '<h3>' . $this->t('Applied Styles') . '</h3>',
      ];
      $form['applied_styles'] = [
        '#prefix' => '<div id="applied-styles">',
        '#suffix' => '</div>',
        '#theme' => 'item_list',
        '#items' => $items,
        '#empty' => $this->t('No styles have been set.'),
        '#attached' => ['library' => ['block_style_plugins/off_canvas']],
      ];
    }

    // Dropdown for adding styles.
    $form['block_styles'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a style'),
      '#options' => $style_plugins,
      '#empty_value' => '',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Styles'),
    ];
    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      $form['actions']['submit']['#ajax']['event'] = 'click';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    $style_id = $form_state->getValue('block_styles');
    $parameters = $this->getParameters($style_id);
    $new_form = $this->formBuilder->getForm('\Drupal\block_style_plugins\Form\ConfigureStyles', $this->sectionStorage, $parameters['delta'], $parameters['uuid'], $parameters['plugin_id']);
    $new_form['#action'] = (new Url('block_style_plugins.layout_builder.add_styles', $parameters))->toString();
    $new_form['actions']['submit']['#attached']['drupalSettings']['ajax'][$new_form['actions']['submit']['#id']]['url'] = new Url('block_style_plugins.layout_builder.add_styles', $parameters, ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $response = new AjaxResponse();
    $response->addCommand(new OpenOffCanvasDialogCommand($this->t('Configure Styles'), $new_form));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getParameters($form_state->getValue('block_styles'));
    $url = new Url('block_style_plugins.layout_builder.add_styles', $parameters);
    $response = new RedirectResponse($url->toString());
    $form_state->setResponse($response);
  }

  /**
   * Gets the parameters needed for the various Url() and form invocations.
   *
   * @param string $style_id
   *   The id of the style plugin.
   *
   * @return array
   *   List of Url parameters.
   */
  protected function getParameters($style_id) {
    return [
      'section_storage_type' => $this->sectionStorage->getStorageType(),
      'section_storage' => $this->sectionStorage->getStorageId(),
      'delta' => $this->delta,
      'uuid' => $this->uuid,
      'plugin_id' => $style_id,
    ];
  }

}
