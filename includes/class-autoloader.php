<?php
namespace WCTE;

defined( 'ABSPATH' ) || exit;

final class Autoloader {
	public static function register() {
		spl_autoload_register(
			static function ( $class ) {
				if ( 0 !== strpos( $class, __NAMESPACE__ . '\\' ) ) {
					return;
				}
				$name = strtolower( str_replace( array( __NAMESPACE__ . '\\', '_', '\\' ), array( '', '-', '/' ), $class ) );
				$file = WCTE_PATH . 'includes/class-' . $name . '.php';
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		);
	}
}
