<?php
/**
 * Plugin Name: GSCR Scheduled Banners
 * Plugin URI: https://github.com/Good-Shepherd-Catholic-Radio/scheduled-banners
 * Description: Shows scheduled banners on the home page
 * Version: 1.0.1
 * Text Domain: scheduled-banners
 * Author: Eric Defore
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 * GitHub Plugin URI: Good-Shepherd-Catholic-Radio/scheduled-banners
 * GitHub Branch: develop
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Scheduled_Banners' ) ) {

	/**
	 * Main Scheduled_Banners class
	 *
	 * @since	  1.0.0
	 */
	class Scheduled_Banners {
		
		/**
		 * @var			Scheduled_Banners $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			Scheduled_Banners $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true Scheduled_Banners
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'scheduled-banners' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			if ( ! class_exists( 'RBM_CPTS' ) ||
				! class_exists( 'RBM_FieldHelpers' ) ) {
				
				$this->admin_errors[] = sprintf( _x( 'To use the %s Plugin, both %s and %s must be active as either a Plugin or a Must Use Plugin!', 'Missing Dependency Error', 'scheduled-banners' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//github.com/realbig/rbm-field-helpers/" target="_blank">' . __( 'RBM Field Helpers', 'scheduled-banners' ) . '</a>', '<a href="//github.com/realbig/rbm-cpts/" target="_blank">' . __( 'RBM Custom Post Types', 'scheduled-banners' ) . '</a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			
			add_action( 'gscr_before_content', array( $this, 'add_banners' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'Scheduled_Banners_VER' ) ) {
				// Plugin version
				define( 'Scheduled_Banners_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'Scheduled_Banners_DIR' ) ) {
				// Plugin path
				define( 'Scheduled_Banners_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'Scheduled_Banners_URL' ) ) {
				// Plugin URL
				define( 'Scheduled_Banners_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'Scheduled_Banners_FILE' ) ) {
				// Plugin File
				define( 'Scheduled_Banners_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = Scheduled_Banners_DIR . '/languages/';
			$lang_dir = apply_filters( 'scheduled_banners_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'scheduled-banners' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'scheduled-banners', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/scheduled-banners/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/scheduled-banners/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'scheduled-banners', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/scheduled-banners/languages/ folder
				load_textdomain( 'scheduled-banners', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'scheduled-banners', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			require_once __DIR__ . '/core/cpt/class-scheduled-banners-cpt.php';
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'scheduled-banners',
				Scheduled_Banners_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Scheduled_Banners_VER
			);
			
			wp_register_script(
				'scheduled-banners',
				Scheduled_Banners_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Scheduled_Banners_VER,
				true
			);
			
			wp_localize_script( 
				'scheduled-banners',
				'scheduledBanners',
				apply_filters( 'scheduled_banners_localize_script', array() )
			);
			
			wp_register_style(
				'scheduled-banners-admin',
				Scheduled_Banners_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Scheduled_Banners_VER
			);
			
			wp_register_script(
				'scheduled-banners-admin',
				Scheduled_Banners_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Scheduled_Banners_VER,
				true
			);
			
			wp_localize_script( 
				'scheduled-banners-admin',
				'scheduledBanners',
				apply_filters( 'scheduled_banners_localize_admin_script', array() )
			);
			
		}
		
		public function wp_enqueue_scripts() {
			
			wp_enqueue_style( 'scheduled-banners' );
			
		}
		
		/**
		 * Add banner above content on the Home Page
		 * 
		 * @access		public
		 * @since		1.0.0
		 * @return		void
		 */
		public function add_banners() {
			
			if ( ! is_front_page() ) return false;
			
			global $post;
		
			$banners = new WP_Query( array( 
				'post_type' => 'scheduled-banner',
				'post_status' => 'publish',
				'posts_per_page' => -1,
			) );

			if ( $banners->have_posts() ) : 
			
				// If this file exists in the Theme, load it there instead
				if ( ! $template = locate_template( array( '/gscr-scheduled-banners/html-banner.php' ) ) ) {
					$template = Scheduled_Banners_DIR . 'core/views/html-banner.php';
				}

				while ( $banners->have_posts() ) : $banners->the_post();

					include $template;

				endwhile;

				wp_reset_postdata();

			endif;
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true Scheduled_Banners
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \Scheduled_Banners The one true Scheduled_Banners
 */
add_action( 'plugins_loaded', 'scheduled_banners_load' );
function scheduled_banners_load() {

	require_once __DIR__ . '/core/scheduled-banners-functions.php';
	SCHEDULEDPOPUPS();

}
