<?php
    
    class ControllerToolFoksCron extends Controller {
        private $dir;
        
        public function index() {
            $this->dir = str_replace( 'catalog', 'admin', DIR_APPLICATION );
            $this->ImportFoksCron();
        }
        
        /**
         * @param $file
         * @return array|\SimpleXMLElement
         */
        public function parseFile( $file ) {
            set_time_limit( 0 );
            $xmlstr = file_get_contents( $file );
            $xml    = new \SimpleXMLElement( $xmlstr );
            
            return [
                'products'   => $this->parseProducts( $xml->shop->offers ),
                'categories' => self::parseCategories( $xml->shop->categories )
            ];
        }
        
        /**
         * @param $categories
         * @return array
         */
        public static function parseCategories( $categories ) {
            $categoriesList = array();
            $data           = $categories->category;
            foreach ( $data as $category ) {
                $categoriesList[] = array(
                    'parent_id'   => (int)$category['parentId'],
                    'name'        => trim( (string)$category ),
                    'id'          => (string)$category['id'],
                    'parent_name' => '',
                    'store_id'    => 0,
                    'column'      => 0,
                    'status'      => 1,
                    'noindex'     => 0,
                    'sort_order'  => 1
                );
            }
            $categories_result = [];
            foreach ( $categoriesList as $item ) {
                $item['parent_name'] = self::getParentCatName( $categoriesList, $item['parent_id'] );
                $categories_result[] = $item;
            }
            
            return $categories_result;
        }
        
        /**
         * @param $categoriesList
         * @param $parent_id
         * @param bool $id
         * @return string
         */
        public static function getParentCatName( $categoriesList, $parent_id, $id = false ) {
            $cat_name = '';
            foreach ( $categoriesList as $cat ) {
                if ( (int)$cat['id'] === $parent_id ) {
                    $cat_name = $cat['name'];
                    break;
                } else {
                    if ( $id && (int)$cat['id'] === $id ) {
                        $cat_name = $cat['name'];
                    }
                }
                
            }
            
            return $cat_name;
        }
        
        /**
         * @param $offers
         * @return array
         */
        public function parseProducts( $offers ) {
            $this->load->model( 'tool/foks_cron' );
            $n      = count( $offers->offer );
            $result = [];
            for ( $i = 0; $i < $n; $i++ ) {
                
                $offer = $offers->offer[ $i ];
                
                $product_images = [];
                $attributes     = [];
                $pictures       = $offer->picture;
                if ( count( $pictures ) > 1 ) {
                    unset( $pictures[0] );
                    foreach ( $pictures as $picture ) {
                        $product_images[] = (string)$picture;
                    }
                }
                
                $productName = (string)$offer->name;
                if ( !$productName ) {
                    if ( isset( $offer->typePrefix ) ) {
                        $productName = (string)$offer->typePrefix . ' ' . (string)$offer->model;
                    } else {
                        $productName = (string)$offer->model;
                    }
                }
                
                if ( isset( $offer->param ) ) {
                    $params = $offer->param;
                    
                    foreach ( $params as $param ) {
                        $attr_name  = (string)$param['name'];
                        $attr_value = (string)$param;
                        
                        $attributes[] = [
                            'name'  => $attr_name,
                            'value' => $attr_value
                        ];
                    }
                }
                //                $id_category  = (int)$offer->categoryId;
                
                $product_description = (string)$offer->description;
                $category_name       = (string)$offer->category;
                $manufacturer        = isset( $offer->vendor ) ? (string)$offer->vendor : '';
                $price_old           = isset( $offer->price_old ) ? (float)$offer->price_old : '';
                $data                = array(
                    'name'            => $productName,
                    'price'           => (float)$offer->price,
                    'price_old'       => $price_old,
                    'quantity'        => (isset( $offer->outlets->outlet['instock'] )) ? (int)$offer->outlets->outlet['instock'] : '999',
                    'model'           => (string)$offer['id'],
                    'sku'             => (!empty( $offer->vendorCode )) ? (string)$offer->vendorCode : (string)$offer['id'],
                    'category'        => $category_name,
                    'category_id'     => $this->getCategoryId( $category_name ),
                    'description'     => $product_description,
                    'image'           => isset( $offer->picture[0] ) ? $offer->picture[0] : '',
                    'images'          => $product_images,
                    'date_available'  => date( 'Y-m-d' ),
                    'manufacturer_id' => $this->getManufacturerId( $manufacturer ),
                    'manufacturer'    => $manufacturer,
                    'status'          => '0',
                    'attributes'      => $this->model_tool_foks_cron->addAttributes( $attributes ),
                );

//                var_dump($data);
                $result[ $i ] = $data;
                
            }
            return $result;
        }
        
        /**
         * @param $file
         * @return array|\SimpleXMLElement
         * @throws \Exception
         */
        public function importData( $file ) {
            $this->load->model( 'tool/foks_cron' );
            $data          = $this->parseFile( $file );
            $total_product = count( $data['products'] );
            
            $this->model_tool_foks_cron->addCategories( $data['categories'] );
            $this->model_tool_foks_cron->addProducts( $data['products'] );
            
            return $data;
        }
        
        public function ImportFoksCron() {
            $this->load->model( 'tool/foks_cron' );
            
            $file_x = $this->model_tool_foks_cron->getSetting( 'foks_import_url' );
            $file   = str_replace( "&amp;", '&', $file_x );
            
            if ( $file ) {
                $xml = file_get_contents( $file );
                file_put_contents( $this->dir . '/view/app/logs/foks_import.xml', $xml );
                $file_path = $this->dir . '/view/app/logs/foks_import.xml';
                $this->importData( $file_path );
            }
        }
        
        public function getManufacturerId( $name ) {
            $this->load->model( 'tool/foks_cron' );
            
            $data = [
                'name'                     => $name,
                'sort_order'               => 1,
                'noindex'                  => 1,
                'manufacturer_description' => '',
            ];
            
            $manufacturer = $this->model_tool_foks_cron->isManufacturer( $name );
            
            if ( isset( $manufacturer['manufacturer_id'] ) ) {
                $id = $manufacturer['manufacturer_id'];
            } else {
                $id = $this->model_tool_foks_cron->addManufacturerImport( $data );
            }
            
            return $id;
        }
        
        public function getCategoryId( $name ) {
            $this->load->model( 'tool/foks_cron' );
            
            $id          = false;
            $category_id = $this->model_tool_foks_cron->isCategory( $name );
            
            if ( !empty( $category_id ) ) {
                $id = (int)$category_id['category_id'];
            }
            
            return $id;
        }
        
    }