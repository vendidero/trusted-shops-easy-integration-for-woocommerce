<?php

namespace Vendidero\TrustedShopsEasyIntegration;

defined( 'ABSPATH' ) || exit;

class TrstdLogin {

	const BLOCK_NAME = 'ts-easy-integration/trstd-login';

	const STORE_NAMESPACE = 'ts-easy-integration/trstd-login';

	const TREE_KEY = 'tsTrstdLoginTreeId';

	const REGION_KEY = 'tsTrstdLoginRegion';

	/**
	 * Number of elements rendered during the current request.
	 *
	 * @var int
	 */
	protected static $rendered = 0;

	/**
	 * Whether the integration script has been printed.
	 *
	 * @var bool
	 */
	protected static $script_printed = false;

	/**
	 * Incrementing id of root block trees.
	 *
	 * @var int
	 */
	protected static $tree_id = 0;

	/**
	 * Stack of root block trees being rendered including the auto-insert anchor detected within each tree.
	 *
	 * @var array
	 */
	protected static $trees = array();

	/**
	 * Number of header, footer and main blocks currently being rendered.
	 *
	 * @var int[]
	 */
	protected static $region_depth = array();

	/**
	 * Whether the first top-level block of the (block) template has been rendered.
	 *
	 * @var bool
	 */
	protected static $template_root_seen = false;

	/**
	 * @var null|bool
	 */
	protected static $is_enabled = null;

	public static function init() {
		self::register_block();

		if ( self::is_storefront_request() ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
			add_action( 'wp_head', array( __CLASS__, 'print_script' ), 5 );

			/**
			 * Automatic attachment relies on the $parent_block argument of render_block_data
			 * which is available since WP 5.9 (the version introducing block themes).
			 */
			if ( version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) ) {
				add_filter( 'render_block_data', array( __CLASS__, 'on_render_block_data' ), PHP_INT_MAX, 3 );
				add_filter( 'render_block', array( __CLASS__, 'on_render_block' ), 10, 2 );
			}

			/**
			 * Allow developers (e.g. classic themes) to output the element within custom locations:
			 * do_action( 'ts_easy_integration_trstd_login' );
			 */
			add_action( 'ts_easy_integration_trstd_login', array( __CLASS__, 'output' ) );
		}
	}

	protected static function is_storefront_request() {
		return ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! ( function_exists( 'WC' ) && is_callable( array( WC(), 'is_rest_api_request' ) ) && WC()->is_rest_api_request() );
	}

	/**
	 * Whether #trstd login is enabled for the current sales channel.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! is_null( self::$is_enabled ) ) {
			return self::$is_enabled;
		}

		$is_enabled = (bool) apply_filters( 'ts_easy_integration_trstd_login_enabled', Package::is_trstd_login_enabled() );

		// Do only cache the result as soon as the main query (and thus the current sales channel, e.g. language) has been setup.
		if ( did_action( 'wp' ) ) {
			self::$is_enabled = $is_enabled;
		}

		return $is_enabled;
	}

	/**
	 * Register the block from block.json. Assets are built into /dist/blocks/trstd-login.
	 *
	 * @return void
	 */
	protected static function register_block() {
		if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
			return;
		}

		$asset_url = Package::get_assets_url() . '/blocks/trstd-login';

		// The editor script is only needed within the (site) editor - do not register it for storefront requests.
		if ( is_admin() ) {
			wp_register_script( 'ts-easy-integration-trstd-login-editor', $asset_url . '/index.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n' ), Package::get_version(), true );

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'ts-easy-integration-trstd-login-editor', 'trusted-shops-easy-integration-for-woocommerce', Package::get_path() . '/i18n/languages' );
			}
		}

		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module( self::STORE_NAMESPACE, $asset_url . '/view.js', array( '@wordpress/interactivity' ), Package::get_version() );
		}

		register_block_type_from_metadata(
			Package::get_path() . '/assets/blocks/trstd-login',
			array(
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * Layout-only styles, scoped to our own wrapper. The element's appearance belongs to Trusted Shops.
	 *
	 * @return string
	 */
	protected static function get_inline_css() {
		$height = self::get_reserved_height();

		/**
		 * Reserve the space of the (default) #trstd login toggle while the integration script loads to prevent the header from jumping.
		 * The toggle measures (height * 38 / 24 + 2 * 1px rail) x (height + 2 * 1px rail) with a default height of 34px.
		 * In case a custom toggle height is used, themes may adjust the reserved space via the --ts-trstd-login-height custom property.
		 */
		$css = '.ts-trstd-login-hd-wrapper{display:inline-flex;align-items:center;justify-content:center;align-self:center;flex:0 0 auto;width:auto;margin:0 10px;}';

		/**
		 * Inner container with a default horizontal padding (similar to the Woo customer account block).
		 * The padding uses :where() to keep specificity at zero which allows themes to easily override the default,
		 * e.g. via the --ts-trstd-login-padding-x custom property.
		 *
		 * The reserved toggle space (including the padding) uses border-box sizing with a higher specificity
		 * to make sure the reservation is not affected by global box-sizing rules of themes.
		 */
		$css .= ':where(.ts-trstd-login-hd-wrapper .ts-trstd-login-hd-wrapper__inner){padding:0 var(--ts-trstd-login-padding-x,.5em);}';
		$css .= '.ts-trstd-login-hd-wrapper .ts-trstd-login-hd-wrapper__inner{display:flex;align-items:center;justify-content:center;box-sizing:border-box;'
			. 'min-width:calc(var(--ts-trstd-login-height,' . esc_attr( $height ) . ') * 38 / 24 + 2px + var(--ts-trstd-login-padding-x,.5em) * 2);'
			. 'min-height:calc(var(--ts-trstd-login-height,' . esc_attr( $height ) . ') + 2px);}';

		/**
		 * Remove the padding on small screens to keep the header icon row on a single line.
		 * Uses a zero specificity selector so that themes may still set a custom padding.
		 * Note: Use 0px instead of 0 as unitless zeros are not allowed within calc() additions.
		 */
		$css .= '@media (max-width:600px){:where(.ts-trstd-login-hd-wrapper){--ts-trstd-login-padding-x:0px;}}';

		return $css;
	}

	/**
	 * The (default) height of the #trstd login toggle used to reserve space within the header.
	 *
	 * @return string
	 */
	protected static function get_reserved_height() {
		$height = apply_filters( 'ts_easy_integration_trstd_login_reserved_height', '34px' );

		if ( ! is_string( $height ) || ! preg_match( '/^\d+(\.\d+)?(px|em|rem)$/', $height ) ) {
			$height = '34px';
		}

		return $height;
	}

	public static function enqueue_styles() {
		if ( ! self::is_enabled() ) {
			return;
		}

		wp_register_style( 'ts-easy-integration-trstd-login', false, array(), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_add_inline_style( 'ts-easy-integration-trstd-login', self::get_inline_css() );
		wp_enqueue_style( 'ts-easy-integration-trstd-login' );
	}

	/**
	 * Print the integration script exactly once per full page render.
	 *
	 * @return void
	 */
	public static function print_script() {
		if ( self::$script_printed || ! self::is_enabled() || ( function_exists( 'is_embed' ) && is_embed() ) ) {
			return;
		}

		$script = Package::get_trstd_login_script_html();

		if ( ! empty( $script ) ) {
			self::$script_printed = true;

			echo "<!-- added by Trusted Shops app: Start -->\n" . $script . "\n<!-- End -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Returns the element HTML.
	 *
	 * @param string $wrapper_attributes Escaped HTML attributes for the wrapper.
	 *
	 * @return string
	 */
	public static function get_element_html( $wrapper_attributes = '' ) {
		if ( ! self::is_enabled() ) {
			return '';
		}

		$args = array(
			'element_tag' => Package::get_trstd_login_element_tag(),
		);

		if ( ! empty( $wrapper_attributes ) ) {
			$args['wrapper_attributes'] = $wrapper_attributes;
		}

		++self::$rendered;

		return Package::get_template_html( 'trstd-login/element.php', $args );
	}

	/**
	 * Output the element, e.g. via do_action( 'ts_easy_integration_trstd_login' ).
	 *
	 * @return void
	 */
	public static function output() {
		echo self::get_element_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Block render callback.
	 *
	 * @param array     $attributes
	 * @param string    $content
	 * @param \WP_Block $block
	 *
	 * @return string
	 */
	public static function render_block( $attributes, $content = '', $block = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! self::is_enabled() ) {
			return '';
		}

		$extra_attributes = array(
			'class'    => 'ts-trstd-login-hd-wrapper',
			'data-app' => 'TrustedShopsApp',
		);

		/**
		 * The Interactivity API (WP 6.5+) is an optional enhancement only. Without it, the plain wrapper is rendered,
		 * the element itself is controlled by the Trusted Shops integration script either way.
		 */
		$supports_interactivity = function_exists( 'wp_interactivity_data_wp_context' ) && function_exists( 'wp_enqueue_script_module' ) && apply_filters( 'ts_easy_integration_trstd_login_use_interactivity', true );

		if ( $supports_interactivity ) {
			$extra_attributes = array_merge(
				$extra_attributes,
				array(
					'data-wp-interactive'     => self::STORE_NAMESPACE,
					'data-wp-init'            => 'callbacks.init',
					'data-wp-class--is-ready' => 'context.isReady',
				)
			);
		}

		$wrapper_attributes = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes( $extra_attributes ) : self::build_attributes( $extra_attributes );

		if ( $supports_interactivity ) {
			$wrapper_attributes .= ' ' . wp_interactivity_data_wp_context(
				array(
					'isReady' => false,
					'tag'     => Package::get_trstd_login_element_tag(),
				)
			);

			wp_enqueue_script_module( self::STORE_NAMESPACE );
		}

		return self::get_element_html( $wrapper_attributes );
	}

	protected static function build_attributes( $attributes ) {
		$html = array();

		foreach ( $attributes as $name => $value ) {
			$html[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $html );
	}

	/**
	 * Anchor blocks (in order of priority) the #trstd login block is automatically attached to,
	 * including the position relative to the anchor.
	 *
	 * @return array
	 */
	protected static function get_anchor_blocks() {
		return (array) apply_filters(
			'ts_easy_integration_trstd_login_anchor_blocks',
			array(
				'woocommerce/customer-account' => 'after',
				'woocommerce/mini-cart'        => 'before',
				/**
				 * Fallback for headers without account/cart icons (e.g. simplified cart/checkout headers):
				 * Attach after the (header) navigation, which is the location Woo hooks its own header blocks to.
				 */
				'core/navigation'              => 'after',
			)
		);
	}

	protected static function auto_insert_enabled() {
		return apply_filters( 'ts_easy_integration_trstd_login_auto_insert', true ) && self::is_enabled();
	}

	/**
	 * Recursively scan a parsed block tree to detect whether the #trstd login block has been placed manually
	 * and which anchor blocks are available. Footer and main content sections are skipped entirely.
	 *
	 * @param array $blocks
	 * @param bool  $in_header
	 * @param array $found
	 *
	 * @return array
	 */
	protected static function scan_blocks( $blocks, $in_header = false, $found = array() ) {
		$found = wp_parse_args(
			$found,
			array(
				'has_block'    => false,
				'has_header'   => false,
				'names'        => array(),
				'header_names' => array(),
			)
		);

		foreach ( $blocks as $block ) {
			$name   = isset( $block['blockName'] ) ? $block['blockName'] : '';
			$region = self::get_block_region( $block );

			// Never attach to blocks placed within the footer or the main content.
			if ( in_array( $region, array( 'footer', 'main' ), true ) ) {
				continue;
			}

			$block_in_header = $in_header || 'header' === $region;

			if ( 'header' === $region ) {
				$found['has_header'] = true;
			}

			if ( self::BLOCK_NAME === $name ) {
				$found['has_block'] = true;
			} elseif ( ! empty( $name ) ) {
				$found['names'][ $name ] = true;

				if ( $block_in_header ) {
					$found['header_names'][ $name ] = true;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = self::scan_blocks( $block['innerBlocks'], $block_in_header, $found );
			}
		}

		return $found;
	}

	/**
	 * Detect the page region a parsed block represents: header, footer, main (content) or none.
	 *
	 * @param array $block
	 *
	 * @return string
	 */
	protected static function get_block_region( $block ) {
		$name   = isset( $block['blockName'] ) ? $block['blockName'] : '';
		$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$region = '';

		if ( isset( $attrs['tagName'] ) && in_array( $attrs['tagName'], array( 'header', 'footer', 'main' ), true ) ) {
			$region = $attrs['tagName'];
		} elseif ( 'core/post-content' === $name ) {
			$region = 'main';
		} elseif ( in_array( $name, array( 'core/template-part', 'core/pattern' ), true ) ) {
			$slug = isset( $attrs['slug'] ) && is_string( $attrs['slug'] ) ? strtolower( $attrs['slug'] ) : '';

			if ( 'core/template-part' === $name && ! empty( $attrs['area'] ) && in_array( $attrs['area'], array( 'header', 'footer' ), true ) ) {
				$region = $attrs['area'];
			} elseif ( false !== strpos( $slug, 'footer' ) ) {
				$region = 'footer';
			} elseif ( false !== strpos( $slug, 'header' ) ) {
				$region = 'header';
			}
		}

		return apply_filters( 'ts_easy_integration_trstd_login_block_region', $region, $block );
	}

	protected static function is_block_theme() {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	protected static function get_region_depth( $region ) {
		return isset( self::$region_depth[ $region ] ) ? self::$region_depth[ $region ] : 0;
	}

	protected static function is_header_context( $require_header = false ) {
		if ( self::get_region_depth( 'footer' ) > 0 || self::get_region_depth( 'main' ) > 0 ) {
			return false;
		}

		return ! $require_header || self::get_region_depth( 'header' ) > 0;
	}

	protected static function get_anchor_by_priority( $names ) {
		foreach ( array_keys( self::get_anchor_blocks() ) as $anchor ) {
			if ( isset( $names[ $anchor ] ) ) {
				return $anchor;
			}
		}

		return '';
	}

	/**
	 * Track root block trees. Whenever a new root tree (e.g. the content of a template part or pattern) starts rendering,
	 * detect the anchor block to attach the #trstd login block to. The root block is marked with a tree id
	 * to detect when the tree finished rendering.
	 *
	 * The block is only ever attached within the header:
	 * - within a header template part/pattern or an element using the header tag or
	 * - within the very first top-level block of a block template (e.g. custom templates with an inline header group)
	 * Footers and main content are always excluded.
	 *
	 * @param array          $parsed_block
	 * @param array          $source_block
	 * @param \WP_Block|null $parent_block
	 *
	 * @return array
	 */
	public static function on_render_block_data( $parsed_block, $source_block = array(), $parent_block = null ) {
		if ( ! is_array( $parsed_block ) ) {
			return $parsed_block;
		}

		$region = self::get_block_region( $parsed_block );

		if ( ! empty( $region ) ) {
			self::$region_depth[ $region ]    = self::get_region_depth( $region ) + 1;
			$parsed_block[ self::REGION_KEY ] = $region;
		}

		if ( null === $parent_block ) {
			$is_template_root = empty( self::$trees ) && ! self::$template_root_seen && self::is_block_theme();
			$tree             = array(
				'id'             => ++self::$tree_id,
				'anchor'         => '',
				'require_header' => false,
			);

			if ( empty( self::$trees ) && self::is_block_theme() ) {
				self::$template_root_seen = true;
			}

			if ( self::$rendered <= 0 && self::is_header_context() && self::auto_insert_enabled() ) {
				$found = self::scan_blocks( array( $parsed_block ) );

				if ( ! $found['has_block'] ) {
					if ( self::get_region_depth( 'header' ) > 0 ) {
						// The tree is part of the header, e.g. the header template part content.
						$tree['anchor'] = self::get_anchor_by_priority( $found['names'] );
					} elseif ( $found['has_header'] ) {
						// The tree contains a header section - restrict to anchors within that section.
						$tree['anchor']         = self::get_anchor_by_priority( $found['header_names'] );
						$tree['require_header'] = true;
					} elseif ( $is_template_root && empty( $region ) ) {
						// First top-level block of the template without explicit header markup, e.g. an inline header group.
						$tree['anchor'] = self::get_anchor_by_priority( $found['names'] );
					}
				}
			}

			$parsed_block[ self::TREE_KEY ] = $tree['id'];
			self::$trees[]                  = $tree;
		}

		return $parsed_block;
	}

	/**
	 * Attach the #trstd login block to the detected anchor block.
	 *
	 * @param string    $block_content
	 * @param array     $block
	 * @param \WP_Block $instance
	 *
	 * @return string
	 */
	public static function on_render_block( $block_content, $block ) {
		if ( isset( $block[ self::REGION_KEY ] ) ) {
			self::$region_depth[ $block[ self::REGION_KEY ] ] = max( 0, self::get_region_depth( $block[ self::REGION_KEY ] ) - 1 );
		}

		$tree_index = count( self::$trees ) - 1;

		if ( $tree_index < 0 ) {
			return $block_content;
		}

		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';
		$tree       = self::$trees[ $tree_index ];

		if ( ! empty( $tree['anchor'] ) && $tree['anchor'] === $block_name && self::$rendered <= 0 && ! empty( $block_content ) && self::is_header_context( $tree['require_header'] ) ) {
			// Prevent attaching multiple times within the same tree.
			self::$trees[ $tree_index ]['anchor'] = '';

			$anchors  = self::get_anchor_blocks();
			$position = isset( $anchors[ $block_name ] ) && 'before' === $anchors[ $block_name ] ? 'before' : 'after';
			$html     = render_block(
				array(
					'blockName'    => self::BLOCK_NAME,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			);

			if ( ! empty( $html ) ) {
				$block_content = 'before' === $position ? $html . $block_content : $block_content . $html;
			}
		}

		// The root block of a tree finished rendering - remove the tree (and any orphans on top of it) from the stack.
		if ( isset( $block[ self::TREE_KEY ] ) ) {
			foreach ( array_reverse( self::$trees, true ) as $index => $tree ) {
				if ( $tree['id'] === $block[ self::TREE_KEY ] ) {
					self::$trees = array_slice( self::$trees, 0, $index );
					break;
				}
			}
		}

		return $block_content;
	}
}
