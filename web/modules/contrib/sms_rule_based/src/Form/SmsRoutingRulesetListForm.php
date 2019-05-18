<?php

namespace Drupal\sms_rule_based\Form;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms\Entity\SmsGateway;

/**
 * @todo: Use the plugin display widgets to show the summary of the rules here.
 */
class SmsRoutingRulesetListForm extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_routing_ruleset_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return array(
      'enabled' => $this->t('Enable'),
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
      'rules' => $this->t('Rules'),
      'gateway' => $this->t('Gateway'),
    ) + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $gateway = SmsGateway::load($entity->get('gateway'));
    $label = $gateway ? $gateway->label() : $this->t('<em>!!<u>missing gateway</u>!!</em>');
    return array (
      'enabled' => array(
        '#type' => 'checkbox',
        '#title' => t('@title enabled', array('@title' => $entity->label())),
        '#title_display' => 'invisible',
        '#default_value' => TRUE == $entity->get('enabled'),
      ),
      'name' => ['#markup' => $entity->label()],
      'description' => ['#markup' => $entity->get('description')],
      'rules' => [
        '#theme' => 'sms_rule_based_routing_rules',
        '#ruleset' => $entity,
      ],
      'gateway' => ['#markup' => $label],
    ) + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $enable = (bool) \Drupal::config('sms_rule_based.settings')->get('enable_rule_based_routing');
    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable rule-based routing'),
      '#default_value' => $enable,
      '#weight' => 0,
    );
    // Place the table below the checkbox.
    $form[$this->entitiesKey]['#weight'] = 1;
//    $form[$this->entitiesKey]['#title'] = $this->t('Rule-based routing rulesets');
    $fallback = $this->fallbackGateway();
    $footer = array(
      array(
        t('Fallback Gateway'),
        t('All SMS that don\'t match above rules will go through the fallback gateway.'),
        $fallback ? $fallback->label() : $this->t('Fallback gateway not set'),
        [
          'data' => [
            '#type' => 'link',
            '#title' => $fallback ? $this->t('Change fallback gateway') : $this->t('Set fallback gateway'),
            '#url' => new Url('sms.settings', [], ['query' => ['destination' => \Drupal::destination()->get()]]),
          ],
        ],
      ),
    );

    $form['footer'] = array(
      '#type' => 'table',
      '#header' => array(
        [
          'data' => ['#markup' => $this->t('Fallback gateway')],
          'colspan' => 4,
        ]
      ),
      '#rows' => $footer,
      '#title' => $this->t('Fallback Gateway'),
      '#weight' => 2,
    );
    $form['actions']['submit']['#value'] = $this->t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update weights and status.
    if ($settings = $form_state->getValue($this->entitiesKey)) {
      foreach ($settings as $id => $value) {
        $changed = FALSE;
        if (isset($this->entities[$id]) && $this->entities[$id]->get($this->weightKey) != $value['weight']) {
          // Save entity only when its weight was changed.
          $this->entities[$id]->set($this->weightKey, $value['weight']);
          $changed = TRUE;
        }
        if (isset($this->entities[$id]) && $this->entities[$id]->get('enabled') != $value['enabled']) {
          // Save entity only when its weight was changed.
          $this->entities[$id]->set('enabled', $value['enabled']);
          $changed = TRUE;
        }
        if ($changed) {
          $this->entities[$id]->save();
        }
      }
    }
    $this->configFactory()->getEditable('sms_rule_based.settings')->set('enable_rule_based_routing', $form_state->getValue('enable'))->save();
  }


  /**
   * Returns the configuration factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected function configFactory() {
    return \Drupal::configFactory();
  }

  /**
   * Returns the fallback SMS gateway.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface
   */
  protected function fallbackGateway() {
    return SmsGateway::load($this->configFactory()->get('sms.settings')->get('fallback_gateway'));
  }

}
