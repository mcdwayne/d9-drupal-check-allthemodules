<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute option add form.
 */
class OptionAddForm extends OptionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);

    $form = parent::buildForm($form, $form_state, $aid);

    $form['#title'] = $this->t('Options for %name', ['%name' => $attribute->name]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove Form API elements from $form_state.
    $form_state->cleanValues();
    $oid = db_insert('uc_attribute_options')->fields($form_state->getValues())->execute();

    $aid = $form_state->getValue('aid');
    $option_name = $form_state->getValue('name');
    $edit_option_link = Link::createFromRoute($this->t('Edit option'), 'uc_attribute.option_edit', ['aid' => $aid, 'oid' => $oid])->toString();

    $this->messenger()->addMessage($this->t('Created new option %option.', ['%option' => $option_name]));
    $this->logger('uc_attribute')->notice('Created new option %option.', ['%option' => $option_name, 'link' => $edit_option_link]);
    $form_state->setRedirect('uc_attribute.option_add', ['aid' => $aid]);
  }

}
