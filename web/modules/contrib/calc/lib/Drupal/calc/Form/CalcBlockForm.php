<?php

/**
 * @file
 * Contains \Drupal\calc\Form\CalcBlockForm.
 */

namespace Drupal\calc\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the calc form for the calc block.
 */
class CalcBlockForm extends FormBase implements FormInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'calc_generate_calculator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    
    // Save the request variable for use in the submit method.
    $this->request = $request;

    $form['calc_result'] = array(
      '#type' => 'textfield',
      '#size' => 25,
      '#default_value' => 0,
      '#attributes' => array(
         'readonly'=>'readonly',
       ),
      '#prefix' => '<div id="calc-wrapper" style="display:none;"><div id="calcFormResult">',
      '#suffix' => '</div>',
    );

    $form['clear_1'] = array(
      '#markup' => '<div class="clear_both"></div>',
    );
    
    $form['calc_b7'] = array(
        '#markup' => '<input type="button" name="b-7" value="7" onclick="calc(\'7\')" />',
        '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_b8'] = array(
      '#markup' => '<input type="button" name="b-8" value="8" onclick="calc(\'8\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_b9'] = array(
      '#markup' => '<input type="button" name="b-9" value="9" onclick="calc(\'9\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    
     $form['calc_div'] = array(
      '#markup' => '<input type="button" name="b-div" value="/" onclick="calc(\'/\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    
    $form['calc_clr'] = array(
      '#markup' => '<input type="button" name="b-C" value="C" onclick="calc(\'C\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    
    $form['clear_2'] = array(
      '#markup' => '<div class="clear_both"></div>',
    );
    
    $form['calc_b4'] = array(
      '#markup' => '<input type="button" name="b-4" value="4" onclick="calc(\'4\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_b5'] = array(
      '#markup' => '<input type="button" name="b-5" value="5" onclick="calc(\'5\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_b6'] = array(
      '#markup' => '<input type="button" name="b-6" value="6" onclick="calc(\'6\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_mul'] = array(
      '#markup' => '<input type="button" name="b-mul" value="*" onclick="calc(\'*\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_prc'] = array(
      '#markup' => '<input type="button" name="b-prc" value="%" onclick="calc(\'%\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    
    $form['clear_3'] = array(
      '#markup' => '<div class="clear_both"></div>',
    );
    
    $form['calc_b1'] = array(
      '#markup' => '<input type="button" name="b-1" value="1" onclick="calc(\'1\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_b2'] = array(
      '#markup' => '<input type="button" name="b-2" value="2" onclick="calc(\'2\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_b3'] = array(
      '#markup' => '<input type="button" name="b-3" value="3" onclick="calc(\'3\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_min'] = array(
      '#markup' => '<input type="button" name="b-min" value="-" onclick="calc(\'-\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_rep'] = array(
      '#markup' => '<input style="font-size:90%" type="button" name="b-rep" value="1/x" onclick="calc(\'1/x\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    
    $form['clear_4'] = array(
      '#markup' => '<div class="clear_both"></div>',
    );
    
    $form['calc_b0'] = array(
      '#markup' => '<input type="button" name="b-0" value="0" onclick="calc(\'0\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_sgn'] = array(
      '#markup' => '<input style="font-size:80%" type="button" name="b-sgn" value="+/-" onclick="calc(\'+/-\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_pnt'] = array(
      '#markup' => '<input type="button" name="b-pnt" value="." onclick="calc(\'.\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_pls'] = array(
      '#markup' => '<input type="button" name="b-pls" value="+" onclick="calc(\'+\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div>',
    );
    $form['calc_eql'] = array(
      '#markup' => '<input type="button" name="b-eql" value="=" onclick="calc(\'=\')" />',
      '#prefix' => '<div class="calcFormItem">',
      '#suffix' => '</div></div>',
    );
    
    $form['clear_5'] = array(
      '#markup' => '<div class="clear_both"></div>',
    );

    //$form['#theme'] = 'calculator';
    drupal_add_js(drupal_get_path('module', 'calc') . '/js/calc.js');
    drupal_add_css(drupal_get_path('module', 'calc') . '/css/calc.css');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }
}
