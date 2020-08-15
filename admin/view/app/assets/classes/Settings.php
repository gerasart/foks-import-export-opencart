<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 7/25/2019
     * Time: 6:13 PM
     */
    
    namespace Foks;
    
    class Settings {
        
        /**
         * Settings constructor.
         */
        public function __construct() {
            add_action( 'admin_menu', [ $this, 'settingPage' ], 12 );
            add_filter( 'plugin_action_links_' . FOKS_BASENAME, [ $this, 'plugin_action_links' ] );
        }
        
        
        public function settingPage() {
            add_menu_page(
                'FOKS Import/Export',
                'FOKS Import/Export',
                'edit_posts',
                FOKS_NAME,
                [ $this, FOKS_NAME ],
                FOKS_URL . '/inc/img/icon.png'
            );
        }
        
        public function foks() {
            include FOKS_PATH . 'views/index.php';
        }
        
        /**
         * @param $links
         * @return mixed
         */
        public function plugin_action_links( $links ) {
            $settings_link = '<a href="' . menu_page_url( FOKS_NAME, false ) . '">' . esc_html( __( 'Settings', 'custom' ) ) . '</a>';
            array_unshift( $links, $settings_link );
            
            return $links;
        }
        
        
    }
