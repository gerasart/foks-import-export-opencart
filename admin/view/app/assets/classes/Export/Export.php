<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 6/5/2020
     * Time: 3:46 PM
     */
    
    namespace Foks\Export;
    
    use Foks\Abstracts\ImportExport;
    use Foks\Interfaces\FoksData;
    
    class Export extends ImportExport implements FoksData {
        
        static $taxonomy = 'product_cat';
        
        public static function getProducts() {
            // TODO: Implement getProducts() method.
            $args = [
                'limit'   => -1,
                'orderby' => 'date',
                'order'   => 'DESC',
                'return'  => 'ids',
                'status'  => 'publish'
            ];
            
            $query = new \WC_Product_Query( $args );
            
            $products = $query->get_products();
            
            $new_data = [];
            foreach ( $products as $product_id ) {
                $new_data[] = Helper::getProductByid( $product_id );
            }
            return $new_data;
        }
        
        public static function getCategories() {
            // TODO: Implement getCategories() method.
            $categories = get_categories( array(
                'taxonomy'     => self::$taxonomy,
                'type'         => 'post',
                'child_of'     => 0,
                'parent'       => '',
                'orderby'      => 'name',
                'order'        => 'ASC',
                'hide_empty'   => 0,
                'hierarchical' => 1,
                'exclude'      => '',
                'include'      => '',
                'number'       => 0,
                'pad_counts'   => false,
            ) );
            $top_cats   = [];
            $sub_cats   = [];
            foreach ( $categories as $cat ) {
                if ( isset( $cat->category_parent ) && $cat->category_parent == 0 ) {
                    $top_cats[ $cat->term_id ]           = $cat;
                    $top_cats[ $cat->term_id ]->children = [];
                } else {
                    if ( $cat ) {
                        $sub_cats[] = $cat;
                    }
                }
            }
            if ( $sub_cats ) {
                foreach ( $sub_cats as $sub_cat ) {
                    if ( isset( $top_cats[ $sub_cat->category_parent ] ) ) {
                        $top_cats[ $sub_cat->category_parent ]->children[] = ($sub_cat);
                    }
                }
            }
            
            return $top_cats;
        }
        
        
        public static function generateXML() {
            $categories = self::getCategories();
            $products   = self::getProducts();
            $site_url   = get_site_url();
            $site_name  = get_bloginfo( 'name' );
            $currency   = get_woocommerce_currency();
            
            $date = date( 'Y-m-d H:i:s' );
            
            $output = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
            $output .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">' . "\n";
            $output .= '<yml_catalog date="' . $date . '">' . "\n";
            $output .= '<shop>' . "\n";
            if ( $site_name ) {
                $output .= '<name>' . $site_name . '</name>' . "\n";
                $output .= '<company>' . $site_name . '</company>' . "\n";
            }
            $output .= '<url>' . $site_url . '</url>' . "\n";
            $output .= '<currencies>' . "\n";
            $output .= '<currency id="' . $currency . '" rate="1" />' . "\n";
            $output .= '</currencies>' . "\n";
            if ( $categories ) {
                $output .= '<categories>' . "\n";
                foreach ( $categories as $item ) {
                    $output .= "\t" . '<category id="' . $item->term_id . '">' . $item->name . '</category>' . "\n";
                    if ( !empty( $item->children ) ) {
                        foreach ( $item->children as $child ) {
                            $output .= "\t" . '<category parent_id="' . $child->category_parent . '" id="' . $child->term_id . '">' . $child->name . '</category>' . "\n";
                        }
                    }
                }
                $output .= '</categories>' . "\n";
            }
            $output .= '<offers>' . "\n";
            foreach ( $products as $product ) {
                if ( $product ) {
                    $output .= "\t" . '<offer id="' . $product->id . '" available="true">' . "\n";
                    $output .= "\t" . '<categoryId>' . $product->category_id . '</categoryId>' . "\n";
                    $output .= "\t" . '<stock_quantity>' . $product->quantity . '</stock_quantity>' . "\n";
                    $output .= "\t" . '<url>' . $product->url . '</url>' . "\n";
                    if ( (int)$product->sale_price ) :
                        $output .= "\t" . '<price>' . $product->sale_price . '</price>' . "\n";
                        $output .= "\t" . '<price_old>' . $product->price . '</price_old>' . "\n";
                    else:
                        $output .= "\t" . '<price>' . $product->price . '</price>' . "\n";
                    endif;
                    $output .= "\t" . '<currencyId>' . $currency . '</currencyId>' . "\n";
                    if ( $product->thumb ) :
                        $output .= "\t" . '<picture>' . $product->thumb . '</picture>' . "\n";
                    endif;
                    if ( $product->images ):
                        foreach ( $product->images as $img ) {
                            $output .= "\t" . '<picture>' . $img . '</picture>' . "\n";
                        }
                    endif;
                    if ( $product->vendor ) :
                        $output .= "\t" . '<vendor>' . $product->vendor . '</vendor>' . "\n";
                    endif;
                    $output .= "\t" . '<name>' . $product->title . '</name>' . "\n";
                    $output .= "\t" . '<description>' . htmlspecialchars( $product->description ) . "\n";
                    $output .= "\t" . '</description>' . "\n";
                    if ( $product->params ):
                        foreach ( $product->params as $attr ) :
                            if ( !$attr['terms'] ) {
                                $output .= "\t" . '<param name="' . $attr['name'] . '">' . $attr['value'] . '</param>' . "\n";
                            } else {
                                $attr_name = wc_attribute_label( $attr['name'] );
                                $output    .= "\t" . '<param name="' . $attr_name . '">' . $attr['value'] . '</param>' . "\n";
                            }
                        endforeach;
                    endif;
                    $output .= "\t" . '</offer>' . "\n";
                }
            }
            $output .= '</offers>' . "\n";
            $output .= '</shop>' . "\n";
            $output .= '</yml_catalog>';
    
            file_put_contents( FOKS_PATH . '/logs/foks_export.xml', $output );
    
            header( "Content-Type: application/xml; charset=utf-8" );
    
            echo $output;
            
        }
        
        public static function getGenerateXml() {
            register_rest_route( 'foks', 'foksExport', array(
                'methods'  => 'GET',
                'callback' => __CLASS__ . '::generateXML',
            ) );
        }
        
    }
