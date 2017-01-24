<?php

namespace adapt\language{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class bundle_language extends \adapt\bundle{
        
        protected $_strings;
        
        public function __construct($data){
            parent::__construct('language', $data);
            $this->_strings = [];
            
            $this->register_config_handler('language', 'language_strings', 'process_language_strings_tag');
        }
        
        public function boot(){
            if (parent::boot()){
                
                /**
                 * Extend \adapt\base and add a get_string method
                 */
                \adapt\base::extend(
                    'get_string',
                    function($_this, $string_key){
                        if ($_this->language instanceof \adapt\model && $_this->language->table_name == 'language'){
                            return $_this->language->get_string($string_key);
                        }
                        
                        return $string_key;
                    }
                );
                
                /**
                 * Extend \adapt\base and add a language property
                 */
                \adapt\base::extend(
                    'pget_language',
                    function($_this){
                        $language = $_this->store('language.model');
                        if (!$language instanceof \adapt\model || $language->table_name != 'language'){
                            $language = new model_language();
                        }
                        
                        //if ($_this->session->is_logged_in){
                        //    if ($language->language_id != $_this->session->user->contact->language_id){
                        //        $language->load($_this->session->user->contact->language_id);
                        //    }
                        //}else{+
                            if ($_this->setting('language.default')){
                                $language->load_by_name($_this->setting('language.default'));
                            }
                        //}
                        $_this->store('language.model', $language);
                        
                        return $language;
                    }
                );
                
                /**
                 * Attach an event to the dom render and set the 
                 * language on the dom.
                 */
                $this->dom->listen(
                    \adapt\page::EVENT_RENDER, 
                    function($event){
                        if ($event['object'] instanceof \adapt\html && $event['object']->tag == 'html'){
                            $event['object']->attr('lang', $event['object']->language->short_code);
                        }
                    }
                );
                
                return true;
            }
            
            return false;
        }

        public function install(){
            if (parent::install()) {

                return true;
            }
            return false;
        }
        
        public function process_language_strings_tag($bundle, $tag_data){
            if ($bundle instanceof \adapt\bundle && $tag_data instanceof \adapt\xml){
                $this->register_install_handler($this->name, $bundle->name, 'install_strings');
                
                $string_nodes = $tag_data->get();
                $this->_strings[$bundle->name] = [];
                
                foreach($string_nodes as $string_node){
                    if ($string_node instanceof \adapt\xml && $string_node->tag == 'string'){
                        if (!is_array($this->_strings[$bundle->name][$string_node->attr('language')])){
                            $this->_strings[$bundle->name][$string_node->attr('language')] = [];
                        }
                        $this->_strings[$bundle->name][$string_node->attr('language')][] = [
                            'language' => $string_node->attr('language'),
                            'key' => $string_node->attr('key'),
                            'value' => $string_node->attr('value')
                        ];
                    }
                }
            }
        }
        
        public function install_strings($bundle){
            $bundle_strings = $this->_strings[$bundle->name];
            
            if (is_array($bundle_strings)){
                foreach($bundle_strings as $language => $strings){
                    $model_language = new model_language();
                    if ($model_language->load_by_name($language)){
                        foreach($strings as $string){
                            $model_language->register_string($string['key'], $string['value']);
                        }
                    }
                }
            }
        }
        
    }
    
    
}

?>