<?php
//    http://opencart20.loc:8888/admin/index.php?route=tool/foks&token=ecf30a52234187c91537942f764a0a13
    
    
    class ControllerToolFoks extends Controller {
        
        private $error = array();
        
        public function index() {
            $this->document->addScript( '/admin/view/app/dist/scripts/vue.js' );
            $this->document->addStyle( '/admin/view/app/dist/styles/vue.css' );
            $this->document->setTitle( 'Foks import/Export' );
    
    
            $this->confi->set('foks_import_url', '');
            
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
            
            $token = $this->session->data['token'];
            
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => 'Home',
                'href' => $this->url->link( 'common/dashboard', 'token=' . $token, 'SSL' )
            );
            
            $data['breadcrumbs'][] = array(
                'text' => 'Foks',
                'href' => $this->url->link( 'tool/backup', 'token=' . $token, 'SSL' )
            );
            
            
            $foks_settings['foks'] = [
                'import'   => $this->confi->get('foks_import_url'), //url
                'img'      => false, //import with img
                'logs_url' => '', //folder url
                'update'   => '', //cron settings
                'token' => $token
            ];
            
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
        public static function parseFile( $file ) {
            set_time_limit( 0 );
            $xmlstr = file_get_contents( $file );
            $xml    = new \SimpleXMLElement( $xmlstr );
            
            return [
                'products'   => self::parseProducts( $xml->shop->offers ),
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
                    'parent_name' => ''
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
        
        public static function parseProducts( $offers ) {
            $n      = count( $offers->offer );
            $data   = [];
            $result = [];
            for ( $i = 0; $i < $n; $i++ ) {
                $offer = $offers->offer[ $i ];
                
                $product_images = [];
                
                foreach ( $offer->picture as $picture ) {
                    $product_images[] = (string)$picture;
                }
                
                $productName = (string)$offer->name;
                if ( !$productName ) {
                    if ( isset( $offer->typePrefix ) ) {
                        $productName = (string)$offer->typePrefix . ' ' . (string)$offer->model;
                    } else {
                        $productName = (string)$offer->model;
                    }
                }
                
                $product_description = (string)$offer->description;
                
                $id_category = (int)$offer->categoryId;
                
                $data = array(
                    'name'           => $productName,
                    'description'    => $product_description,
                    'category'       => $id_category,
                    'model'          => (!empty( $offer->vendorCode )) ? (string)$offer->vendorCode : (string)$offer['id'],
                    'thumb'          => $product_images[0],
                    'sku'            => (!empty( $offer->vendorCode )) ? (string)$offer->vendorCode : (string)$offer['id'],
                    'quantity'       => (isset( $offer->outlets->outlet['instock'] )) ? (int)$offer->outlets->outlet['instock'] : '999',
                    //                    'stock_status_id'     => ($offer['available'] == 'true') ? 7 : 8,
                    'date_available' => date( 'Y-m-d' ),
                    'price'          => (float)$offer->price,
                    'price_old'      => (float)$offer->price_old,
                    'status'         => '0',
                    'images'         => $product_images,
                    'attributes'     => [],
                    'manufacturer'   => ''
                );
                
                if ( isset( $offer->vendor ) ) {
                    $data['manufacturer'] = (string)$offer->vendor;
                }
                
                if ( isset( $offer->param ) ) {
                    $params = $offer->param;
                    
                    foreach ( $params as $param ) {
                        $attr_name  = (string)$param['name'];
                        $attr_value = (string)$param;
                        
                        $data['attributes'][] = [
                            'name'  => $attr_name,
                            'value' => $attr_value
                        ];
                    }
                }
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
            
            
            $data          = self::parseFile( $file );
            $total_product = count( $data['products'] );
            file_put_contents( DIR_APPLICATION . '/view/app/logs/total.json', $total_product );

//            $categories = $this->model_tool_foks->addCategories($data['categories']);
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
            $post = $_POST;
            
            $this->config->set('foks_img', $post['img']);
            $this->config->set('foks_import_url', $post['import']);
            
            $json = $post;
            $this->response->addHeader( 'Content-Type: application/json' );
            $this->response->setOutput( json_encode( $json ) );
        }
        
        
    }
