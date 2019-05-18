<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorCommandesAjax extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_commandes_ajax';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['into'] = array(
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Commands Ajax in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'form' => 'form_commands.php.twig',
        'commandsajax.routing.yml.twig'
      ),
    );
    $options = array(
      'before' => 'AJAX Before',
      'after' => 'AJAX After',
      'alert' => 'AJAX Alert',
      'html ' => 'AJAX html',
      'remove' => 'AJAX remove',
      'replace' => 'AJAX replace',
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Form class'),
      '#default_value' => 'AjaxFormsCommandsForm',
      '#description' => t('Has an impact of the path of the file : module/src/Form/xxx'),
      '#required' => TRUE,
    );
    $form['select'] = array(
      '#type' => 'select',
      '#options' => $options,
    );
    $form['note'] = array(
      '#type' => 'markup',
      '#markup' => $this->t( '<h2>How to use  Commands !</h2>
<ul>
<li>
<b>InsertCommand/ BeforeCommand</b> : The insert/before command instructs the client to use jQuery\'s before()
method to insert the given HTML content before each of elements matched by
the given selector.</li>
<li>
<b>InsertCommand/ AfterCommand</b>:  The insert/after command instructs the client to use jQuery\'s after()
method to insert the given HTML content after each element matched by the
given selector.</li>
<li> <b>CssCommand</b> : An AJAX command for adding css to the page via ajax.</li>
<li><b>AlertCommand </b>: AJAX command for a javascript alert box. </li>
<li><b>HtmlCommand</b> : The insert/html command instructs the client to use jQuery\'s html() method
to set the HTML content of each element matched by the given selector while
leaving the outer tags intact.</li>
<li><b>RemoveCommand</b> : The remove command instructs the client to use jQuery\'s remove() method
to remove each of elements matched by the given selector, and everything
within them. </li>' .
        '
</ul>'),
    );
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    return parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }
}