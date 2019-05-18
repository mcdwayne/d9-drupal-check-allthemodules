<?php

namespace Drupal\ics_field\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\ics_field\CalendarProperty\CalendarPropertyProcessor;
use Drupal\ics_field\ICalFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'calendar_download_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "calendar_download_default_widget",
 *   label = @Translation("Calendar download default widget"),
 *   field_types = {
 *     "calendar_download_type"
 *   }
 * )
 */
class CalendarDownloadDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity_field.manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * @var CalendarPropertyProcessor
   */
  protected $calendarPropertyProcessor;

  /**
   * @var ICalFactory
   */
  protected $iCalFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($pluginId,
                              $pluginDefinition,
                              FieldDefinitionInterface $fieldDefinition,
                              array $settings,
                              array $thirdPartySettings,
                              Request $request,
                              Token $tokenService,
                              EntityFieldManager $entityFieldManager,
                              LoggerChannelInterface $logger,
                              CalendarPropertyProcessor $calendarPropertyProcessor,
                              ICalFactory $iCalFactory) {

    parent::__construct($pluginId,
                        $pluginDefinition,
                        $fieldDefinition,
                        $settings,
                        $thirdPartySettings);

    $this->request = $request;
    $this->tokenService = $tokenService;
    $this->entityFieldManager = $entityFieldManager;
    $this->logger = $logger;
    $this->calendarPropertyProcessor = $calendarPropertyProcessor;
    $this->iCalFactory = $iCalFactory;

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $pluginId,
                                $pluginDefinition) {

    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('token'),
      $container->get('entity_field.manager'),
      $container->get('logger.factory')->get('ics_field'),
      $container->get('ics_field.calendar_property_processor_factory')
                ->create($configuration['field_definition']),
      $container->get('ics_field.ical_factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   */
  public function formElement(FieldItemListInterface $items,
                              $delta,
                              array $element,
                              array &$form,
                              FormStateInterface $formState) {

    $fieldConfig = $this->isFieldConfigForm($formState);
    $fieldDefinitions = $this->getEntityFieldDefinitions();
    $element['summary'] = [
      '#type'          => 'textfield',
      '#placeholder'   => t('Summary'),
      '#title'         => t('Summary'),
      '#default_value' => isset($items[$delta]->summary) ?
        $items[$delta]->summary : NULL,
      '#required'      => !$fieldConfig,
    ];
    $element['description'] = [
      '#type'          => 'textarea',
      '#placeholder'   => t('Description'),
      '#title'         => t('Description'),
      '#default_value' => isset($items[$delta]->description) ?
        $items[$delta]->description : NULL,
      '#required'      => !$fieldConfig,
    ];
    $element['url'] = [
      '#type'          => 'textfield',
      '#placeholder'   => t('URL'),
      '#title'         => t('URL'),
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url :
        NULL,
    ];
    $tokenTree = [];
    foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
      $tokenTree['[node:' . $fieldName . ']'] = [
        'name'   => $fieldDefinition->getLabel(),
        'tokens' => [],
      ];
    }
    $element['tokens'] = [
      '#type'     => 'details',
      '#title'    => t('Tokens'),
      'tokenlist' => [
        '#type'       => 'token_tree_table',
        '#columns'    => ['token', 'name'],
        '#token_tree' => $tokenTree,
      ],
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()
                              ->getCardinality() === 1
    ) {
      $element += ['#type' => 'fieldset'];
    }

    return $element;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return bool
   */
  private function isFieldConfigForm(FormStateInterface $formState) {

    $build = $formState->getBuildInfo();
    return $build['base_form_id'] === 'field_config_form';
  }

  /**
   * Get the fields/properties from the entity the widget is attached to.
   *
   * @return FieldDefinitionInterface[]
   *
   * @throws \LogicException
   *   An array of FieldDefinitionInterfaces for all fields/properties.
   */
  private function getEntityFieldDefinitions() {
    $attBundle = $this->fieldDefinition->getConfig($this->fieldDefinition->getTargetBundle())
                                       ->get('bundle');
    $attEntityType = $this->fieldDefinition->get('entity_type');

    /** @var FieldDefinitionInterface[] $fieldDefinitions */
    $fieldDefinitions = array_filter(
                          $this->entityFieldManager->getBaseFieldDefinitions($attEntityType),
                          function ($fieldDefinition) {
                            return $fieldDefinition instanceof
                                   FieldDefinitionInterface;
                          }
                        ) + array_filter(
                          $this->entityFieldManager->getFieldDefinitions($attEntityType,
                                                                         $attBundle),
                          function ($fieldDefinition) {
                            return $fieldDefinition instanceof
                                   FieldDefinitionInterface;
                          }
                        );
    // Do not include ourselves in the list of fields that we'll use
    // for token replacement.
    $definitions = array_filter($fieldDefinitions,
      function ($key) {
        return $key !== $this->fieldDefinition->get('field_name');
      },
                                ARRAY_FILTER_USE_KEY);
    return $definitions;
  }

}
