<?php
/**
 * Created by PhpStorm.
 * User: skipin
 * Date: 06.11.18
 * Time: 15:42
 */

namespace Foks\Helpers;

class AjaxHelper {
	static $front_vars = [];
	static $admin_vars = [];

    public static function declaration_ajax() {
        $class_methods = get_class_methods( get_called_class() );
        foreach ( $class_methods as $name ) {

            $ajax   = strpos( $name, 'ajax' );
            $nopriv = strpos( $name, 'nopriv' );
            $short  = str_replace( 'nopriv_', '', str_replace( 'ajax_', '', $name ) );

            if ( $ajax === 0 ) {
                add_action( 'wp_ajax_' . $short, array( get_called_class(), $name ) );
            }
            if ( $nopriv === 5 ) {
                add_action( 'wp_ajax_nopriv_' . $short, array( get_called_class(), $name ) );
            }
        }
    }

    public static function getPostVar( $var ) {
        return ( isset( $_POST[ $var ] ) ) ? $_POST[ $var ] : false;
    }

    public static function getGetVar( $var ) {
        return ( isset( $_GET[ $var ] ) ) ? $_GET[ $var ] : false;
    }

	public static function localAdminVars() {
		echo "<script>";
		foreach (self::$admin_vars as $key => $value) {
			if ( !is_string($value) ) {
				$value = json_encode($value, JSON_UNESCAPED_UNICODE);
			}

			echo "window.{$key} = {$value};" . "\n";
		}
		echo "</script>";
	}

	public static function localFrontVars() {
		echo "<script>";
		foreach (self::$front_vars as $key => $value) {
			if ( !is_string($value) ) {
				$value = json_encode($value, JSON_UNESCAPED_UNICODE);
			}

			echo "window.{$key} = {$value}" . "\n";
		}
		echo "</script>";
	}

}
