<?php
    /**
     * Created by PhpStorm.
     * User: geras
     * Date: 7/31/2019
     * Time: 4:44 PM
     */
    
    namespace Foks\Traits;
    
    trait LocalVars {
        public function viewData() {
            $import = get_option( 'foks_import' );
            $update = get_option( 'foks_update' );
            $img    = get_option( 'foks_img' );
            
            self::$admin_vars['foks'] = [
                'import'   => $import ? $import : '',
                'update'   => $update ? $update : '1',
                'export'   => get_site_url() . '/wp-json/foks/foksExport',
                'logs_url' => FOKS_URL . 'logs/',
                'img'      => $img && $img === 'true' ? (boolean)$img : false
            ];
        }
        
        public static function localAdminVars() {
            echo "<script>";
            foreach ( self::$admin_vars as $key => $value ) {
                if ( !is_string( $value ) ) {
                    $value = json_encode( $value, JSON_UNESCAPED_UNICODE );
                }
                
                echo "window.{$key} = {$value};" . "\n";
            }
            echo "</script>";
        }
    }
