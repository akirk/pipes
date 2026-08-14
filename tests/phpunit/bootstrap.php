<?php

namespace WpApp {
    if ( ! class_exists( BaseApp::class ) ) {
        abstract class BaseApp {}
    }

    if ( ! class_exists( WpApp::class ) ) {
        class WpApp {
            public function __construct( string $template_dir, string $url_path, array $args = [] ) {}
        }
    }
}

namespace {
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', sys_get_temp_dir() . '/wordpress/' );
    }

    function __( $text, $domain = 'default' ) {
        return $text;
    }

    function esc_html__( $text, $domain = 'default' ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }

    function esc_html( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }

    function wp_json_encode( $value, $flags = 0, $depth = 512 ) {
        return json_encode( $value, $flags, $depth );
    }

    function wp_register_ability( $ability_id, $args ) {
        $GLOBALS['pipes_test_registered_abilities'][ $ability_id ] = $args;
    }

    $autoload = dirname( __DIR__, 2 ) . '/vendor/autoload.php';
    if ( file_exists( $autoload ) ) {
        require_once $autoload;
    } else {
        require_once dirname( __DIR__, 2 ) . '/src/App.php';
    }
}
