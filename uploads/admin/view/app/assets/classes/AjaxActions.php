<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 7/26/2019
     * Time: 2:51 PM
     */
    
    namespace Foks;
    
    
    use Foks\Helpers\AjaxHelper;
    use Foks\Import\Import;
    
    class AjaxActions extends AjaxHelper {
        
        public function __construct() {
            self::declaration_ajax();
        }
        
        public static function ajax_nopriv_importFoks() {
            file_put_contents( FOKS_PATH . '/logs/total.json', 0 );
            
            $file = get_option( 'foks_import' );
            $data = [];
            if ( $file ) {
                $xml = file_get_contents( $file );
                file_put_contents( FOKS_PATH . '/logs/foks_import.xml', $xml );
                $file_path = FOKS_URL . '/logs/foks_import.xml';
                $data      = Import::importData( $file_path );
            }
            
            wp_send_json_success( $data );
            
        }
        
        public static function ajax_nopriv_saveSettings() {
            $post = $_POST['data'];
            
            $import = update_option( 'foks_import', $post['import'] );
            $update = update_option( 'foks_update', $post['update'] );
            $img    = update_option( 'foks_img', $post['img'] );
            
            $result = [
                'import' => $import,
                'update' => $update,
                'img'    => $img,
            ];
            wp_send_json_success( $result );
            
        }
        
    }
