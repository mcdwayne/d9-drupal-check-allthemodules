<?php

namespace Drupal\group_behavior;

class FormHooks {

  public static function alterGroupContentTypeEditForm(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $config */
    $config = $formObject->getEntity();

    $form['third_party_settings']['#tree'] = TRUE;
    $tpsForm =&$form['third_party_settings']['group_behavior'];
    $tpsForm['#type'] = 'fieldset';
    $tpsForm['#title'] = t('Group Behavior');
    $tpsForm['#description'] = t('Cares about creating, deleting and enriching entities with a shadow group.');

    $tpsForm['autocreate'] = [
      '#type' => 'checkbox',
      '#title' => t('Autocreate'),
      '#description' => t('Autocreate group and relation on content entity create.'),
      '#default_value' => $config->getThirdPartySetting('group_behavior', 'autocreate'),
    ];
    $tpsForm['autoupdate_title'] = [
      '#type' => 'checkbox',
      '#title' => t('Autoupdate title'),
      '#description' => t('Autoupdate group and relation title on content entity update.'),
      '#default_value' => $config->getThirdPartySetting('group_behavior', 'autoupdate_title'),
    ];
    $tpsForm['autodelete'] = [
      '#type' => 'checkbox',
      '#title' => t('Autodelete'),
      '#description' => t('Autodelete group and relation on content entity delete.'),
      '#default_value' => $config->getThirdPartySetting('group_behavior', 'autodelete'),
    ];

    // Care that our settings go the right way.
    // @see \Drupal\group\Entity\Form\GroupContentTypeForm::submitForm
    array_unshift($form['actions']['submit']['#submit'], [static::class, 'submitGroupContentTypeEditForm']);
  }

  public static function submitGroupContentTypeEditForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $config */
    $config = $formObject->getEntity();

    $tps = $form_state->getValue(['third_party_settings', 'group_behavior']);
    foreach ($tps as $key => $value) {
      $config->setThirdPartySetting('group_behavior', $key, $value);
    }
    $form_state->unsetValue(['third_party_settings', 'group_behavior']);
    if (!$form_state->getValue('third_party_settings')) {
      $form_state->unsetValue('third_party_settings');
    }
  }

}
