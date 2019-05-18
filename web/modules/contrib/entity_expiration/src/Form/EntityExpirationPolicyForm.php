<?php
/**
 * @file
 * Contains \Drupal\entity_expiration\Form\SpectraExpirationPolicyForm.
 */

namespace Drupal\entity_expiration\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Utility\Html;

/**
 * Form controller for the entity_expiration_policy entity edit forms.
 *
 * @ingroup entity_expiration
 */
class EntityExpirationPolicyForm extends ContentEntityForm {

  /**
   * Build Bundle Type List
   */
  public function createEntityList() {
    $ret = array();
    foreach(\Drupal::entityTypeManager()->getDefinitions() as $type => $info) {
      // is this a content/front-facing entity?
      if ($info instanceof \Drupal\Core\Entity\ContentEntityType) {
        $label = $info->getLabel();
        if ($label instanceof \Drupal\Core\StringTranslation\TranslatableMarkup) {
          $label = $label->render();
        }
        $ret[$type] = $label;
      }
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\entity_expiration\Entity\EntityExpirationPolicy */
    $form = parent::buildForm($form, $form_state);

    $form['select_method'] = array(
      '#type' => 'select',
      '#title' => t('Method of Selecting Expiring Statements'),
      '#required' => TRUE,
      '#options' => array(),
      '#empty_option' => t('Select...'),
    );
    $select_plugin_manager = \Drupal::service('plugin.manager.entity_expiration_method');
    $select_plugin_definitions = $select_plugin_manager->getDefinitions();
    foreach ($select_plugin_definitions as $plugin => $definition) {
      foreach ($definition['select_options'] as $key => $method) {
        $form['select_method']['#options'][$key] = $method;
      }
    }

    $form['entity_type'] = array(
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#required' => TRUE,
      '#description' => t('The type of entity'),
      '#options' => $this->createEntityList()
    );

    $form['expire_method'] = array(
      '#type' => 'select',
      '#title' => t('Method of Expiration'),
      '#required' => TRUE,
      '#options' => array(),
      '#empty_option' => t('Select'),
    );
    foreach ($select_plugin_definitions as $plugin => $definition) {
      foreach ($definition['expire_options'] as $key => $method) {
        $form['expire_method']['#options'][$key] = $method;
      }
    }

    $form['expire_age'] = array(
      '#type' => 'textfield',
      '#title' => t('Age of Items to Expire'),
      '#description' => t('Age in seconds. 86400 = 1 day, 604800 = 7 days, 2592000 = 30 days, and 31536000 = 365 days'),
    );

    $form['expire_max'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum number of items to expire per cron run'),
      '#description' => t('Set to 0 to expire all applicable entities. If you are having performance issues, set this to a sufficiently low number.'),
    );

    $entity = $this->getEntity();
    $form_keys = array('select_method', 'entity_type', 'expire_method', 'expire_age', 'expire_max');
    foreach ($form_keys as $key) {
      if ($val = $entity->get($key)->getValue()) {
        $form[$key]['#default_value'] = $val[0]['value'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Redirect to term list after save.
    $form_state->setRedirect('entity.entity_expiration_policy.collection');
    $vals = $form_state->getValues();
    $entity = $this->getEntity();
    $entity->set('select_method', Html::escape($vals['select_method']));
    $entity->set('entity_type', Html::escape($vals['entity_type']));
    $entity->set('expire_method', Html::escape($vals['expire_method']));
    is_numeric($vals['expire_age']) ? $entity->set('expire_age', Html::escape($vals['expire_age'])) : NULL;
    isset($vals['expire_max']) && !empty($vals['expire_max']) && is_numeric($vals['expire_max']) ? $entity->set('expire_max', Html::escape($vals['expire_max'])) : NULL;
    $entity->save();
  }
}
