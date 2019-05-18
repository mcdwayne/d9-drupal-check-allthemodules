<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabRender;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Form\OnPageSendMailForm;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabRenderAnon;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderAdministrativeBase;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderSafeTrait;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Displays buttons to trigger mailers.
 *
 * @HiddenTabRenderAnon(
 *   id = "hidden_tab_admin_senders"
 * )
 */
class HiddenTabRenderAdminSenders extends HiddenTabRenderAdministrativeBase {

  use HiddenTabRenderSafeTrait;

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_admin_senders';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Admin Senders';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'Displays buttons to trigger mailers.';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 2;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * To build the send form.
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

  /**
   * {@inheritdoc}
   */
  public function render0(EntityInterface $entity,
                          HiddenTabPageInterface $page,
                          AccountInterface $user,
                          ParameterBag $bag,
                          array &$output) {
    return;
    try {
      $this->render1($entity, $page, $user, $bag, $output);
    }
    catch (\Throwable $error) {
      $output[$this->id()] = [
        '#type' => 'markup',
        '#markup' => t('There was an error generating send mail form'),
      ];
      Utility::renderLog($error, $entity->getEntityTypeId(), 'mailer', $entity->id(), 'mailer send form');
    }
  }

  protected function render1(EntityInterface $entity,
                             HiddenTabPageInterface $page,
                             AccountInterface $use,
                             ParameterBag $bag,
                             array &$output) {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $form = $this->formBuilder->getForm(
      OnPageSendMailForm::class, $entity, $page);
    $output['admin'][$this->id()] = $form;
  }

}
