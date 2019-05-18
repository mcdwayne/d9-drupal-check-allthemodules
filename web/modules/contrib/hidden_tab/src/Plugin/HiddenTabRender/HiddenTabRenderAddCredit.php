<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabRender;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Form\OnPageAddCreditForm;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabRenderAnon;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderAdministrativeBase;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderSafeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Displays an add credit form on a hidden tab page.
 *
 * @HiddenTabRenderAnon(
 *   id = "hidden_tab_add_credit"
 * )
 */
class HiddenTabRenderAddCredit extends HiddenTabRenderAdministrativeBase {

  use HiddenTabRenderSafeTrait;

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_add_credit';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Add Credit';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'Displays an add credit form on a hidden tab page.';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 4;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * To build the add form.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              string $plugin_id,
                              $plugin_definition,
                              FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  protected function render0(EntityInterface $entity,
                             HiddenTabPageInterface $page,
                             AccountInterface $user,
                             ParameterBag $bag,
                             array &$output) {
    try {
      $this->render1($entity, $page, $user, $bag, $output);
    }
    catch (\Throwable $error) {
      $output[$this->id()] = [
        '#type' => 'markup',
        '#markup' => t('There was an error generating add credit form'),
      ];
      \Drupal::logger('hidden_tab')
        ->error('error while creating on page add credit form page={page} entity={entity} entity-type={h_type} user={user} msg={msg} trace={trace}', [
          'page' => $page->id(),
          'entity' => $entity->id(),
          'h_type' => $entity->getEntityTypeId(),
          'user' => $user->id(),
          'msg' => $error->getMessage(),
          'trace' => \str_replace("\n", ' ________ ', $error->getTraceAsString()),
        ]);
    }
  }

  protected function render1(EntityInterface $entity,
                             HiddenTabPageInterface $page,
                             AccountInterface $use,
                             ParameterBag $bag,
                             array &$output) {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $form = $this->formBuilder->getForm(OnPageAddCreditForm::class, $entity, $page);
    $output['admin'][$this->id()] = $form;
  }

}
