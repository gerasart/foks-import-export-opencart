<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 06.06.2020
     * Time: 01:17
     */
    
    namespace Foks\Abstracts;
    
    
    abstract class ImportExport {
        
        
        public static function parseFile( $file ) {
            set_time_limit( 0 );
            $xmlstr = file_get_contents( $file );
            $xml    = new \SimpleXMLElement( $xmlstr );
            
            return $xml;
        }
        
    
        /**
         * @param $link
         * @param $img_path
         * @return bool
         */
        public static function loadImageFromHost( $link, $img_path ) {
            if ( !file_exists( $img_path ) ) {
                $ch = curl_init( $link );
                $fp = fopen( $img_path, "wb" );
                if ( $fp ) {
                    $options = array( CURLOPT_FILE    => $fp,
                                      CURLOPT_HEADER  => 0,
                                      CURLOPT_TIMEOUT => 60,
                    );
                    curl_setopt_array( $ch, $options );
                    curl_exec( $ch );
                    curl_close( $ch );
                    fclose( $fp );
                }
                
                return file_exists( $img_path );
            }
            
            return true;
        }
        
        
        /**
         * @param $product
         * @param $data
         */
        protected static function set_product_data( $product, $data ) {
            if ( isset( $data['raw_attributes'] ) ) {
                $attributes          = array();
                $default_attributes  = array();
                $existing_attributes = $product->get_attributes();
                
                foreach ( $data['raw_attributes'] as $position => $attribute ) {
                    $attribute_id = 0;
                    
                    // Get ID if is a global attribute.
                    if ( !empty( $attribute['taxonomy'] ) ) {
//                        $attribute_id = $this->get_attribute_taxonomy_id( $attribute['name'] );
                    }
                    
                    // Set attribute visibility.
                    if ( isset( $attribute['visible'] ) ) {
                        $is_visible = $attribute['visible'];
                    } else {
                        $is_visible = 1;
                    }
                    
                    // Get name.
                    $attribute_name = $attribute_id ? wc_attribute_taxonomy_name_by_id( $attribute_id ) : $attribute['name'];
                    
                    // Set if is a variation attribute based on existing attributes if possible so updates via CSV do not change this.
                    $is_variation = 0;
                    
                    if ( $existing_attributes ) {
                        foreach ( $existing_attributes as $existing_attribute ) {
                            if ( $existing_attribute->get_name() === $attribute_name ) {
                                $is_variation = $existing_attribute->get_variation();
                                break;
                            }
                        }
                    }
                    
                    if ( $attribute_id ) {
                        if ( isset( $attribute['value'] ) ) {
                            $options = array_map( 'wc_sanitize_term_text_based', $attribute['value'] );
                            $options = array_filter( $options, 'strlen' );
                        } else {
                            $options = array();
                        }
                        
                        // Check for default attributes and set "is_variation".
                        if ( !empty( $attribute['default'] ) && in_array( $attribute['default'], $options, true ) ) {
                            $default_term = get_term_by( 'name', $attribute['default'], $attribute_name );
                            
                            if ( $default_term && !is_wp_error( $default_term ) ) {
                                $default = $default_term->slug;
                            } else {
                                $default = sanitize_title( $attribute['default'] );
                            }
                            
                            $default_attributes[ $attribute_name ] = $default;
                            $is_variation                          = 1;
                        }
                        
                        if ( !empty( $options ) ) {
                            $attribute_object = new WC_Product_Attribute();
                            $attribute_object->set_id( $attribute_id );
                            $attribute_object->set_name( $attribute_name );
                            $attribute_object->set_options( $options );
                            $attribute_object->set_position( $position );
                            $attribute_object->set_visible( $is_visible );
                            $attribute_object->set_variation( $is_variation );
                            $attributes[] = $attribute_object;
                        }
                    } elseif ( isset( $attribute['value'] ) ) {
                        // Check for default attributes and set "is_variation".
                        if ( !empty( $attribute['default'] ) && in_array( $attribute['default'], $attribute['value'], true ) ) {
                            $default_attributes[ sanitize_title( $attribute['name'] ) ] = $attribute['default'];
                            $is_variation                                               = 1;
                        }
                        
                        $attribute_object = new WC_Product_Attribute();
                        $attribute_object->set_name( $attribute['name'] );
                        $attribute_object->set_options( $attribute['value'] );
                        $attribute_object->set_position( $position );
                        $attribute_object->set_visible( $is_visible );
                        $attribute_object->set_variation( $is_variation );
                        $attributes[] = $attribute_object;
                    }
                }
                
                $product->set_attributes( $attributes );
                
                // Set variable default attributes.
                if ( $product->is_type( 'variable' ) ) {
                    $product->set_default_attributes( $default_attributes );
                }
            }
        }
        
    }
