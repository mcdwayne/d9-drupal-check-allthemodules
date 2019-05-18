# Drupal 8 Module entity_parser
Drupal 8 Module for convert Entity (Node,Taxonomy term and User) object complex structure to Simple Array or a Custom Type .
This module can support paragraphs <a target='_blank' href='https://www.drupal.org/project/paragraphs'>click</a> type field and can Extend to another Type Fields 
 
## Install 
 - git clone https://github.com/miandry/entity_parser.git 
 - Enable module and That's all !!
 
## Example Simple
<pre>
    $parser = new \Drupal\entity_parser\EntityParser();
    $nid = 1;
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $node_array = $parser->entity_parser_load($node); 
</pre> 
## Demo 
Login in to see Kint Devel module , username : admin / password : admin <a target='_blank' href='http://demo.e-roso.net/entity_parser_demo/demo' > Here </a>
## How to implement in your drupal project

- Create a Class for example EntityParserDemo.php extend  Drupal\entity_parser\EntityParser
<pre>
    namespace  Drupal\entity_parser_demo;
    use Drupal\entity_parser\EntityParser;
    class EntityParserDemo extend EntityParser{
       //****ADD HERE YOUR HOOK CUSTOM FUNCTION  of FIELDS BY NAME OR BY FIELD TYPE *****//
       
       //custom field image type by ALIAS = front
       function front_image_file($entity, $field) {
         return parent::image_file($entity, $field, 'medium');
       }
       //custom field by name
       function changed($entity, $field) {
         $item =$this->string($entity, $field);
         $changed_date = date("F j, Y, g:i a", array_shift($item));
         return  $changed_date ;
       }
    }
</pre>    
## How to use 
<pre>
    $parser = new EntityParserDemo();
    $nid = 1;
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    //full node array
    $node_array = $parser->node_parser_load($node);
    //get only nid and field_address
    $fields = ['nid','field_address'];
    $node_array_hook_alias = $parser->node_parser_load($node,fields);
    // hook_alias and exclude fields_exclude
    $options =array(
         'hook_alias' => 'front'
         'fields_exclude'=>['field_adress','nid','uuid']
    );
    $node_array_hook_alias = $parser->node_parser_load($node,array(),$options);
</pre> 
