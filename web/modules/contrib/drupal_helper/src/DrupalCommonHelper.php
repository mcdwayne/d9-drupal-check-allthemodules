<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:52 PM
 */

namespace Drupal\drupal_helper;

use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\RedirectResponse;


class DrupalCommonHelper
{

    public function entity_render_tostring($id,$entity_type='node',$mode_view='teaser'){
        $result = $this->entity_render_toview($id,$entity_type,$mode_view);

        return \Drupal::service('renderer')->renderRoot($result);
    }
    public function entity_render_toview($id,$entity_type='node',$mode_view='teaser'){
        $entity_type_manager = \Drupal::entityTypeManager();
        $entity = $entity_type_manager->getStorage($entity_type)->load($id);

        $view_builder = $entity_type_manager->getViewBuilder($entity_type);
        return $view_builder->view($entity, $mode_view);
    }

    public function get_router_list($route_name_list = [] )
    {
        $db = Database::getConnection();
        $query = $db->select('router', 'rt');
        $query->fields('rt', ['name', 'pattern_outline', 'path','number_parts']);
        $query->condition('rt.name', ($route_name_list), 'IN');
        return  $query->execute()->fetchAllAssoc('name');
    }
    public function taxonomy_load_by_tid($tid, $type = "teaser")
    {
        if (is_numeric($tid)) {
            $taxonomy_term = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')->load($tid);
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            if($taxonomy_term && method_exists($taxonomy_term,'hasTranslation')&& $taxonomy_term->hasTranslation($language)){
                $taxonomy_term = $taxonomy_term->getTranslation($language);
            }

            if (is_object($taxonomy_term)) {
                if ($type == "full") {
                    return $taxonomy_term;
                } else {
                    return array('name' => $taxonomy_term->label(), 'tid' => $taxonomy_term->id());
                }
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function node_load_object_by_nid($nid)
    {
        $entity =  \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if($entity && method_exists($entity,'hasTranslation')&& $entity->hasTranslation($language)){
            $entity = $entity->getTranslation($language);
        }
        return $entity;
    }
    public function user_load_object_by_nid($uid)
    {
        $entity =  $this->entity_load_by_id('user',$uid);
        return $entity;
    }
    public function entity_load_by_id($entity_type,$id)
    {
        $entity =  \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if($entity && method_exists($entity,'hasTranslation')&& $entity->hasTranslation($language)){
            $entity = $entity->getTranslation($language);
        }
        return $entity;
    }


    public function taxonomy_url_alias($term_id)
    {
        return \Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/' . $term_id);
    }

    public function taxonomy_url($term_id)
    {
        return ('taxonomy/term/' . $term_id);
    }
    public function get_current_path_theme(){
        $theme = \Drupal::theme()->getActiveTheme();
        return $theme->getPath();
    }
    public function switch_language_url($url,$lang='en')
    {
        $lang_list = \Drupal::languageManager()->getLanguages();
        $code = null;
        $url_array = explode('/',$url);
        $status = true  ;
        foreach ($url_array as $key_lg => $lg){
            if(in_array($lg,array_keys($lang_list))){
                $url_array[$key_lg] = $lang ;
                $status =false ;
            }
        }
        if($status){
           return  '/'.$lang.'/'.$url;
        }
        return implode('/',$url_array) ;
    }
    public function get_route_name_by_url($path)
    {
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($path);
        if(is_object($url_object)){
        return $url_object->getRouteName();
        }else{return null ;}
    }
    public function get_route_name_by_url_current()
    {
        $url = $this->current_url();
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($url);
        if(is_object($url_object)){
            return $url_object->getRouteName();
        }else{return null ;}
    }
    public function generate_url_node_edit($nid)
    {
        $options = ['absolute' => TRUE];
        $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid ], $options);
        return $url->toString();
    }

    public function taxonomy_load_by_name($term_name, $vid = null)
    {
        $taxonomy_terms = taxonomy_term_load_multiple_by_name($term_name, $vid);
        $result = [];
        if (!empty($taxonomy_terms)) {
            foreach ($taxonomy_terms as $key => $taxonomy_term) {
                $result[] = array('name' => $taxonomy_term->label(), 'tid' => $taxonomy_term->id());
            }
        }
        if (count($result) == 1) {
            return array_shift($result);
        }
        return $result;
    }

    public function taxonomy_load_multi_by_vid($vid)
    {
        $connection = Database::getConnection();
        $res = $connection->select('taxonomy_term_data', 'n')
            ->fields('n', array('tid', 'vid'))
            ->condition('n.vid', $vid, '=')
            ->execute()
            ->fetchAllAssoc('tid');
        $items = [];
        foreach (array_keys($res) as $key => $tid) {
            //taxonomy_term
            $taxonomy_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            if($taxonomy_term && method_exists($taxonomy_term,'hasTranslation')&& $taxonomy_term->hasTranslation($language)){
                $taxonomy_term = $taxonomy_term->getTranslation($language);
            }
            if (is_object($taxonomy_term)) {
                $items[] = array(
                    'name' => strtolower($taxonomy_term->label()),
                    'tid' => $taxonomy_term->id(),
                    'url' => $this->taxonomy_url_alias($tid),
                    'object' => $taxonomy_term
                );
            }
        }

        return $items;
    }
    public function taxonomy_getallparent($tid)
    {
        $parent = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($tid);
        $terms =[];
        foreach ($parent as $key => $term){
                $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
                if($term && method_exists($term,'hasTranslation')&& $term->hasTranslation($language)){
                    $terms[$key] = $term->getTranslation($language);
                }
        }
        return $terms ;
    }
    public function taxonomy_getparent($tid)
    {
        $parent = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadParents($tid);
        if(!empty($parent)){
           $parent = reset($parent);
        }else{
            $parent = null ;
        }
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if($parent && method_exists($parent,'hasTranslation')&& $parent->hasTranslation($language)){
                $parent = $parent->getTranslation($language);
        }
        return $parent ;
    }
    public function taxonomy_getparent_tid($tid)
    {
        $parent = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadParents($tid);
        if(!empty($parent)){
            return array_keys($parent)[0];
        }else{
            return null ;
        }
    }

    public function taxonomy_first_level_by_vid($vid)
    {
        $terms = $this->taxonomy_load_multi_by_vid($vid);
        $first_level = [];
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $parent = $this->taxonomy_getparent($term['tid']);
                if (!$parent) {
                    $first_level[] = $term;
                }
            }
        }
        return $first_level;
    }

    public function current_lang()
    {
        return \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    public function get_list_content_type(){
        $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
        $options = [];
        foreach ($node_types as $node_type) {
            $options[$node_type->id()] = $node_type->label();
        }
        return $options;
    }
    public function current_url()
    {
        $url = Url::fromRoute('<current>');
        return $url->getInternalPath();
    }
    public function get_node_type_by_url($url){
        $nid =$this->get_numeric_args_url($url);
        $type =null;
        if($nid){
            $node_object = $this->node_load_object_by_nid($nid);
            if(is_object($node_object)){
                $type = $node_object->getType();
            }
        }
        return $type ;
    }
    public function get_numeric_args_url($url=null){
        if($url==null){
          $url = $this->current_url();
        }
        $url_array = explode("/", $url);
        $id = null;
        foreach ($url_array as $u) {
            if (is_numeric($u)) {
                $id = ($u);
            }
        }
        return $id ;
    }

    public function current_url_alias()
    {
        $url = Url::fromRoute('<current>');
        $url_alias = \Drupal::service('path.alias_manager')->getAliasByPath($url->toString());
        return $url_alias;
    }

    public function get_menu_tree($menu)
    {
        $tree = \Drupal::menuTree()->load($menu, new MenuTreeParameters());
        $menu = [];
        foreach ($tree as $item) {
            $menu["url"] = $item->link->getUrlObject()->toString();
            $menu["title"] = $item->link->getTitle();
            $menu["link"] = $item->link;
        }
        return $tree;
    }

    public function str_ends_with($haystack, $needle)
    {
        return strrpos($haystack, $needle) + strlen($needle) ===
            strlen($haystack);
    }

    function str_starts_with($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public function get_uri_image_by_fid($fid, $style = null)
    {
        $file = \Drupal\file\Entity\File::load($fid);
        if (is_object($file)) {
            if ($style != null) {
                return \Drupal\image\Entity\ImageStyle::load($style)->buildUrl($file->getFileUri());
            } else {
                return $file->getFileUri();
            }
        } else {
            return null;
        }
    }

    public function is_login()
    {
        $user = \Drupal::currentUser();
        return !$user->isAnonymous();
    }

    public function current_user()
    {
        $userCurrent = \Drupal::currentUser();
        if ($userCurrent->id() != 0) {
            return array(
                "name" => $userCurrent->getAccountName(),
                "uid" => $userCurrent->id(),
                "email" => $userCurrent->getEmail(),
                "user" => $userCurrent
            );
        } else {
            return array(
                "user" => $userCurrent
            );
        }


    }

    public function get_block_custom_type($block_content)
    {
        if (is_object($block_content)) {
            return $block_content->bundle();
        } else {
            return null;
        }
    }

    public function node_id_by_current_node_url()
    {
        $path = $this->current_url();
        if ($this->str_starts_with($path, "node/")) {
            $fs_refer = explode("node/", $path);
            return $fs_refer[1];
        } else {
            return null;
        }
    }

    public function taxonomy_id_by_current_url()
    {
        $path = $this->current_url();
        if ($this->str_starts_with($path, "taxonomy/term/")) {
            $fs_refer = explode("taxonomy/term/", $path);
            return $fs_refer[1];
        } else {
            return null;
        }
    }

    public function taxonomy_children_all($tid)
    {
        $results = [];
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($tid);

        if (!empty($children)) {
            $results = array_keys($children);

            foreach ($children as $key => $child) {
                $next = $this->taxonomy_children_all($key);
                if (!empty($next)) {
                    $results = array_merge($results, ($next));
                }
            }

        }

        return $results;

    }

    public function get_vocabulary_by_tid($tid)
    {
        $term = $this->taxonomy_load_by_tid($tid, "full");
        $vid = $term->get("vid")->getValue();
        return $vid[0]['target_id'];

    }

    public function get_vocabulary_by_taxonomy_url()
    {
        $tid = $this->taxonomy_id_by_current_url();
        return $this->get_vocabulary_by_tid($tid);
    }

    public function is_field_ready($entity, $field)
    {
        $bool = false;
        if (is_object($entity) && $entity->hasField($field)) {
            $field_value = $entity->get($field)->getValue();
            if (!empty($field_value)) {
                $bool = true;
            }
        }
        return $bool;
    }

    public function get_parameter($param = null)
    {
        $method = \Drupal::request()->getMethod();
        if ($param == null) {
            if ($method == "GET") {
                return \Drupal::request()->query->all();
            } elseif ($method == "POST") {
                return \Drupal::request()->request->all();
            } else {
                return null;
            }
        } else {
            if ($method == "GET") {
                return \Drupal::request()->query->get($param);
            } elseif ($method == "POST") {
                return \Drupal::request()->request->get($param);
            } else {
                return null;
            }
        }
    }

    function get_multi_query_request()
    {
        if ($_SERVER['QUERY_STRING']) {
            foreach (explode("&", $_SERVER['QUERY_STRING']) as $tmp_arr_param) {
                $tmp_arr_param_1 = explode("=", $tmp_arr_param);
                $filters[$tmp_arr_param_1[0]][] = $tmp_arr_param_1[1];
            }
            foreach ($filters as $key => $filter) {
                if (count($filter) == 1) {
                    $filters[$key] = array_shift($filter);
                }
            }
        } else {
            $filters = null;
        }

        return $filters;
    }

    //insert block custom content
    public function insert_content_block($type, $title, $fields = array())
    {

        // Grab a block entity manager from EntityManager service
        $blockEntityManager = \Drupal::service('entity.manager')
            ->getStorage('block_content');
        $block_type = $this->block_custom_load_by_type($type);
        if (!empty($block_type)) {

            // Tell block entity manager to create a block of type "ad_block"
            $block = $blockEntityManager->create(array(
                'type' => $type
            ));
            // Every block should have a description, but strangely it's property
            // is not 'description' but 'info'
            // in my case, I want it to be equal to my ad_group's term name.
            $block->info = $title;
            // This is optional part, my ad_block has a field field_ad_group
            // which is a taxonomy reference to the ad_group taxonomy,
            // that way I link ad_group and ad_block together.
            foreach ($fields as $key => $field) {
                $is_exist = $this->is_field_ready($block, $key);
                if ($is_exist) {
                    $block->{$key} = $field;
                } else {
                    drupal_set_message('Block type:' . $type . 'do not have Field:' . $key, 'warning');
                }
            }
            //$block->field_ad_group = $entity;
            // In the end, save our new block.
            $block->save();
        } else {
            drupal_set_message('Block type:' . $type . ' not exist', 'error');
        }
    }

    //get module path
    public function get_module_path($module_name)
    {
        $module_handler = \Drupal::service('module_handler');
        return $module_handler->getModule($module_name)->getPath();
    }

    public function is_url_admin()
    {
        $route = \Drupal::routeMatch()->getRouteObject();
        return \Drupal::service('router.admin_context')->isAdminRoute($route);
    }

    public function paragraph_load_object($key)
    {
        $paragraph = \Drupal::entityTypeManager()
            ->getStorage('paragraph')
            ->load($key);
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        if($paragraph && method_exists($paragraph,'hasTranslation')&& $paragraph->hasTranslation($language)){
            $paragraph = $paragraph->getTranslation($language);
        }
        return $paragraph;
    }

    /**
     * @param entity_type can be 'taxonomy_term' or 'node' or 'user'
     * entity_name is the name of your entity
     * @note : for get field user list $entity_type='user' and $entity_name = 'user'
     * @deprecated
     *   Use helper->get_entity_fields() instead in most cases.
     */
    public function get_fields_by_entity_info($entity_type, $entity_name = null)
    {
        $entity_type_list = array("taxonomy_term", "node", "user");
        if (in_array($entity_type, $entity_type_list)) {
            $fields_node_config = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $entity_name);
            $fields = [];
            foreach ($fields_node_config as $key => $field) {
                $fields[] = array(
                    "name" => $key,
                    "type" => $field->getType(),
                    "target_type" => (isset($field->getSettings()['target_type'])) ? $field->getSettings()['target_type'] : ""
                );
            }
            return $fields;
        } else {
            return null;
        }
    }
    public function get_current_nid_by_custom_pattern_url(){
        $url = $this->current_url();
        $url_array = explode("/",$url);
        $nid = null;
        foreach ($url_array as $u){
            if(is_numeric($u)){
                $nid = $u ;
            }
        }
        return $nid ;
    }
    public function get_fields_by_type($entity_type,$bundle,$type){
        $info = $this->get_entity_fields($entity_type,$bundle);
        $items = [];
        if(!empty($info)){
            foreach ($info as $key =>  $field){
                if($field['type']== $type){
                    $items[] = $field ;
                }
            }
        }
        return $items ;
    }
    public function get_entity_fields($entity_type, $entity_name = null)
    {
        $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
        $items =[];
        if (in_array($entity_type, array_keys($field_map))) {
            $entity_fields = $field_map[$entity_type] ;

            foreach ($entity_fields as $key => $field){
                if(in_array($entity_name, $field['bundles'])){
                    $items[] = [
                        'type'=>$field['type'],
                        'name'=> $key
                    ];
                }
            }

        }
        return $items;
    }

    public function get_type_field($entity, $field_name)
    {
        if (is_object($entity) && $entity->hasField($field_name)) {
            $field_type = $entity->get($field_name)->getFieldDefinition()->getType();
            return $field_type;
        } else {
            return null;
        }
    }

    public function get_setting_field($entity, $field_name)
    {
        $resulat = null;
        if (is_object($entity) && $entity->hasField($field_name)) {
            $setting_field = $entity->get($field_name)->getFieldDefinition()->getSettings(); //->getValue();
            if (isset($setting_field['target_type'])) {
                $resulat = $setting_field['target_type'];
            }
        }
        return $resulat;
    }

    public static function is_module_exist($module_name)
    {
        return \Drupal::moduleHandler()->moduleExists($module_name);
    }

    public function taxonomy_tree($vid)
    {
        return \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0);
    }

    public function taxonomy_children($vid, $tid)
    {
        $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0);
        $childre_term = [];
        foreach ($tree as $term) {
            if (in_array($tid, $term->parents)) {
                $childre_term[] = array(
                    'name' => strtolower($term->name),
                    'tid' => $term->tid,
                    'url' => $this->taxonomy_url_alias($term->tid)
                );

            }
        }
        return $childre_term;
    }

    public function render_block_by_title($title, $type = 'all')
    {
        $factory = \Drupal::entityTypeManager()->getStorage("block_content")->getQuery();
        $factory->condition('info', $title);
        if ($type != 'all') {
            $factory->condition('type', $type);
        }
        $resultat = $factory->execute();
        $items = [];
        if (!empty($resultat)) {
            foreach ($resultat as $block_id)
                $block = \Drupal::entityTypeManager()->getStorage('block_content')->load($block_id);
            $items[] = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block);
        }
        return $items;
    }

    public function get_block_by_title($title, $type = 'all')
    {
        $filter['info'] = $title ;
        if ($type != 'all') {
            $filter['type'] = $type ;
        }
        return  \Drupal::entityTypeManager()
            ->getStorage('block_content')
            ->loadByProperties($filter);
    }

    public function block_custom_load_by_type($type)
    {
        $query = \Drupal::entityTypeManager()->getStorage("block_content")->getQuery();
        $query->condition("type", $type);
        $resultat = $query->execute();
        $blocks = [];
        if (!empty($resultat)) {
            foreach ($resultat as $block_id) {
                $blocks[] = \Drupal::entityTypeManager()->getStorage("block_content")->load($block_id);
            }
        }
        return $blocks;
    }


    public function node_url($node_or_nid)
    {
        if (is_numeric($node_or_nid)) {
            $nid = $node_or_nid;
            $options = ['absolute' => TRUE];
            $url_object = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options)->toString();
        } else if (is_object($node_or_nid)) {
            $url_path = explode("/", $node_or_nid->toUrl()->getInternalPath());
            $nid = $url_path[sizeof($url_path) - 1];
            $options = ['absolute' => TRUE];
            $url_object = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options)->toString();
        } else {
            $url_object = null;
        }
        return $url_object;
    }

    public function entity_object_load($nid, $entity = 'node')
    {
        if (is_numeric($nid)) {
            return \Drupal::entityTypeManager()->getStorage($entity)->load($nid);
        }
        {
            return null;
        }
    }

    public function get_list_paragraphs_by_type($type){
        return \Drupal::entityTypeManager()
            ->getStorage('paragraph')
            ->loadByProperties(array('type' => $type));
    }

    public function redirectTo($url,$lang=null){
        global $base_url;
        if($lang==null){
        $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
        }
        //if domain exit
        $url = str_replace($base_url,"",$url);

        //if lang exist
        $lang_list = \Drupal::languageManager()->getLanguages();
        $url_array = explode('/',$url);
        foreach ($url_array as $key_lg => $lg){
            if(in_array($lg,array_keys($lang_list))){
                unset($url_array[$key_lg]);
            }
        }
        $url = implode('/',$url_array);
        $path = $base_url .'/'.$lang.'/'.$url ;
        $response = new RedirectResponse($path, 302);
        $response->send();
        return;
    }



}