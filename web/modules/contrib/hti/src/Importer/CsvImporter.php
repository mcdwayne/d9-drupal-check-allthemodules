<?php

  namespace Drupal\hierarchical_taxonomy_importer\Importer;

  use Drupal\hierarchical_taxonomy_importer\Base\ImporterBase;
  use Drupal\Core\Entity\EntityTypeManager;

  class CsvImporter extends ImporterBase {

    protected $entity_manager;
    protected $counter;

    public function __construct(EntityTypeManager $entity_manager) {
      parent::__construct();
      $this->entity_manager = $entity_manager;
      $this->counter = 0;
    }
    
    private function setCounter($counter) {
      $this->counter = $counter;
    }
    
    private function getCounter($counter) {
      return $this->counter;
    }

    public function getNestedTid($vid, $data, $parent = 0) {
      // Current Term Id
      $term = $data[0];
//      kint(count($data));
//      kint($this->counter);
//      print implode(",", $data);
      
      if (count($data) == 1 && empty($data[1])) {
        $parent_new = $this->entity_manager->getStorage('taxonomy_term')->create(
          [
            'name' => $data[0],
            'vid' => $vid,
          ]
        );
        $parent_new->save();
        $this->counter++;
        kint($parent_new);
        //$parent = $this->getNestedTid($vid, $data, $parent_new->id(), $this->counter);
      }
      
       if(!empty($data[$this->counter]) && !empty($data[$this->counter + 1])) {

        $parent_term = $this->entity_manager->getStorage('taxonomy_term')->loadByProperties(
          [
            'name' => $data[$this->counter + 1],
            'vid' => $vid,
          ]
        );
        
        $parent_new = $this->entity_manager->getStorage('taxonomy_term')->create(
          [
            'name' => $term,
            'parent' => array_keys($parent_term)[0],
            'vid' => $vid,
          ]
        );
        $parent_new->save();
        $parent = array_keys($parent_term)[0];
        $this->counter++;
        $parent = $this->getNestedTid($vid, $data, $parent, $this->counter);
      }
      else {
        return $data[$count];
      }
    }

    /**
     * This method returns a taxonomy term's id by going into depth. 
     */
    public function importTaxonomies($vid, $data, $reset) {

      // Checking for a valid VID
      if(empty($vid)) {
        throw new \Exception("Vocabulary ID is invalid or null");
      }

      /*if(!empty($data)) {
        // Checking fi teh row has only single record without the parent id
        $data_count = count($data);
        // This is applicable for the single record rows.
        if(empty($data[1]) && $data_count == 1) {
          // Setting up an array for the data of new taxonomy term.
          \Drupal\taxonomy\Entity\Term::create(
            [
              'name' => $data[0],
              'vid' => $vid,
            ]
          )->save();
        }
        elseif($data_count > 1) {

          $this->getNestedTid($vid, $data, $parent = 0, $count = 1, $reset);
        }
      }*/
      
      $this->getNestedTid($vid, $data, $parent = 0, $reset);
    }

  }

  /**
   * 1 check if count & count + 1 is available
   * 2 call self.
   * 3 if count + 1 is not available then return 
   * 
   * 
   */