<?php

namespace adapt\language{
    
    class model_language extends \adapt\model {
        
        public function __construct($id = null, $data_source = null) {
            parent::__construct('language', $id, $data_source);
        }
        
        public function mget_short_code(){
            if (strlen($this->language_code) >= 2){
                return strtolower($this->language_code);
            }
        }
        
        public function register_string($key, $value){
            if (!$this->is_loaded){
                $this->error('Language not loaded');
                return false;
            }
            
            $cache_key = "adapt/language/" . $this->name;
            $strings = $this->cache->get($cache_key);
            if (!is_array($strings)) $strings = [];
            $hash_key = md5($key);
            
            if (in_array($hash_key, array_keys($strings))){
                $strings[$hash_key] = $value;
            }else{
                // Does the key exist?
                $model_key = new model_language_key();
                if ($model_key->load_by_name($key)){
                    // Does the value exist?
                    $sql = $this->data_source->sql;
                    
                    $sql->select('v.value')
                        ->from('language_key', 'k')
                        ->join(
                            'language_value', 
                            'v',
                            new sql_and(
                                new sql_cond('k.language_id', sql::EQUALS, 'v.language_key_id'),
                                new sql_cond('v.language_id', sql::EQUALS, $this->language_id),
                                new sql_cond('v.date_delete', sql::IS, new sql_null())
                            )
                        )
                        ->where(
                            new sql_and(
                                new sql_cond('k.name', sql::EQUALS, q($key)),
                                new sql_cond('k.date_deleted', sql::IS, new sql_null())
                            )
                        );

                    $results = $sql->execute()->results();

                    if (count($results) == 1){
                        $value = $results[0]['value'];
                        $strings[$hash_key] = $value;
                    }elseif(count($results) == 0){
                        $model_value = new model_language_value();
                        
                        $model_value->language_key_id = $model_key->language_key_id;
                        $model_value->language_id = $this->language_id;
                        $model_value->value = $value;

                        if (!$model_value->save()){
                            $this->error("Unable to set key value");
                            return false;
                        }

                        // Update the strings array
                        $strings[$hash_key] = $value;
                    }
                }else{
                    // Clear the load error
                    $model_key->errors(true);
                    
                    // New key
                    $model_key->name = $key;
                    
                    if (!$model_key->save()){
                        $model_key->errors(true);
                        $this->error('Unable to create language key ' . $key);
                        return false;
                    }
                    
                    $model_value = new model_language_value();
                    $model_value->language_key_id = $model_key->language_key_id;
                    $model_value->language_id = $this->language_id;
                    $model_value->value = $value;
                    
                    if (!$model_value->save()){
                        $this->error("Unable to set key value");
                        return false;
                    }
                    
                    // Update the strings array
                    $strings[$hash_key] = $value;
                }
            }
            
            // Re-cache
            $this->cache->serialize($cache_key, $strings, 60 * 60 * 24 * 365);
            return true;
        }
        
        public function get_string($key){
            if (!$this->is_loaded){
                return $key;
            }
            
            $cache_key = "adapt/language/" . $this->name;
            $strings = $this->cache->get($cache_key);
            if (!is_array($strings)) $strings = [];
            $hash_key = md5($key);
            
            if (in_array($hash_key, array_keys($strings))){
                return $strings[$key];
            }else{
                // Check the database then add to cache
                $sql = $this->data_source->sql;
                $sql->select('v.value')
                    ->from('language_key', 'k')
                    ->join(
                        'language_value', 
                        'v',
                        new sql_and(
                            new sql_cond('k.language_id', sql::EQUALS, 'v.language_key_id'),
                            new sql_cond('v.language_id', sql::EQUALS, $this->language_id),
                            new sql_cond('v.date_delete', sql::IS, new sql_null())
                        )
                    )
                    ->where(
                        new sql_and(
                            new sql_cond('k.name', sql::EQUALS, q($key)),
                            new sql_cond('k.date_deleted', sql::IS, new sql_null())
                        )
                    );
                //print "<pre>{$sql}</pre>";
                $results = $sql->execute()->results();
                
                if (count($results) == 1){
                    $value = $results[0]['value'];
                    $strings[$hash_key] = $value;
                    // Re-cache the strings
                    $this->cache->serialize($cache_key, $strings, 60 * 60 * 24 * 365);
                    
                    if ($this->setting('language.highlight') == 'Yes'){
                        $value = new html_span($value, ['class' => 'language-string translated ' . $this->name]);
                    }
                    
                    return $value;
                }
            }
            
            // Incase all else fails
            return $key;
        }
    }

}