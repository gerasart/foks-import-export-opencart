<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 6/5/2020
     * Time: 3:46 PM
     */
    
    namespace Foks\Import;
    
    
    use Foks\Abstracts\ImportExport;
    use Foks\Helpers\Helpers;
    use Foks\Model\ModelProduct;
    
    class Import extends ImportExport {
        
        /**
         * @param $offers
         * @return array
         */
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
         * @param $file
         * @return array|\SimpleXMLElement
         * @throws \Exception
         */
        public static function importData( $file ) {
            $data = self::parseFile( $file );
            $total_product = count($data['products']);
            file_put_contents(FOKS_PATH.'/logs/total.json', $total_product);
            $categories = ModelProduct::addCategories( $data['categories'] );
            ModelProduct::addProducts( $data['products'], $categories );
            
            return $data;
        }
    }
