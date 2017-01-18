<?php

namespace adapt\language{
    
    /* Prevent Direct Access */
    defined('ADAPT_STARTED') or die;
    
    class bundle_language extends \adapt\bundle{
        
        public function __construct($data){
            parent::__construct('language', $data);
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
        
    }
    
    
}

?>