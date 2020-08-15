<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 07.06.2020
     * Time: 12:00
     */
    
    namespace Foks\Model;
    
    use Foks\Helpers\Helpers;
    use Foks\Import\Import;
    use Foks\Helpers\ImageUploader;
    
    class ModelProduct {
        /* Todo
            Картинки
        */
        
        /**
         * @param $manufacturer_data
         * @return bool
         */
        public static function addManufacturer( $manufacturer_data ) {
            return true;
        }
        
        /**
         * @param $category_data
         * @return mixed
         */
        public static function addCategories( $category_data ) {
            foreach ( $category_data as $cat ) {
                if ( !$cat['parent_id'] ) {
                    $term_exist = term_exists( (string)$cat['parent_name'], 'product_cat' );
                    if ( !$term_exist ) {
                        wp_insert_term( (string)$cat['name'], 'product_cat' );
                    }
                }
            }
            
            
            foreach ( $category_data as $cat ) {
                if ( $cat['parent_id'] ) {
                    $parent     = term_exists( (string)$cat['parent_name'], 'product_cat' );
                    $term_exist = term_exists( (string)$cat['name'], 'product_cat' );
                    
                    if ( $parent ) {
                        $parent_arr = [
                            'description' => (string)$cat['name'],
                            'parent'      => $parent['term_id'],
                            'slug'        => Helpers::translit( (string)$cat['name'], true )
                        ];
                        if ( !$term_exist ) {
                            wp_insert_term( (string)$cat['name'], 'product_cat', $parent_arr );
                        }
                    } else {
                        if ( !$term_exist ) {
                            wp_insert_term( (string)$cat['name'], 'product_cat' );
                        }
                    }
                }
            }
            
            
            return $category_data;
        }
        
        
        /**
         * @param $products
         * @param $categories
         * @return bool
         * @throws \Exception
         */
        public static function addProducts( $products, $categories ) {
            $foks_img = get_option( 'foks_img' );
            
            $i = 0;
            foreach ( $products as $product ) {
                $i++;
                file_put_contents( FOKS_PATH . '/logs/current.json', $i );
                $post = array(
                    'post_content' => $product['description'],
                    'post_status'  => "pending",
                    'post_title'   => $product['name'],
                    'post_name'    => Helpers::translit( $product['name'], true ),
                    'post_parent'  => '',
                    'post_type'    => "product",
                );
                
                $is_product = self::isProduct( $product['name'] );
                if ( !$is_product ) {
                    $product_id = wp_insert_post( $post );
                } else {
                    $product_id = (int)$is_product->ID;
                }
                
                self::updateCategory( $product, $product_id, $categories );
                
                if ( !$foks_img || $foks_img === 'false' ) {
                    self::addImages( $product_id, $product['images'] );
                }
                
                if ( !$is_product ) {
                    wp_set_object_terms( $product_id, 'simple', 'product_type' );
                }
                update_post_meta( $product_id, '_visibility', 'visible' );
                update_post_meta( $product_id, '_stock_status', 'instock' );
                
                if ( $product['price_old'] ) {
                    update_post_meta( $product_id, '_sale_price', $product['price'] );
                    update_post_meta( $product_id, '_price', $product['price'] );
                    update_post_meta( $product_id, '_regular_price', $product['price_old'] );
                } else {
                    update_post_meta( $product_id, '_price', $product['price'] );
                    update_post_meta( $product_id, '_regular_price', $product['price'] );
                }
                
                update_post_meta( $product_id, '_featured', "no" );
                update_post_meta( $product_id, '_sku', $product['model'] );
                update_post_meta( $product_id, '_product_attributes', array() );
                
                self::addAttributeGroup( $product_id, $product['attributes'] );
                
                update_post_meta( $product_id, '_manage_stock', "no" );
                update_post_meta( $product_id, '_backorders', "no" );
                update_post_meta( $product_id, '_stock', $product['quantity'] );
                
            }
        }
        
        
        /**
         * @param $name
         * @return mixed
         */
        public static function isProduct( $name ) {
            global $wpdb;
            
            $query = "SELECT * FROM {$wpdb->prefix}posts WHERE post_title  = '{$name}'";
            $data  = $wpdb->get_results( $query );
            return $data[0];
        }
        
        /**
         * @param $product
         * @param $product_id
         * @param $categories
         * @return mixed
         */
        public static function updateCategory( $product, $product_id, $categories ) {
            $term_name = Import::getParentCatName( $categories, $product['category'], $product['category'] );
            
            $cat = term_exists( $term_name, 'product_cat' );
            
            if ( $cat ) {
                wp_set_post_terms( $product_id, (string)$cat['term_id'], 'product_cat' );
            }
            
            return $cat;
        }
        
        /**
         * @param $product_id
         * @param $images
         * @throws \Exception
         */
        public static function addImages( $product_id, $images ) {
            if ( isset( $images[0] ) ) {
                $id = ImageUploader::get_attachment_id_from_url( $images[0], $product_id );
                
                update_post_meta( $product_id, '_thumbnail_id', $id );
                
                if ( isset( $images[1] ) ) {
                    $i                   = 0;
                    $updated_gallery_ids = [];
                    foreach ( $images as $img ) {
                        $i++;
                        if ( $i > 1 ) {
                            if ( $img ) {
                                $updated_gallery_ids[] = ImageUploader::get_attachment_id_from_url( $img, $product_id );
                            }
                        }
                    }
                    
                    update_post_meta( $product_id, '_product_image_gallery', implode( ',', $updated_gallery_ids ) );
                }
            }
            
            
        }
        
        
        /**
         * @param $product_id
         * @param $attrs
         */
        public static function addAttributeGroup( $product_id, $attrs ) {
            $product_attributes = array();
            $i                  = 0;
            foreach ( $attrs as $attr ) {
                $product_attributes[ sanitize_title( $attr['name'] ) ] = array(
                    'name'         => wc_clean( $attr['name'] ), // set attribute name
                    'value'        => $attr['value'], // set attribute value
                    'position'     => $i,
                    'is_visible'   => 1,
                    'is_variation' => 0,
                    'is_taxonomy'  => 0
                );
                $i++;
            }
            update_post_meta( $product_id, '_product_attributes', $product_attributes );
        }
        
    }
