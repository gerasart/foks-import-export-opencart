<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 6/5/2020
     * Time: 4:00 PM
     */
    
    namespace Foks;
    
    
    use Foks\Export\Export;
    use Foks\Import\Import;
    
    class Cron {
        
        public function __construct() {
            self::declaration();
            add_filter( 'cron_schedules', array( __CLASS__, 'cronTimes' ) );
            add_action( 'wp', array( __CLASS__, 'registration' ) );
//        wp_clear_scheduled_hook( 'resetRegular' );
        }
        
        /**
         * @param $schedules {
         * @return mixed
         */
        public static function cronTimes( $schedules ) {
            $schedules['one_min']   = array(
                'interval' => 60,
                'display'  => 'Every 1 min'
            );
            $schedules['one_hour']  = array(
                'interval' => 3600,
                'display'  => 'Every 1 hour'
            );
            $schedules['four_hour'] = array(
                'interval' => 14400,
                'display'  => 'Every 4 hour'
            );
            $schedules['one_day']   = array(
                'interval' => 43200,
                'display'  => 'Every 1 day'
            );
            $schedules['three_day'] = array(
                'interval' => 129600,
                'display'  => 'Every 3 day'
            );
            
            return $schedules;
        }
        
        public static function registration() {
            $option_time = (int)get_option( 'foks_update' );
            if ( !wp_next_scheduled( 'ImportProducts' ) ) {
                switch ($option_time) {
                    case 1:
                        wp_schedule_event( time(), 'one_hour', 'ImportProducts' );
                        break;
                    case 4:
                        wp_schedule_event( time(), 'four_hour', 'ImportProducts' );
                        break;
                    default:
                        wp_schedule_event( time(), 'one_day', 'ImportProducts' );
                        break;
                }
            }
            
        }
        
        
        public static function declaration() {
            $pref          = 'action_';
            $class_methods = get_class_methods( get_called_class() );
            foreach ( $class_methods as $name ) {
                
                $need  = strpos( $name, $pref );
                $short = str_replace( $pref, '', $name );
                
                if ( $need === 0 ) {
                    add_action( $short, array( get_called_class(), $name ) );
                }
            }
        }
    
        /**
         * @throws \Exception
         */
        public static function action_ImportProducts() {
            $file = get_option( 'foks_import' );
            if ( $file ) {
                $xml = file_get_contents( $file );
                file_put_contents( FOKS_PATH . '/logs/foks_import.xml', $xml );
                $file_path = FOKS_URL . '/logs/foks_import.xml';
                Import::importData( $file_path );
                Export::generateXML();
            }
        }
        
        
    }
