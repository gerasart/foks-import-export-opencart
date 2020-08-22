<?php
    
    class ModelToolFoks extends Model {
    
        private $log_folder = 'view/javascript/app/logs/';
    
        
        /**
         * @param $categories
         */
        public function addCategories( $categories ) {
            if  (!empty($categories) &&  count($categories)) {
                foreach ( $categories as $category ) {
                    $is_cat = $this->isCategory( $category['name'] );
                    if ( !$is_cat ) {
                        $this->addCategoryImport( $category );
                    }
                }
            }
        }
    
        /**
         * @param $data
         * @return mixed
         */
        public function addCategoryImport( $data) {
            $languages = $this->getLanguages();
            $sql_q = "INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', ";
            $sql_q .= "`top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', ";
            $sql_q .= "`column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', ";
            $sql_q .= "status = '" . (int)$data['status'] . "', ";
            $sql_q .= "date_modified = NOW(), date_added = NOW()";
            $this->db->query($sql_q);
        
            $category_id = $this->db->getLastId();
        
            if (isset($data['image'])) {
                $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
            }
        
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)0 . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)0 . "', layout_id = '" . (int)0 . "'");
        
            foreach ($languages as $language_id) {
                $sql_c = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', ";
                $sql_c .= "language_id = '" . (int)$language_id . "', name = '" . $this->db->escape( $data['name'] ) . "', meta_title = '" . $this->db->escape( $data['name'] ) . "', ";
                $sql_c .= "meta_description = '', meta_keyword = ''";
                $this->db->query( $sql_c );
            }
        
            // MySQL Hierarchical Data Closure Table Pattern
            $level = 0;
        
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");
        
            foreach ($query->rows as $result) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
            
                $level++;
            }
        
            $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

//        $this->cache->delete('category');
//
//        if($this->config->get('config_seo_pro')){
//            $this->cache->delete('seopro');
//        }
        
            return $category_id;
        }
    
        /**
         * @param $products
         */
        public function addProducts( $products ) {
            $i = 0;
            foreach ( $products as $product ) {
                $is_product = $this->getProductByUniqueId( $product['model'] );
                if ( $is_product ) {
                    $this->UpdateProductImport( $is_product, $product );
                } else {
                    $this->addProductImport( $product );
                }
                file_put_contents( DIR_APPLICATION .$this->log_folder. 'current.json', 0 );
                $i++;
            }
            
        }
    
        /**
         * @param $data
         * @return mixed
         */
        public function addManufacturerImport( $data ) {
            $prefix = DB_PREFIX;
            $res_data = [
              'name'  => $this->db->escape( $data['name'] ),
            ];
            
            $mysql_request = "INSERT INTO {$prefix}manufacturer SET name = '{$res_data['name']}'";
            
            $this->db->query($mysql_request);
            
            $manufacturer_id = $this->db->getLastId();
            
//            $this->db->query( "INSERT INTO " . DB_PREFIX . "manufacturer_to_layout SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)0 . "', layout_id = '" . (int)0 . "'" );
//
//            if ( isset( $data['image'] ) ) {
//                $this->db->query( "UPDATE " . DB_PREFIX . "manufacturer SET image = '" . $this->db->escape( $data['image'] ) . "' WHERE manufacturer_id = '" . (int)$manufacturer_id . "'" );
//            }
            
//            $this->db->query( "INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)0 . "'" );
            
            $this->cache->delete( 'manufacturer' );
            
            return $manufacturer_id;
        }
    
        /**
         * @param $data
         * @return mixed
         */
        public function addProductImport( $data ) {
            $languages = $this->getLanguages();
            
            $sql_q     = "INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape( $data['model'] ) . "', ";
            $sql_q     .= "sku = '" . $this->db->escape( $data['sku'] ) . "', ";
            $sql_q     .= "quantity = '" . (int)$data['quantity'] . "', ";
            $sql_q     .= "minimum = 1, subtract = 1, stock_status_id = 7, ";
            $sql_q     .= "date_available = '" . $this->db->escape( $data['date_available'] ) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', ";
            $sql_q     .= "shipping = 1, price = '" . (float)$data['price'] . "', ";
            $sql_q     .= "status = 1,";
            $sql_q     .= "sort_order = 1, date_added = NOW(), date_modified = NOW()";
        
            $this->db->query( $sql_q );
        
            $product_id = $this->db->getLastId();

            if ( isset( $data['image'] ) && !empty($data['image']) ) {
                $thumb = $this->imgUrlUpload($data['image'], (int)$product_id);
                
                $this->db->query( "UPDATE " . DB_PREFIX . "product SET image = '" . $thumb . "' WHERE product_id = '" . (int)$product_id . "'" );
            }
        
            if ( isset($data['description']) && !empty($data['description']) ) {
                foreach ( $languages as $lang ) {
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$lang . "', name = '" . $this->db->escape( $data['name'] ) . "', description = '" . $this->db->escape( $data['description'] ) . "',  meta_title = '', meta_description = '" . $this->db->escape( $data['description'] ) . "', meta_keyword = ''" );
                }
            }
        
            if ( isset( $data['attributes'] ) && !empty($data['attributes']) ) {
            
                foreach ( $data['attributes'] as $attr_id => $attr_id_val ) {
                    // Removes duplicates
                    foreach ( $languages as $lang ) {
                        $this->db->query( "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attr_id . "' AND language_id = '" . (int)$lang . "'" );
                    }
    
                    foreach ( $languages as $lang ) {
                        $this->db->query( "INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attr_id . "', language_id = '" . (int)$lang . "', text = '" . $this->db->escape( $attr_id_val ) . "'" );
    
                    }
                }
            
            }
        
            if ( !empty( $data['images'] ) ) {
                foreach ( $data['images'] as $product_image ) {
                    $img = $this->imgUrlUpload($product_image, (int)$product_id);
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $img . "', sort_order = '" . (int)0 . "'" );
                }
            }
        
            if ( !empty( $data['category_id'] ) ) {
                $this->db->query( "INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['category_id'] . "'" );
            }
        
            $this->db->query( "INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)0 . "'" );
        
            $this->cache->delete( 'product' );
        
            if ( $this->config->get( 'config_seo_pro' ) ) {
                $this->cache->delete( 'seopro' );
            }
        
            return (int)$product_id;
        }
    
        /**
         * @param $product_id
         * @param $data
         */
        public function UpdateProductImport( $product_id, $data ) {
            $product_id = $product_id['row'];
            
            $languages = $this->getLanguages();
            
            $sql_q = "UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape( $data['model'] ) . "', ";
            $sql_q .= "sku = '" . $this->db->escape( $data['sku'] ) . "', ";
            $sql_q .= "quantity = '" . (int)$data['quantity'] . "', ";
            $sql_q .= "minimum = 1, subtract = 1, stock_status_id = 7, ";
            $sql_q .= "date_available = '" . $this->db->escape( $data['date_available'] ) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', ";
            $sql_q .= "shipping = 1, price = '" . (float)$data['price'] . "', ";
            $sql_q .= "status = 1, ";
            $sql_q .= "sort_order = 1, date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'";
            
            $this->db->query( $sql_q );
            
            if ( isset( $data['image'] ) && !empty($data['image']) ) {
                $thumb = $this->imgUrlUpload($data['image'], (int)$product_id);
    
                $this->db->query( "UPDATE " . DB_PREFIX . "product SET image = '" . $thumb . "' WHERE product_id = '" . (int)$product_id . "'" );
            }
            
            $this->db->query( "DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'" );
            $this->db->query( "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'" );
            
            if ( isset( $data['attributes'] ) && !empty($data['attributes']) ) {
                
                foreach ( $data['attributes'] as $attr_id => $attr_id_val ) {
                    // Removes duplicates
                    foreach ( $languages as $lang ) {
                        $this->db->query( "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attr_id . "' AND language_id = '" . (int)$lang . "'" );
                    }
                    foreach ( $languages as $lang ) {
                        $this->db->query( "INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attr_id . "', language_id = '" . (int)$lang . "', text = '" . $this->db->escape( $attr_id_val ) . "'" );
                    }
                }
                
            }
            
            if ( !empty( $data['images'] ) ) {
                foreach ( $data['images'] as $product_image ) {
                    $img = $this->imgUrlUpload($product_image, (int)$product_id);
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $img. "', sort_order = '" . (int)1 . "'" );
                }
            }
    
            if ( isset($data['description']) && !empty($data['description']) ) {
                foreach ( $languages as $lang ) {
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$lang . "', name = '" . $this->db->escape( $data['name'] ) . "', description = '" . $this->db->escape( $data['description'] ) . "',  meta_title = '" . $this->db->escape( $data['name'] ) . "', meta_description = '" . $this->db->escape( $data['description'] ) . "', meta_keyword = ''" );
                }
            }
            
            $this->db->query( "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'" );
            
            if ( isset( $data['category'] ) ) {
                $this->db->query( "INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['category_id'] . "'" );
//                $this->db->query( "INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['category_id'] . "', main_category = 1" );
            }
            
            $this->cache->delete( 'product' );
            
            if ( $this->config->get( 'config_seo_pro' ) ) {
                $this->cache->delete( 'seopro' );
            }
        }
    
    
        /**
         * @param $str
         * @return string|string[]
         */
        public static function transliterate( $str ) {
            // переводим в транслит
            $str = self::rus2translit( $str );
            // в нижний регистр
            $str = strtolower( $str );
            // заменям все ненужное нам на "-"
            $str = preg_replace( '~[^-a-z0-9_]+~u', '-', $str );
            // удаляем начальные и конечные '-'
            $str    = trim( $str, "-" );
            $result = str_replace( '---', '-', $str );
            
            return $result;
        }
    
        /**
         * @param $string
         * @return string
         */
        public static function rus2translit( $string ) {
            $converter = array(
                'а' => 'a', 'б' => 'b', 'в' => 'v',
                'г' => 'g', 'д' => 'd', 'е' => 'e',
                'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
                'и' => 'i', 'й' => 'y', 'к' => 'k',
                'л' => 'l', 'м' => 'm', 'н' => 'n',
                'о' => 'o', 'п' => 'p', 'р' => 'r',
                'с' => 's', 'т' => 't', 'у' => 'u',
                'ф' => 'f', 'х' => 'h', 'ц' => 'c',
                'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
                'ь' => '\'', 'ы' => 'y', 'ъ' => '\'',
                'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
                
                'А' => 'A', 'Б' => 'B', 'В' => 'V',
                'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
                'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
                'И' => 'I', 'Й' => 'Y', 'К' => 'K',
                'Л' => 'L', 'М' => 'M', 'Н' => 'N',
                'О' => 'O', 'П' => 'P', 'Р' => 'R',
                'С' => 'S', 'Т' => 'T', 'У' => 'U',
                'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
                'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
                'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
                'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            );
            return strtr( $string, $converter );
        }
    
        /**
         * @return array
         */
        public function getLanguages() {
            $language_data = array();
            $query         = $this->db->query( "SELECT * FROM " . DB_PREFIX . "language ORDER BY sort_order, name" );
            foreach ( $query->rows as $result ) {
                $language_data[] = $result['language_id'];
            }
            return $language_data;
        }
    
        /**
         * @param $key
         * @return mixed
         */
        public function getSetting( $key ) {
            $query        = $this->db->query( "SELECT value FROM " . DB_PREFIX . "setting WHERE  `code` = 'foks' AND `key` = '{$key}' LIMIT 1" );
            $setting_data = $query->row;
            return $setting_data['value'];
        }
    
        /**
         * @param $unique_id
         * @return mixed
         */
        public function getProductByUniqueId( $unique_id ) {
            $sql = "SELECT product_id as row FROM " . DB_PREFIX . "product ";
            $sql .= "WHERE model ='{$unique_id}' LIMIT 1";
        
            $query = $this->db->query( $sql );
        
            return $query->row;
        }
    
        /**
         * @param $key
         * @param $value
         * @param int $store_id
         */
        public function editSetting( $key, $value, $store_id = 0 ) {
            $check = $this->db->query( "SELECT * FROM " . DB_PREFIX . "setting WHERE  `code` = 'foks' AND `key` = '{$key}' LIMIT 1" );
            if ( !$check->row ) {
                if ( $value ) {
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape( 'foks' ) . "', `key` = '" . $this->db->escape( $key ) . "', `value` = '" . $this->db->escape( serialize( $value ) ) . "'" );
                } else {
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape( 'foks' ) . "', `key` = '" . $this->db->escape( $key ) . "', `value` = '" . $this->db->escape( serialize( $value ) ) . "'" );
                }
            } else {
                $this->db->query( "UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape( ($value) ) . "', serialized = '1' WHERE `code` = 'foks' AND `key` = '" . $this->db->escape( $key ) . "' AND store_id = '" . (int)$store_id . "'" );
            }
        }
    
        /**
         * @param $name
         * @return mixed
         */
        public function isCategory( $name ) {
            $query = $this->db->query( "SELECT DISTINCT category_id  FROM " . DB_PREFIX . "category_description WHERE name = '" . $name . "'" );
            return $query->row;
        }
    
        /**
         * @param $manufacturer_name
         * @return mixed
         */
        public function isManufacturer( $manufacturer_name ) {
            $query = $this->db->query( "SELECT DISTINCT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $manufacturer_name . "'" );
        
            return $query->row;
        }
        
        public function isAttrGroup( $attr_name ) {
            $query = $this->db->query( "SELECT DISTINCT attribute_group_id FROM " . DB_PREFIX . "attribute_group_description WHERE name = '" . $attr_name . "'" );
        
            return $query->row;
        }
        
        public function isAttribute($data) {
            $query = $this->db->query( "SELECT DISTINCT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '" . $data['name'] . "'" );
    
            return $query->row;
        }
        
        protected function imgUrlUpload($image_url, $product_id, $result = true)
        {
            $folder = DIR_IMAGE . 'catalog/image_url/product' . $product_id;
        
            if ($result) {
                $this->removeImageFolder($folder);
                mkdir($folder, 0750);
            }
        
            $array_url = explode('.', $image_url);
            $format = array_pop($array_url);
            $valid = ['png', 'jpg', 'jpeg'];
            if (!in_array($format,$valid)) {
                $format = 'jpg';
            }
            $img_path = 'catalog/image_url/product' . $product_id . '/image-url-' . $product_id . '.' . $format;
            $path = DIR_IMAGE . $img_path;
            if ($result) {
                $file = file_get_contents($image_url);
                file_put_contents($path, $file);
            }
        
            if (!$image_url) {
                $this->removeImageFolder($folder);
            }
//            var_dump(str_replace(DIR_IMAGE, '/image/', $path));
//            var_dump(DIR_IMAGE);
//            return str_replace(DIR_IMAGE, '/image/', $path);
            return $img_path;
        }
    
        protected function removeImageFolder($dir)
        {
            foreach (glob($dir) as $file) {
                if (is_dir($file)) {
                    $this->removeImageFolder("$file/*");
                    rmdir($file);
                } else {
                    unlink($file);
                }
            }
        }
        
        public function addAttribute($attribute, $attr_group_id)  {
            $this->db->query( "INSERT INTO " . DB_PREFIX . "attribute SET attribute_group_id = '" . (int)$attr_group_id . "'" );
            $attribute_id = $this->db->getLastId();
            $languages = $this->getLanguages();
            foreach ( $languages as $language_id ) {
                $this->db->query( "INSERT INTO " . DB_PREFIX . "attribute_description SET attribute_id = '" . (int)$attribute_id . "', language_id = '" .(int)$language_id . "', name = '".$attribute['name']."'" );
            }
            
            return $this->getAttribute($attribute_id);
        }
        
        public function getAttribute($attr_id) {
            $query = $this->db->query( "SELECT DISTINCT * FROM " . DB_PREFIX . "attribute_description WHERE attribute_id = '" . (int)$attr_id . "'" );
    
            return $query->row;
        }
        
        public function addAttributes($attributes) {
            $attr_group_id = $this->createGroupAttribute();
            $attrs  = [];
            
            if ($attributes) {
                foreach ( $attributes as $attribute ) {
                    $attr_id = $this->isAttribute( $attribute );
                    if ( isset($attr_id['attribute_id']) && !empty($attr_id['attribute_id']) ) {
                        $attr                           = $this->getAttribute( $attr_id['attribute_id'] );
                        $attrs[ $attr['attribute_id'] ] = $attribute['value'];
                    } else {
                        $attr                           = $this->addAttribute( $attribute, $attr_group_id );
                        $attrs[ $attr['attribute_id'] ] = $attribute['value'];
            
                    }
                }
            }
            
            return $attrs;
            
        }
        
        public function createGroupAttribute() {
            $languages = $this->getLanguages();
            $is_attr = $this->isAttrGroup('Foks');
            
            if (!$is_attr) {
                $this->db->query( "INSERT INTO " . DB_PREFIX . "attribute_group SET sort_order = '1'" );
    
                $attribute_group_id = $this->db->getLastId();
    
                foreach ( $languages as $language_id ) {
                    $this->db->query( "INSERT INTO " . DB_PREFIX . "attribute_group_description SET attribute_group_id = '" . (int)$attribute_group_id . "', language_id = '" . (int)$language_id . "', name = 'Foks'" );
                }
            } else {
                $attribute_group_id = $is_attr;
            }
            
            
            return $attribute_group_id;
    
        }
        
    }