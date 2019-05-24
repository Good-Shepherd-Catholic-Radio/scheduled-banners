<?php
/**
 * Class CPT_GSCR_Scheduled_Banners
 *
 * Creates the post type.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CPT_GSCR_Scheduled_Banners extends RBM_CPT {

	public $post_type = 'scheduled-banner';
	public $label_singular = null;
	public $label_plural = null;
	public $labels = array();
	public $icon = 'editor-kitchensink';
	public $post_args = array(
		'public' => false,
		'hierarchical' => false,
		'supports' => array( 'title', 'editor', 'author' ),
		'has_archive' => false,
		'rewrite' => array(
			'slug' => 'scheduled-banner',
			'with_front' => false,
			'feeds' => false,
			'pages' => true
		),
		'menu_position' => 11,
		'capability_type' => 'post',
	);

	/**
	 * CPT_GSCR_Scheduled_Banners constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// This allows us to Localize the Labels
		$this->label_singular = __( 'Scheduled Banner', 'scheduled-banners' );
		$this->label_plural = __( 'Scheduled Banners', 'scheduled-banners' );

		$this->labels = array(
			'menu_name' => __( 'Scheduled Banners', 'scheduled-banners' ),
			'all_items' => __( 'All Scheduled Banners', 'scheduled-banners' ),
		);

		parent::__construct();
		
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'admin_column_add' ) );
		
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'admin_column_display' ), 10, 2 );
		
		add_action( 'init', array( $this, 'auto_draft_expired' ) );
		
	}
	
	/**
	 * Add Meta Box
	 * 
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		
		add_meta_box(
			'scheduled-banner-meta',
			sprintf( __( '%s Meta', 'scheduled-banners' ), $this->label_singular ),
			array( $this, 'metabox_content' ),
			$this->post_type,
			'side',
			'high'
		);
		
	}
	
	/**
	 * Add Meta Field
	 * 
	 * @since 1.0.0
	 */
	public function metabox_content() {

		// minDate and maxDate must be offset strings
		// https://jqueryui.com/datepicker/#min-max
		
		rbm_do_field_datetimepicker(
			'scheduled_banner_expires',
			__( 'Expire Time', 'scheduled-banners' ),
			false,
			array(
				'description' => __( 'At this time, the Banner will be set to a Draft and will no longer show on the site.', 'scheduled-banners' ),
				'datetimepicker_args' => array(
					'minDate' => '0',
				),
			)
		);
		
	}
	
	/**
	 * Adds an Admin Column
	 * @param  array $columns Array of Admin Columns
	 * @return array Modified Admin Column Array
	 */
	public function admin_column_add( $columns ) {
		
		$columns['date'] = __( 'Start Time', 'scheduled-banners' );
		$columns['scheduled_banner_expires'] = __( 'Expire Time', 'scheduled-banners' );
		
		return $columns;
		
	}
	
	/**
	 * Displays data within Admin Columns
	 * @param string $column  Admin Column ID
	 * @param integer $post_id Post ID
	 */
	public function admin_column_display( $column, $post_id ) {
		
		switch ( $column ) {
				
			case 'scheduled_banner_expires' :
				echo date_i18n( 'F j, Y @ h:i a', strtotime( rbm_get_field( $column, $post_id ) ) );
				break;
				
		}
		
	}
	
	/**
	 * Auto-drafts a Banner once the Expire datetime passes
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function auto_draft_expired() {
		
		global $post;
		
		$banners = new WP_Query( array( 
			'post_type' => 'scheduled-banner',
			'post_status' => 'publish',
			'posts_per_page' => -1,
		) );
		
		if ( $banners->have_posts() ) : 
		
			while ( $banners->have_posts() ) : $banners->the_post();
		
				$expires = rbm_get_field( 'scheduled_banner_expires', get_the_ID() );
		
				if ( ! $expires ) continue;
		
				$expires_datetime = new DateTime( $expires );
		
				if ( current_time( 'Y-m-d H:i:s' ) > $expires_datetime->format( 'Y-m-d H:i:s' ) ) {
					
					$post->post_status = 'draft';
					
					wp_update_post( $post );
					
				}
		
			endwhile;
		
			wp_reset_postdata();
		
		endif;
		
	}
	
}

$instance = new CPT_GSCR_Scheduled_Banners();