<?php
namespace Drupal\dynamic_banner\forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
//use Drupal\dynamic_banner\forms\FormBuilderModel; 

class DeleteBannerForm extends ConfirmFormBase {

   protected $bid;

   public function getFormId(){
      return "frm_deletebannerform";
   }

   public function getQuestion(){
      return t('Are you sure you want to delete the banner?');   
   }
   
   public function getCancelUrl(){
      return FALSE;  
   }

   public function getConfirmText() {
      return t('Delete');
   }

   public function getCancelRoute() {
      //return new Url('cdb.listbanners');
   }

   public function buildForm(array $form, FormStateInterface $form_state, $bid = NULL) {
      $this->bid = $bid;
      return parent::buildForm($form, $form_state);
   }

   public function submitForm(array &$form, FormStateInterface $form_state){
      $status = $this->dynamic_banner_admin_delete($this->bid);
      //watchdog('bd_contact', 'Deleted BD Contact Submission with id %id.', array('%id' => $this->id));

      \Drupal::logger('DynamicBanner')->error('Deleted BD Contact Submission with id %id.', array('%id' => $this->bid));

      if($status == 0){
        drupal_set_message(t('Error while deleting'));

      }else{
        drupal_set_message(t('Banner %id has been deleted.', array('%id' => $this->bid)));
      }

      //$form_state['redirect'] = 'admin/content/bd_contact';
      $form_state->setRedirect('cdb.listbanners');
   } 

   /**
   * Post-confirmation; delete a Banner
   */
   public function dynamic_banner_admin_delete($dbid = 0) {
   db_delete('dynamic_banner')->condition('dbid', $dbid)->execute();
   drupal_set_message(t('The banner has been deleted, the image still exists though'));
   }     
}