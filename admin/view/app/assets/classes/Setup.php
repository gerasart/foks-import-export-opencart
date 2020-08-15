<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 1/23/2019
     * Time: 2:01 PM
     */
    
    namespace Foks;
    
    use Foks\Export\Export;
    use Foks\Traits\LocalVars;

    ini_set( 'memory_limit', '1024M' );


    class Setup {
        
        use LocalVars;
        
        static $front_vars = [];
        static $admin_vars = [];
        
        
        /**
         * Setup constructor.
         */
        public function __construct() {
            if ( FOKS_PAGE === 'page=' . FOKS_NAME ) {
                file_put_contents(FOKS_PATH.'/logs/total.json', 0);
    
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
                add_action( 'admin_head', array( __CLASS__, 'localAdminVars' ) );
                add_action( 'init', [ $this, 'init' ] );
            }
            add_action( 'rest_api_init', array( 'Foks\Export\Export', 'getGenerateXml' ) );
            
        }
        
        public function init() {
            $this->viewData();
        }
        
        public function enqueue_admin() {
            wp_enqueue_script( FOKS_NAME, FOKS_URL . 'dist/scripts/vue.js', array( 'jquery' ), time(), true );
            wp_enqueue_style( FOKS_NAME, FOKS_URL . 'dist/styles/vue.css' );
        }
    }
