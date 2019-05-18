<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blocktabs\ConfigurableTabInterface;
use Drupal\blocktabs\BlocktabsInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for tab.
 */
abstract class TabFormBase extends FormBase {

  /**
   * The blockTabs.
   *
   * @var \Drupal\blocktabs\BlocktabsInterface
   */
  protected $blocktabs;

  /**
   * The tab.
   *
   * @var \Drupal\blocktabs\TabInterface
   */
  protected $tab;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tab_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\blocktabs\BlocktabsInterface $blocktabs
   *   The block tabs.
   * @param string $tab
   *   The tab ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, BlocktabsInterface $blocktabs = NULL, $tab = NULL) {
    $this->blocktabs = $blocktabs;
    try {
      $this->tab = $this->prepareTab($tab);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid tab id: '$tab'.");
    }
    $request = $this->getRequest();

    if (!($this->tab instanceof ConfigurableTabInterface)) {
      throw new NotFoundHttpException();
    }

    $form['#attached']['library'][] = 'blocktabs/admin';
    $form['uuid'] = [
      '#type' => 'hidden',
      '#value' => $this->tab->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $this->tab->getPluginId(),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab title'),
      '#default_value' => $this->tab->getTitle(),
      '#required' => TRUE,
    ];

    $form['data'] = $this->tab->buildConfigurationForm([], $form_state);
    $form['data']['#tree'] = TRUE;

    // Check the URL for a weight, then the tab, otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->tab->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->blocktabs->urlInfo('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The tab configuration is stored in the 'data' key in the form,
    // pass that through for validation.
    $tab_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->tab->validateConfigurationForm($form, $tab_data);
    // Update the original form values.
    $form_state->setValue('data', $tab_data->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The tab configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $tab_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->tab->submitConfigurationForm($form, $tab_data);
    // $logger = \Drupal::logger('blocktabs');
    // $logger->notice('submitForm:' . var_export($tab_data, true));
    // $logger->notice('default_view_name:' . var_export($default_view_name, true));
    // Update the original form values.
    $form_state->setValue('data', $tab_data->getValues());
    $this->tab->setTitle($form_state->getValue('title'));
    $this->tab->setWeight($form_state->getValue('weight'));
    if (!$this->tab->getUuid()) {
      $this->blocktabs->addTab($this->tab->getConfiguration());
    }
    else {
      $uuid = $this->tab->getUuid();
      $config = $this->tab->getConfiguration();
      $this->blocktabs->getTabs()->setInstanceConfiguration($uuid, $config);
	}
    // $config = $this->tab->getConfiguration();
    // $logger = \Drupal::logger('blocktabs');
    // $logger->notice('$config:' . var_export($config, true));
    // $tab = $this->blocktabs->getTab($this->tab->getUuid());
    // $config1 = $tab->getConfiguration();
    // $logger = \Drupal::logger('blocktabs');
    // $logger->notice('$config:' . var_export($config1, true));
    // $tab = $this->tab;
    $this->blocktabs->save();
   
    drupal_set_message($this->t('The tab was successfully applied.'));
    $form_state->setRedirectUrl($this->blocktabs->urlInfo('edit-form'));
  }

  /**
   * Converts a tab ID into an object.
   *
   * @param string $tab
   *   The tab ID.
   *
   * @return \Drupal\blocktabs\TabInterface
   *   The tab object.
   */
  abstract protected function prepareTab($tab);

}
