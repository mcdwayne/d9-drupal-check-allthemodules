<?php
namespace Drupal\ot\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ot\Controller\OverrideMain;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Delete extends ConfirmFormBase
{

  protected $id;

  public function __construct()
  {
    $this->ot_main = new OverrideMain();
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL)
  {
    $this->id = $id;
    if(!$this->ot_main->getOtById($this->id)){
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    if($this->ot_main->otDeleteById($this->id)){
      drupal_set_message(t("Override Title ID: '".$this->id. "' has been deleted successfully."), 'status');
      $form_state->setRedirectUrl(new Url('override.ot'));
    }else{
      drupal_set_message(t("Override Title ID: '".$this->id. "' cannot be deleted. Please try again."), 'error');
    }
  }


  public function getFormId()
  {
    return "confirm_delete_form";
  }


  public function getCancelUrl()
  {
    return new Url('override.ot');
  }

  public function getQuestion()
  {
    $title = $this->ot_main->getOtById($this->id);
    return t('Do you want to delete Override Title %id(%title)?', ['%id' => $this->id, '%title'=> $title['title']]);
  }

}
