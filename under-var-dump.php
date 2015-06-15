<?php
/*
Plugin Name: Under Var Dump
Plugin URI: 
Description: 
Author: Toshimichi Mimoto
Version: 1.1
Author URI: http://mimosafa.me/
*/

if ( ! function_exists( '_var_dump' ) ) {
	/**
	 * Dump function - Wrap Under_Var_Dump::var_dump()
	 *
	 * @access public
	 *
	 * @param  mixed   $var
	 * @param  integer $back_to (Optional) What number of finding backtrace
	 * @return (void)
	 */
	function _var_dump( $var, $back_to = 1 ) {
		Under_Var_Dump::var_dump( $var, $back_to );
	}
}

if ( ! class_exists( 'Under_Var_Dump' ) ) {

	/**
	 * Plugin class
	 */
	class Under_Var_Dump {

		/**
		 * @access private
		 * @var    array
		 */
		private static $vars = array();

		/**
		 * Get instance - Singleton pattern
		 *
		 * @access public
		 * @return Under_Var_Dump
		 */
		public static function getInstance() {
			static $instance = null;
			$class = __CLASS__;
			return $instance ?: $instance = new $class();
		}

		/**
		 * Constructor
		 *
		 * @access private
		 */
		private function __construct() {
			if ( is_admin() ) {
				$html_hook = 'admin_footer';
				$css_hook  = 'admin_enqueue_scripts';
			} else {
				$html_hook = 'wp_footer';
				$css_hook  = 'wp_enqueue_scripts';
			}
			add_action( $html_hook, array( $this, 'display_vars' ) );
			add_action( $css_hook,  array( $this, 'scripts' ) );
		}

		/**
		 * Dump method
		 *
		 * @access public
		 *
		 * @param  mixed   $var
		 * @param  integer $back_to (Optional) What number of finding backtrace
		 * @return (void)
		 */
		public static function var_dump( $var, $back_to = 1 ) {
			if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG )
				return;

			if ( ! absint( $back_to ) || 1 > $back_to )
				return;

			$self = self::getInstance();
			$vars = array( 'var' => $var, 'backtrace' => array() );
			$debug_backtrace = version_compare( PHP_VERSION, '5.2.5', '>=' ) ? debug_backtrace( false ) : debug_backtrace();

			foreach ( $debug_backtrace as $arg ) {
				if ( $arg['file'] === __FILE__ )
					continue;

				$vars['backtrace'][] = array( $arg['file'], $arg['line'] );
				$back_to--;
				if ( !$back_to )
					break;
			}
			self::$vars[] = $vars;
		}

		/**
		 * Display dump results
		 *
		 * @access public
		 */
		public function display_vars() {
			if ( ! is_super_admin() || ! self::$vars )
				return;
			echo '<div class="message" id="under-var-dump-message">' . "\n";
			foreach ( self::$vars as $vars ) {
				$i = 0;
	?>
	<div>
		<p><b>Variable</b></p>
		<pre><?php esc_html_e( var_export( $vars['var'], true ) ); ?></pre>
		<?php foreach ( $vars['backtrace'] as $array ) { ?>
		<hr>
		<dl>
		<dt>#</dt>
			<dd><?php echo ++$i; ?></dd>
			<dt>File</dt>
			<dd><code><?php echo $array[0]; ?></code></dd>
			<dt>Line</dt>
			<dd><?php echo $array[1]; ?></dd>
		</dl>
		<?php } ?>
	</div>
	<?php
			}
			echo '<a href="#" id="close-under-var-dump-message">x</a>' . "\n";
			echo '</div>' . "\n";
		}

		public function scripts() {
			if ( ! is_super_admin() )
				return;

			wp_enqueue_style( 'under-var-dump', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), '', 'screen' );
			wp_enqueue_script( 'under-var-dump', plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), '', true );
		}

	}

	/**
	 * Plugin class initialize
	 */
	Under_Var_Dump::getInstance();

}
