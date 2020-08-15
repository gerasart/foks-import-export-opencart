<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 08.06.2020
     * Time: 01:14
     */
    
    namespace Foks\Export;
    
    
    class Helper {
        
        public static function getProductByid( $product_id ) {
            $thumb          = has_post_thumbnail( $product_id ) ? get_the_post_thumbnail_url( $product_id, 'full' ) : false;
            $categories     = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
            $product        = new \WC_Product( $product_id );
            $attachment_ids = $product->get_gallery_image_ids();
            $images         = [];
            if ( !empty( $attachment_ids ) ) {
                foreach ( $attachment_ids as $attachment_id ) {
                    $images[] = wp_get_attachment_image_url( $attachment_id, 'full' );
                }
            }
            $price      = get_post_meta( $product_id, '_regular_price', true );
            $sale_price = get_post_meta( $product_id, '_sale_price', true );
            $quantity   = get_post_meta( $product_id, '_stock', true );
            $sku        = get_post_meta( $product_id, '_sku', true );
            $attributes = $product->get_attributes();
            $attr_data  = [];
            if ( $attributes ) {
                foreach ( $attributes as $item ) {
                    $value_names = $item->get_options();
                    if ( $item->get_terms() ) {
                        $value_names = [];
                        foreach ( $item->get_terms() as $term ) {
                            $value_names[] = $term->name;
                        }
                    }
                    $attr_data[] = [
                        'name'  => $item->get_name(),
                        'value' => implode( ', ', $value_names ),
                        'terms' => $item->get_terms(),
                        'slug'  => $item->get_data()
                    ];
                }
            
            }
            return (object)[
                'id'          => $product_id,
                'title'       => html_entity_decode( get_the_title( $product_id ) ),
                'url'         => get_the_permalink( $product_id ),
                'thumb'       => $thumb ? $thumb : '',
                'images'      => $images ? $images : [],
                'description' => $product->get_description(),
                'status'      => get_post_meta( $product_id, '_stock_status', true ),
                'category'    => isset( $categories[0] ) ? $categories[0] : '',
                'category_id' => self::getProductID( $product_id ),
                'price'       => $price ? $price: '',
                'sale_price'  => $sale_price ? $sale_price : '',
                'quantity'    => $quantity ? $quantity : 999,
                'sku'         => $sku ? $sku : '',
                'params'      => $attr_data ? $attr_data : [],
                'vendor'      => '',
            ];
        }
        
        public static function formatPrice( $price ) {
            if ( $price ) {
                $args = [
                    'decimal_separator'  => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'decimals'           => wc_get_price_decimals(),
                    'price_format'       => get_woocommerce_price_format(),
                ];
                
                return number_format( (int)$price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );
            } else {
                return 0;
            }
        }
        
        public static function getProductID( $product_id ) {
            $terms  = get_the_terms( $product_id, 'product_cat' );
            $cat_id = 0;
            if ( $terms ) {
                foreach ( $terms as $term ) {
                    if ( $term->term_id ) {
                        $cat_id = $term->term_id;
                        break;
                    }
                }
            }
            return $cat_id;
        }
        
    }
