<?php

namespace Drupal\scriptjunkie\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the Script Junkie edit form.
 */
class EditForm extends ScriptJunkieFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'script_junkie_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildScript($sid) {
    return $this->scriptJunkieStorage->getScriptJunkieSettings(array('sid' => $sid));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL) {
    $form = parent::buildForm($form, $form_state, $sid);

    $form['#title'] = $this->script['name'];
    $form['sid'] = array(
      '#type' => 'hidden',
      '#value' => $this->script['sid'],
    );

    $url = new Url('scriptjunkie.settings.delete', array(
      'sid' => $this->script['sid'],
    ));

    if ($this->getRequest()->query->has('destination')) {
      $url->setOption('query', $this->getDestinationArray());
    }

    $form['actions']['delete'] = array(
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#url' => $url,
      '#attributes' => array(
        'class' => array('button', 'button--danger'),
      ),
    );

    return $form;
  }

}
