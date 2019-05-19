<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\visualn\ConfigurableDrawerModifierInterface;
use Drupal\visualn\Entity\VisualNDrawerInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for drawer modifiers.
 */
abstract class DrawerModifierFormBase extends FormBase {

  /**
   * The  visualn drawer.
   *
   * @var \Drupal\visualn\Entity\VisualNDrawerInterface;
   */
  protected $visualnDrawer;

  /**
   * The drawer modifier.
   *
   * @var \Drupal\image\ImageEffectInterface|\Drupal\image\ConfigurableImageEffectInterface
   */
  protected $drawerModifier;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drawer_modifier_delete_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\visualn\Entity\VisualNDrawerInterface $visualn_drawer
   *   The  visualn drawer.
   * @param string $drawer_modifier
   *   The drawer modifier ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, VisualNDrawerInterface $visualn_drawer = NULL, $drawer_modifier = NULL) {
    $this->visualnDrawer = $visualn_drawer;
    try {
      $this->drawerModifier = $this->prepareDrawerModifier($drawer_modifier);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid modifier id: '$drawer_modifier'.");
    }
    $request = $this->getRequest();

    if (!($this->drawerModifier instanceof ConfigurableDrawerModifierInterface)) {
      throw new NotFoundHttpException();
    }

    $form['#attached']['library'][] = 'visualn/admin';
    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $this->drawerModifier->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->drawerModifier->getPluginId(),
    ];

    $form['data'] = [];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $this->drawerModifier->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;

    // Check the URL for a weight, then the drawer modifier, otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->drawerModifier->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->visualnDrawer->urlInfo('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The drawer modifier configuration is stored in the 'data' key in the form,
    // pass that through for validation.
    $this->drawerModifier->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The drawer modifier configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $this->drawerModifier->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));

    $this->drawerModifier->setWeight($form_state->getValue('weight'));
    if (!$this->drawerModifier->getUuid()) {
      $this->visualnDrawer->addDrawerModifier($this->drawerModifier->getConfiguration());
    }
    $this->visualnDrawer->save();

    drupal_set_message($this->t('The drawer modifier was successfully applied.'));
    $form_state->setRedirectUrl($this->visualnDrawer->urlInfo('edit-form'));
  }

  /**
   * Converts a drawer modifier ID into an object.
   *
   * @param string $drawer_modifier
   *   The drawer modifier ID.
   *
   * @return \Drupal\visualn\Plugin\VisualNDrawerModifierInterface
   *   The drawer modifier object.
   */
  abstract protected function prepareDrawerModifier($drawer_modifier);

}
