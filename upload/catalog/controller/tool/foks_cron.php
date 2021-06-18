<?php

    class ControllerToolFoksCron extends Controller {
        private $dir;
        private static $total;
        private static $categoreis = [];
        private static $status = 'waiting...';
        private const LOG_FOLDER = 'view/javascript/app/logs/';

        /**
         * @throws Exception
         */
        public function index() {
            $this->dir = str_replace( 'catalog', 'admin', DIR_APPLICATION );
            echo 'starting..';

            try {
                $this->ImportFoksCron();
            } catch (\Exception $e) {
                self::$status = $e->getMessage();
            }

            echo "<br>";
            echo 'Total: ' . self::$total;
            echo "<br>";
            echo 'Status: ' . self::$status;
        }
    
        /**
         * @param $file
         *
         * @return array|\SimpleXMLElement
         */
        public function parseFile($file)
        {
            $xmlstr = file_get_contents($file);
            $xml    = new \SimpleXMLElement($xmlstr);
        
            return [
                'categories' => self::parseCategories($xml->shop->categories),
                'products'   => $this->parseProducts($xml->shop->offers),
            ];
        }
    
        public function parseFileCategories($file)
        {
            $xmlstr = file_get_contents($file);
            $xml    = new \SimpleXMLElement($xmlstr);
        
            return self::parseCategories($xml->shop->categories);
        }
    
        public function parseFileProducts($file)
        {
            $xmlstr = file_get_contents($file);
            $xml    = new \SimpleXMLElement($xmlstr);
        
            return $this->parseProducts($xml->shop->offers);
        }
    
        /**
         * @param $categories
         *
         * @return array
         */
        public static function parseCategories($categories)
        {
            $categoriesList   = [];
            $data             = $categories->category;
            self::$categoreis = [];
        
            foreach ($data as $category) {
                $categoryName     = (string)$category;
                $categoriesList[] = [
                    'parent_id'   => (int)$category['parentId'],
                    'name'        => trim(htmlspecialchars($categoryName, ENT_QUOTES)),
                    'id'          => (string)$category['id'],
                    'parent_name' => '',
                    'store_id'    => 0,
                    'column'      => 0,
                    'status'      => 1,
                    'noindex'     => 0,
                    'sort_order'  => 1,
                ];
            }
        
            $categories_result = [];
        
            foreach ($categoriesList as $item) {
                $item['parent_name'] = self::getParentCatName($categoriesList, $item['parent_id']);
                $categories_result[] = $item;
                self::$categoreis[]  = $item;
            }
        
            return $categories_result;
        }
    
        /**
         * @param $categoriesList
         * @param $parent_id
         * @param bool $id
         *
         * @return string
         */
        public static function getParentCatName($categoriesList, $parent_id, $id = false)
        {
            $cat_name = '';
        
            foreach ($categoriesList as $cat) {
                if ((int)$cat['id'] === $parent_id) {
                    $cat_name = $cat['name'];
                    break;
                }
            
                if ($id && (int)$cat['id'] === $id) {
                    $cat_name = $cat['name'];
                }
            }
        
            return $cat_name;
        }
    
        /**
         * Convert from xml to array
         *
         * @param $offers
         *
         * @return array
         */
        public function parseProducts($offers)
        {
            $this->load->model('tool/foks_cron');
            $count  = count($offers->offer);
            $result = [];
        
            for ($i = 0; $i < $count; $i++) {
                $offer = $offers->offer[$i];
            
                $product_images = [];
                $attributes     = [];
                $thumb_product  = '';
                $isMainImageSet = false;
            
            
                foreach ($offer->picture as $image) {
                    if ( ! $isMainImageSet) {
                        $thumb_product  = $image;
                        $isMainImageSet = true;
                    } else {
                        $product_images[] = $image;
                    }
                }
            
                $productName = (string)$offer->name;
            
                if ( ! $productName) {
                    if (isset($offer->typePrefix)) {
                        $productName = $offer->typePrefix.' '.$offer->model;
                    } else {
                        $productName = (string)$offer->model;
                    }
                }
            
                if (isset($offer->param) && ! empty($offer->param)) {
                    $params = $offer->param;
                
                    foreach ($params as $param) {
                    
                        if ($param && isset($param['name'])) {
                            $attr_name  = (string)$param['name'];
                            $attr_value = (string)$param;
                        
                            $attributes[] = [
                                'name'  => htmlspecialchars($attr_name, ENT_QUOTES),
                                'value' => htmlspecialchars($attr_value, ENT_QUOTES),
                            ];
                        }
                    }
                }
            
                $categoryName        = isset($offer->category) ? (string)$offer->category : '';
                $vendor              = isset($offer->vendor) ? (string)$offer->vendor : '';
                $id_category         = isset($offer->categoryId) ? (string)$offer->categoryId : 0;
                $product_description = isset($offer->description) ? (string)$offer->description : '';
                $category_name       = isset($categoryName) ? htmlspecialchars($categoryName, ENT_QUOTES) : '';
                $manufacturer        = isset($vendor) ? htmlspecialchars($vendor, ENT_QUOTES) : '';
                $price_old           = isset($offer->price_old) ? (float)$offer->price_old : '';
            
                if (empty($category_name)) {
                    $category_name = self::searchCatName($id_category);
                }
            
                $data = [
                    'name'            => htmlspecialchars($productName),
                    'price'           => isset($offer->price) ? (float)$offer->price : '',
                    'price_old'       => $price_old,
                    'quantity'        => (isset($offer->stock_quantity)) ? (int)$offer->stock_quantity : 0,
                    'model'           => (string)$offer['id'],
                    'sku'             => isset($offer->vendorCode) && ! empty($offer->vendorCode) ? (string)$offer->vendorCode : (string)$offer['id'],
                    'category'        => $category_name,
                    'category_id'     => $this->getCategoryId($category_name),
                    'parent_category' => '',
                    'description'     => ! empty($product_description) ? html_entity_decode($product_description) : '',
                    'image'           => $thumb_product,
                    'images'          => $product_images,
                    'date_available'  => date('Y-m-d'),
                    'manufacturer_id' => $this->getManufacturerId($manufacturer),
                    'manufacturer'    => $manufacturer,
                    'status'          => 1,
                    'attributes'      => $this->model_tool_foks_cron->addAttributes($attributes),
                ];
            
                $result[$i] = $data;
            }
        
            return $result;
        }
        
        /**
         * Helper for import data.
         *
         * @param $file
         * @return array|\SimpleXMLElement
         * @throws \Exception
         */
        public function importData( $file ) {
            $this->load->model( 'tool/foks_cron' );
            $data          = $this->parseFile( $file );
            $total_product = count( $data['products'] );
            self::$total   = $total_product;

            $this->model_tool_foks_cron->addCategories( $data['categories'] );
            $this->model_tool_foks_cron->addProducts( $data['products'] );

            self::$status = 'done';

            return $data;
        }

        /**
         * Import data products.
         *
         * @throws Exception
         */
        public function ImportFoksCron() {

            $this->load->model( 'tool/foks_cron' );

            $file_x = $this->model_tool_foks_cron->getSetting( 'foks_import_url' );
            $file   = str_replace( "&amp;", '&', $file_x );

            if ( $file ) {
                $xml = file_get_contents( $file );
                file_put_contents( $this->dir . '/' . self::LOG_FOLDER . 'foks_import.xml', $xml );
                $file_path = $this->dir . self::LOG_FOLDER . 'foks_import.xml';
                $this->importData( $file_path );
            }
        }

        /**
         * Get manufacturer id
         *
         * @param $name
         *
         * @return false|mixed
         */
        public function getManufacturerId($name)
        {
            if ( ! empty($name)) {
                $this->load->model('tool/foks_cron');
                $data         = [
                    'name'                     => $name,
                    'sort_order'               => 1,
                    'noindex'                  => 1,
                    'manufacturer_description' => '',
                ];
                $manufacturer = $this->model_tool_foks_cron->isManufacturer($data['name']);

                return $manufacturer['manufacturer_id'] ?? $this->model_tool_foks_cron->addManufacturerImport($data);
            }

            return false;
        }

        /**
         * @param $name
         * @return false|int
         */
        public function getCategoryId( $name ) {
            $this->load->model( 'tool/foks_cron' );

            $id          = false;
            $category_id = $this->model_tool_foks_cron->isCategory( $name );

            if ( !empty( $category_id ) ) {
                $id = (int)$category_id['category_id'];
            }

            return $id;
        }

        /**
         * @param $cat_id
         * @param false $parent_id
         * @return mixed|string
         */
        public static function searchCatName( $cat_id, $parent_id = false ) {
            $categories = self::$categoreis;
            $result     = '';

            foreach ( $categories as $item ) {
                if ( $item['id'] == $cat_id ) {
                    $result = $item['name'];
                }
            }

            return $result;
        }

    }
