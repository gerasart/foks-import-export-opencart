<?php
    
    /**
     * Created by PhpStorm.
     * User: skipin
     * Date: 08.01.19
     * Time: 18:15
     */
    
    namespace Foks\Helpers;
    
    class ImageUploader {
        
        /**
         * @param $file
         * @param bool $parent_post_id
         * @return bool|int|\WP_Error
         */
        public static function upload( $file, $parent_post_id = false ) {
            $filename = basename( $file );
            $exist    = self::exist( $filename );
            
            $upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );
            if ( !$upload_file['error'] && !$exist ) {
                $wp_filetype = wp_check_filetype( $filename, null );
                
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
                    'post_status'    => 'inherit'
                );
                
                if ( $parent_post_id ) {
                    $attachment['post_parent'] = $parent_post_id;
                }
                
                $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );
                
                if ( !is_wp_error( $attachment_id ) ) {
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                    wp_update_attachment_metadata( $attachment_id, $attachment_data );
                    
                    return $attachment_id;
                } else {
                    return false;
                }
            } elseif ( $exist ) {
                if ( $parent_post_id ) {
                    $attachment = array(
                        'ID'          => $exist,
                        'post_parent' => $parent_post_id
                    );
                    wp_update_post( $attachment );
                }
                
                return $exist;
            }
            
            return false;
        }
        
        /**
         * @param $filename
         * @return bool
         */
        public static function exist( $filename ) {
            $exp   = explode( '.', $filename );
            $title = array_shift( $exp );
            
            return self::get_by_title( $title );
        }
        
        /**
         * @param $title
         * @param string $return
         * @return bool
         */
        public static function get_by_title( $title, $return = 'ID' ) {
            global $wpdb;
            
            $attachments = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_title = '$title' AND post_type = 'attachment' ", OBJECT );
            //print_r($attachments);
            if ( $attachments ) {
                $attachment = $attachments[0]->$return;
            } else {
                return false;
            }
            
            return $attachment;
        }
        
        
        /**
         * @param $url
         * @param $product_id
         * @return int|mixed
         * @throws \Exception
         */
        public static function get_attachment_id_from_url( $url, $product_id ) {
            if ( empty( $url ) ) {
                return 0;
            }
            
            $id         = 0;
            $upload_dir = wp_upload_dir( null, false );
            $base_url   = $upload_dir['baseurl'] . '/';
            
            // Check first if attachment is inside the WordPress uploads directory, or we're given a filename only.
            if ( false !== strpos( $url, $base_url ) || false === strpos( $url, '://' ) ) {
                // Search for yyyy/mm/slug.extension or slug.extension - remove the base URL.
                $file = str_replace( $base_url, '', $url );
                $args = array(
                    'post_type'   => 'attachment',
                    'post_status' => 'any',
                    'fields'      => 'ids',
                    'meta_query'  => array( // @codingStandardsIgnoreLine.
                                            'relation' => 'OR',
                                            array(
                                                'key'     => '_wp_attached_file',
                                                'value'   => '^' . $file,
                                                'compare' => 'REGEXP',
                                            ),
                                            array(
                                                'key'     => '_wp_attached_file',
                                                'value'   => '/' . $file,
                                                'compare' => 'LIKE',
                                            ),
                                            array(
                                                'key'     => '_wc_attachment_source',
                                                'value'   => '/' . $file,
                                                'compare' => 'LIKE',
                                            ),
                    ),
                );
            } else {
//                // This is an external URL, so compare to source.
//                $args = array(
//                    'post_type'   => 'attachment',
//                    'post_status' => 'any',
//                    'fields'      => 'ids',
//                    'meta_query'  => array( // @codingStandardsIgnoreLine.
//                                            array(
//                                                'value' => $url,
//                                                'key'   => '_wc_attachment_source',
//                                            ),
//                    ),
//                );
//            }
//
//            $ids = get_posts( $args ); // @codingStandardsIgnoreLine.
//
//            if ( $ids ) {
//                $id = current( $ids );
//            }
                
                // Upload if attachment does not exists.
                if ( !$id && stristr( $url, '://' ) ) {
                    $upload = wc_rest_upload_image_from_url( $url );
                    
                    if ( is_wp_error( $upload ) ) {
//                    throw new \Exception( $upload->get_error_message(), 400 );
                        return $id;
                    }
                    
                    $id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );

//                if ( !wp_attachment_is_image( $id ) ) {
//                    /* translators: %s: image URL */
//                    throw new \Exception( sprintf( __( 'Not able to attach "%s".', 'woocommerce' ), $url ), 400 );
//                }
                    
                    // Save attachment source for future reference.
                    if ( $id ) {
                        update_post_meta( $id, '_wc_attachment_source', $url );
                    }
                }
                
                if ( !$id ) {
                    /* translators: %s: image URL */
//                throw new \Exception( sprintf( __( 'Unable to use image "%s".', 'woocommerce' ), $url ), 400 );
                }
                
                return $id;
            }
            
            
        }
        
        
    }
