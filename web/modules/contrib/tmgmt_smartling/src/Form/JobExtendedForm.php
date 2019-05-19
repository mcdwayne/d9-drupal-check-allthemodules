<?php

namespace Drupal\tmgmt_smartling\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Form\JobForm;
use Drupal\tmgmt_extension_suit\ExtendedTranslatorPluginInterface;

class JobExtendedForm extends JobForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Everything below this line is only invoked if the 'Submit to provider'
    // button was clicked.
    if (isset($form['actions']['submit']) && $form_state->getTriggeringElement()['#value'] == $form['actions']['submit']['#value']) {
      $translator_plugin = $this->entity->getTranslatorPlugin();

      if ($translator_plugin instanceof ExtendedTranslatorPluginInterface) {
        // Log upload event.
        \Drupal::getContainer()
          ->get('logger.channel.smartling')
          ->info(
            t('File upload triggered (request translation). Job id: @job_id, file name: @name.', [
              '@name' => $translator_plugin->getFileName($this->entity),
              '@job_id' => $this->entity->id(),
            ])
          );
      }
    }

    parent::save($form, $form_state);
  }

}
