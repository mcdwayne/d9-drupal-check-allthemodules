<?php
namespace Drupal\enterprise_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ChooseIndexForm extends ConfigFormBase
{

    public function getIndices() {

    }

    public function getFormId()
    {
       return 'enterprise_search_choose_index';
    }

    public function getEditableConfigNames()
    {
        // TODO: Implement getEditableConfigNames() method.
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        return parent::buildForm($form, $form_state);
    }
}