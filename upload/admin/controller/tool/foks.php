<?php
//    http://opencart20.loc:8888/admin/index.php?route=tool/foks&token=ecf30a52234187c91537942f764a0a13
//    https://my.foks.biz/s/pb/f?key=498100e4-d428-42dc-96fa-ce2955dba3ed&type=yml_catalog&ext=xml
    class ControllerToolFoks extends Controller {
        
        private $error = array();
        
        public function index() {
            
            $this->document->addScript( '/admin/view/javascript/app/dist/scripts/vue.js' );
            $this->document->addStyle( '/admin/view/javascript/app/dist/styles/vue.css' );
            $this->document->setTitle( 'Foks import/Export' );
            $version = version_compare( VERSION, '3.0.0', '>=' );
            
            self::createImgFolder();
            
            $this->load->model( 'tool/foks' );
            
            $data['heading_title'] = 'Foks import/Export';
            
            if ( isset( $this->session->data['error'] ) ) {
                $data['error_warning'] = $this->session->data['error'];
                
                unset( $this->session->data['error'] );
            } elseif ( isset( $this->error['warning'] ) ) {
                $data['error_warning'] = $this->error['warning'];
            } else {
                $data['error_warning'] = '';
            }
            
            if ( isset( $this->session->data['success'] ) ) {
                $data['success'] = $this->session->data['success'];
                
                unset( $this->session->data['success'] );
            } else {
                $data['success'] = '';
            }
            if ( !$version ) {
                $token     = $this->session->data['token'];
                $token_str = 'token';
            } else {
                $token     = $this->session->data['user_token'];
                $token_str = 'user_token';
            }
            
            $data['breadcrumbs'] = array();
            
            
            $data['breadcrumbs'][] = array(
                'text' => 'Home',
                'href' => $this->url->link( 'common/dashboard', "{$token_str}=" . $token, 'SSL' )
            );
            
            $data['breadcrumbs'][] = array(
                'text' => 'Foks',
                'href' => $this->url->link( 'tool/backup', "{$token_str}=" . $token, 'SSL' )
            );
//            var_dump(version_compare(VERSION, '2.2.0', '<='));
            $file = str_replace( "&amp;", '&', $this->model_tool_foks->getSetting( 'foks_import_url' ) );
            
            $foks_settings['foks'] = [
                'import'   => $file, //url
                'img'      => (boolean)$this->model_tool_foks->getSetting( 'foks_img' ), //import with img
                'logs_url' => '', //folder url
                'update'   => '', //cron settings
                'token'    => $token,
                'version3' => $version,
            ];
            file_put_contents( DIR_APPLICATION . 'view/app/logs/total.json', 0 );
            file_put_contents( DIR_APPLICATION . 'view/app/logs/current.json', 0 );
            
            
            $data['local_vars'] = self::LocalVars( $foks_settings );
            
            $data['header']      = $this->load->controller( 'common/header' );
            $data['column_left'] = $this->load->controller( 'common/column_left' );
            $data['footer']      = $this->load->controller( 'common/footer' );
            
            $this->response->setOutput( $this->load->view( 'tool/foks.tpl', $data ) );
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
            $this->load->model( 'tool/foks' );
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
                    'attributes'      => $this->model_tool_foks->addAttributes( $attributes ),
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
            $this->load->model( 'tool/foks' );
            $data          = $this->parseFile( $file );
            $total_product = count( $data['products'] );
            file_put_contents( DIR_APPLICATION . 'view/app/logs/total.json', $total_product );
            
            $this->model_tool_foks->addCategories( $data['categories'] );
            $this->model_tool_foks->addProducts( $data['products'] );
            
            return $data;
        }
        
        public static function LocalVars( $data ) {
            $html = '';
            
            foreach ( $data as $key => $value ) {
                $html .= "<script>";
                if ( !is_string( $value ) ) {
                    $value = json_encode( $value, JSON_UNESCAPED_UNICODE );
                } elseif ( is_string( $value ) ) {
                    $value = "'$value'";
                }
                
                $html .= "window.{$key} = {$value};" . "\n";
                $html .= "</script>";
            }
            return $html;
        }
        
        public function ajaxSaveSettings() {
            $post = $this->request->post;
            
            $this->load->model( 'tool/foks' );
            
            $img_val = $post['img'] === 'false' ? '0' : '1';
            
            $this->model_tool_foks->editSetting( 'foks_img', $img_val );
            $this->model_tool_foks->editSetting( 'foks_import_url', $post['import'] );
            
            $json = $post;
            $this->response->addHeader( 'Content-Type: application/json' );
            $this->response->setOutput( json_encode( $json ) );
        }
        
        public function ajaxImportFoks() {
            file_put_contents( DIR_APPLICATION . 'view/app/logs/total.json', 0 );
            file_put_contents( DIR_APPLICATION . 'view/app/logs/current.json', 0 );
            
            $this->load->model( 'tool/foks' );
            
            $file_x = $this->model_tool_foks->getSetting( 'foks_import_url' );
            $file   = str_replace( "&amp;", '&', $file_x );
            
            $data = [];
            if ( $file ) {
                $xml = file_get_contents( $file );
                file_put_contents( DIR_APPLICATION . 'view/app/logs/foks_import.xml', $xml );
                $file_path = DIR_APPLICATION . 'view/app/logs/foks_import.xml';
                $data      = $this->importData( $file_path );
            }
            
            $this->response->addHeader( 'Content-Type: application/json' );
            $this->response->setOutput( json_encode( $data ) );
        }
        
        public function getManufacturerId( $name ) {
            $this->load->model( 'tool/foks' );
            
            $data = [
                'name'                     => $name,
                'sort_order'               => 1,
                'noindex'                  => 1,
                'manufacturer_description' => '',
            ];
            
            $manufacturer = $this->model_tool_foks->isManufacturer( $name );
            
            if ( isset( $manufacturer['manufacturer_id'] ) ) {
                $id = $manufacturer['manufacturer_id'];
            } else {
                $id = $this->model_tool_foks->addManufacturerImport( $data );
            }
            
            return $id;
        }
        
        public function getCategoryId( $name ) {
            $this->load->model( 'tool/foks' );
            
            $id          = false;
            $category_id = $this->model_tool_foks->isCategory( $name );
            
            if ( !empty( $category_id ) ) {
                $id = (int)$category_id['category_id'];
            }
            
            return $id;
        }
        
        public static function createImgFolder() {
            $dir = DIR_IMAGE . 'catalog/image_url';
            
            if ( !file_exists( $dir ) ) {
                mkdir( $dir, 0777, true );
            }
        }
        
        
    }
