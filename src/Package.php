<?php

namespace Vendidero\TrustedShopsEasyIntegration;

use Vendidero\TrustedShopsEasyIntegration\Admin\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '0.0.1-alpha';

	public static function init() {
		if ( ! self::has_dependencies() ) {
			if ( ! self::is_integration() ) {
				add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			}

			return;
		}

		self::init_hooks();

		if ( is_admin() ) {
			Helper::init();
            Ajax::init();
		}

        add_action( 'admin_init', function() {

        });

		self::includes();

		do_action( 'ts_easy_integration_init' );
	}

	public static function dependency_notice() {
		?>
		<div class="error notice notice-error"><p><?php _ex( 'To use the Trusted Shops Easy Integration for WooCommerce plugin please make sure that WooCommerce is installed and activated.', 'trusted-shops', 'trusted-shops-easy-integration' ); ?></p></div>
		<?php
	}

	public static function has_dependencies() {
		return ( class_exists( 'WooCommerce' ) );
	}

	protected static function init_hooks() {
		if ( ! self::is_integration() ) {
			add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
		}
	}

	public static function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'trusted-shops-easy-integration' );

		unload_textdomain( 'trusted-shops-easy-integration' );
		load_textdomain( 'trusted-shops-easy-integration', trailingslashit( WP_LANG_DIR ) . 'trusted-shops-easy-integration/trusted-shops-easy-integration-' . $locale . '.mo' );
		load_plugin_textdomain( 'trusted-shops-easy-integration', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
	}

	public static function install() {
		self::init();
        Install::install();
	}

	/**
     * Whether debug mode is enabled or not.
     *
     * @TODO Disable before release.
     *
	 * @return bool
	 */
	public static function is_debug_mode() {
		return true;
	}

	/**
     * Whether this installation is an integration (e.g. bundled within Germanized) or standalone.
     *
     * @TODO Need to check for Germanized existence.
     *
	 * @return false
	 */
	public static function is_integration() {
		return false;

		return class_exists( 'WooCommerce_Germanized' ) ? true : false;
	}

	/**
     * Log via the Woo file logger if WP_DEBUG is enabled.
     *
	 * @param $message
	 * @param $type
	 *
	 * @return bool
	 */
	public static function log( $message, $type = 'info' ) {
		$enable_logging = defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false;

		if ( ! apply_filters( 'ts_easy_integration_enable_logging', $enable_logging ) ) {
			return false;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return false;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'ts-easy-integration' ) );

        return true;
	}

	private static function includes() {
		// include_once self::get_path() . '/includes/';
	}

	private static function is_frontend_request() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}

    public static function get_system_version() {
        return self::is_integration() ? get_option( 'woocommerce_gzd_version' ) : get_option( 'woocommerce_version' );
    }

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/dist';
	}

	public static function get_lib_assets_url() {
		return 'https://static-app.connect' . ( self::is_debug_mode() ? '-qa' : '' ) . '.trustedshops.com';
	}

    public static function get_ts_locale( $locale ) {
	    $ts_locale = strtolower( substr( $locale, 0, 2 ) );

	    if ( ! in_array( $ts_locale, array(
		    'de',
		    'en',
		    'es',
		    'fr',
		    'it',
		    'nl',
		    'pt',
		    'pl'
	    ) ) ) {
		    $ts_locale = 'en';
	    }

        return $ts_locale;
    }

	/**
     * Returns available sale channels.
     *
	 * @return array[]
	 */
    public static function get_sale_channels() {
        return apply_filters( 'ts_sale_channels', array(
            'main' => array(
                'id'     => 'main',
                'name'   => get_bloginfo( 'name' ),
                'url'    => get_bloginfo( 'url' ),
                'locale' => self::get_ts_locale( get_locale() ),
            ),
        ) );
    }

	/**
     * Retrieves a sale channel by id.
     *
	 * @param $id
	 *
	 * @return array|false
	 */
    public static function get_sale_channel( $id ) {
        $channels = self::get_sale_channels();

        if ( array_key_exists( $id, $channels ) ) {
            return $channels[ $id ];
        }

        return false;
    }

	/**
     * Get TS mapped channels.
     *
	 * @return array
	 */
    public static function get_channels() {
        $channels = array_filter( (array) self::get_setting( 'channels', array() ) );

        if ( ! empty( $channels ) ) {
            foreach( $channels as $key => $channel ) {
	            /**
	             * Merge channel data with current (subject to change) sale channel data.
	             */
                if ( $sale_channel = self::get_sale_channel( $channel->salesChannelRef ) ) {
                    $channels[ $key ]->salesChannelLocale = $sale_channel['locale'];
	                $channels[ $key ]->salesChannelName   = $sale_channel['name'];
	                $channels[ $key ]->salesChannelUrl    = $sale_channel['url'];
                }
            }
        }

        return $channels;
    }

    public static function get_trustbadges() {
	    $trustbadges = array_filter( (array) self::get_setting( 'trustbadges', array() ) );

        return $trustbadges;
    }

    public static function get_trustbadge( $sale_channel = 'main' ) {
        $trustbadges = self::get_trustbadges();
        $trustbadge  = array_key_exists( $sale_channel, $trustbadges ) ? $trustbadges[ $sale_channel ] : false;

        return $trustbadge;
    }

    public static function get_enable_review_invites() {
	    $invites = array_filter( (array) self::get_setting( 'enable_invites', array() ) );

        return $invites;
    }

	public static function enable_review_invites( $sale_channel = 'main' ) {
		$invites = self::get_enable_review_invites();

        return in_array( $sale_channel, $invites ) ? true : false;
	}

	public static function get_widgets( $sale_channel = 'main' ) {
		$widgets = array_filter( (array) self::get_setting( 'widgets', array() ) );

		if ( ! empty( $sale_channel ) ) {
			$widgets = array_key_exists( $sale_channel, $widgets ) ? $widgets[ $sale_channel ] : array();

            if ( ! empty( $widgets ) ) {
                $widgets = isset( $widgets->children ) ? $widgets->children[0]->children : array();
            }
		}

		return $widgets;
	}

	/**
     * Returns TS settings.
     *
	 * @param $name
	 * @param $default
	 *
	 * @return mixed
	 */
	public static function get_setting( $name, $default = false ) {
		$option_name  = "ts_easy_integration_{$name}";
		$option_value = get_option( $option_name, $default );

		if ( ! empty( $option_value ) && in_array( $name, array( 'client_id', 'client_secret' ) ) ) {
			$option_value = SecretsHelper::decrypt( $option_value );

            if ( is_wp_error( $option_value ) ) {
                $option_value = '';
            }
		}

		return $option_value;
	}

	/**
     * Get TS settings.
     *
	 * @return array
	 */
    public static function get_settings() {
        $settings = array(
            'channels'       => self::get_channels(),
            'trustbadges'    => self::get_trustbadges(),
            'widgets'        => self::get_widgets( '' ),
            'enable_invites' => self::get_enable_review_invites(),
        );

        return $settings;
    }

	/**
     * Update a specific TS setting.
     *
	 * @param $name
	 * @param $value
	 *
	 * @return bool|\WP_Error
	 */
	public static function update_setting( $name, $value ) {
		$option_name = "ts_easy_integration_{$name}";

		if ( ! empty( $value ) && in_array( $name, array( 'client_id', 'client_secret' ) ) ) {
			$value = SecretsHelper::encrypt( $value );

			/**
			 * In case encryption fails, returns false.
			 */
            if ( is_wp_error( $value ) ) {
                return $value;
            }
		}

		update_option( $option_name, $value );

        return true;
	}

	/**
     * Deletes all TS settings stored in DB.
     *
	 * @return bool
	 */
    public static function delete_settings() {
	    delete_option( "ts_easy_integration_client_id" );
	    delete_option( "ts_easy_integration_client_secret" );

        foreach( self::get_settings() as $name => $value ) {
	        $option_name  = "ts_easy_integration_{$name}";
	        delete_option( $option_name );
        }

        return true;
    }
}