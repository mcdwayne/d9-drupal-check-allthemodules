<?php

namespace Drupal\dbg\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the PHP form executor.
 */
class PHPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dbg_php_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['code'] = [
      '#type' => 'textarea',
      '#rows' => 15,
      '#default_value' => '<?php' . (isset($_SESSION['dbg_code']) ? $_SESSION['dbg_code'] : PHP_EOL . PHP_EOL),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run'),
    ];

    if (isset($_SESSION['dbg_code'])) {
      unset($_SESSION['dbg_code']);
    }

    $form['#attached']['library'][] = 'dbg/editor.assets';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['dbg_code'] = str_replace('<?php', '', $form_state->getValue('code'));
    $result = eval($_SESSION['dbg_code']);
    if ($result) {
      dbg($result);
    }
  }

}
