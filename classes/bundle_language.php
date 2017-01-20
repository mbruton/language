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
                 * Extend \adapt\base and add a get_text method
                 */
                \adapt\base::extend(
                    'get_text',
                    function($_this, $string_key){
                        return "gtk:{$string_key}";
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
            print_r($this->_strings);
            exit(1);
        }
        
    }
    
    
}

?>