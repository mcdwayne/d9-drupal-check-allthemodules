<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierManager;
use Drupal\visualn\Entity\VisualNDrawerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an add form for drawer modifiers.
 */
class DrawerModifierAddForm extends DrawerModifierFormBase {

  /**
   * The drawer modifier manager.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerModifierManager
   */
  protected $modifierManager;

  /**
   * Constructs a new DrawerModifierAddForm.
   *
   * @param \Drupal\visualn\Plugin\VisualNDrawerModifierManager $modifier_manager
   *   The drawer modifier manager.
   */
  public function __construct(VisualNDrawerModifierManager $modifier_manager) {
    $this->modifierManager = $modifier_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.visualn.drawer_modifier')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VisualNDrawerInterface $visualn_drawer = NULL, $drawer_modifier = NULL) {
    $form = parent::buildForm($form, $form_state, $visualn_drawer, $drawer_modifier);

    $form['#title'] = $this->t('Add %label modifier', ['%label' => $this->drawerModifier->label()]);
    $form['actions']['submit']['#value'] = $this->t('Add modifier');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDrawerModifier($drawer_modifier) {
    $drawer_modifier = $this->modifierManager->createInstance($drawer_modifier);
    // Set the initial weight so this modifier comes last.
    $drawer_modifier->setWeight(count($this->visualnDrawer->getModifiers()));
    return $drawer_modifier;
  }

}
