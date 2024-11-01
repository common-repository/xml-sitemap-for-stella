<?php
/**
 * @package Gomo Sitemaps Multiple Lang Admin
 */

if ( !defined( 'GOMO_SFS_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

/**
 *  Class to generate the admin page for the XML Sitemaps for Stella Plugin
 */
class GOMO_Sitemaps_Multiple_Langs_admin {
	// Private variables
	

	/**
	 * Class constructor
	 */
	function __construct() {
		add_action('admin_menu', array( $this, 'gomo_sitemaps_admin_menu') );
		add_action('admin_init', array( $this, 'gomo_sitemaps_register_settings') );
		// hook action whenever a post chnages status
		add_action( 'transition_post_status', array( $this, 'gomo_status_transition' ), 10, 3 );
	}	

	function gomo_sitemaps_admin_menu() {
		add_options_page( 'Sitemaps for Stella', 'Sitemaps for Stella', 'manage_options', 'sitemaps-multiple-languages', array( $this, 'gomo_sitemaps_settings_page' ) );
	}
	
	function gomo_sitemaps_settings_page () {
		?>
		<div class="wrap">
			<?php screen_icon('options-general'); ?><h2>XML Sitemaps for Stella plugin</h2>
			<div class="postbox-container" style="width:70%;margin-right: 5%;min-width:400px;max-width:700px;">
				<?php
				$public = get_option( 'blog_public' );
				if( 0 == $public ) : ?>
					<p style="color: brown;"><strong>Alert:</strong> Don't forget to allow <em>Search Engine Visibility</em> on <a href="<?php echo admin_url( 'options-reading.php' ); ?>">Settings > Reading</a> page</p>
				<?php endif; ?>
			    <form action="options.php" method="POST">
			        <?php settings_fields( 'gomo-sitemaps-settings-group' ); ?>
			        <?php do_settings_sections( 'sitemaps-multiple-languages' ); ?>
			        <?php submit_button(); ?>
			    </form>
			</div>
			
			<div class="postbox-container" style="width:20%; padding-left: 2%;min-width:210px;max-width:210px;border-left: 1px solid #ddd;">
				<a target="_blank" href="http://www.gomo.pt/"><img src="<?php echo GOMO_SFS_URL . '/img/logo_gomo.png'; ?>" alt="GOMO agency logo" /></a>
				<h3>Need help with your website?</h3>
				<p>GOMO is a digital agency specialized in user experience design and WordPress development.</p>
				<br><hr>
				<h3>Like it?</h3>
				<p>Want to help make this plugin even better? Donate now!</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="PM5X7Z8JVF62W">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
				<p>Rate this plugin at <a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/xml-sitemap-for-stella">wordpress.org</a></p>		
			</div>
		</div>
		<?php
	}
	
	
	// Register settings, sections and fields
	
	function gomo_sitemaps_register_settings() {
		
		//register sitemap settings - gomo_sitemap_options
		register_setting( 'gomo-sitemaps-settings-group', 'gomo_sitemap_options', array( $this, 'gomo_settings_sanitize'));
		
		//set defaults
		$options = get_option( 'gomo_sitemap_options' );
		if( !is_array( $options ) ) {
			$options = array();
			$options['enable'] = 1;
			$options['robots'] = 1;
			$options['max_entries'] = 1000;
			$options['home_type'] = 'post';
			$options['exclude_types-attachment'] = 1;
			//$options['exclude_tax-post_tags'] = 0;
			update_option( 'gomo_sitemap_options', $options );
		}
		
		//register settings sections
		add_settings_section( 'gomo-settings-section-general', __( 'General Settings' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_section_general'), 'sitemaps-multiple-languages' );
		add_settings_section( 'gomo-settings-section-exclude', __( 'Exclude from sitemaps' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_section_exclude'), 'sitemaps-multiple-languages' );
				
		//register settings fields
		add_settings_field( 'gomo-settings-enable', __( 'Enable XML Sitemaps engine' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_checkbox_enable'), 'sitemaps-multiple-languages', 'gomo-settings-section-general', array( 'name' => 'gomo_sitemap_options[enable]', 'value' => $options ) );
		add_settings_field( 'gomo-settings-robots', __( 'Include sitemap url in robots.txt' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_checkbox_robots'), 'sitemaps-multiple-languages', 'gomo-settings-section-general', array( 'name' => 'gomo_sitemap_options[robots]', 'value' => $options ) );
		add_settings_field( 'gomo-settings-max_entries', __( 'Max entries per sitemap file' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_text_input' ), 'sitemaps-multiple-languages', 'gomo-settings-section-general' , array( 'name' => 'gomo_sitemap_options[max_entries]', 'value' => $options['max_entries'] ) );
		add_settings_field( 'gomo-settings-home_type', __( 'Include Home in sitemap of' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_home_type' ), 'sitemaps-multiple-languages', 'gomo-settings-section-general' , array( 'value' => $options['home_type'] ) );
		
		add_settings_field( 'gomo-settings-posttypes', __( 'Exclude Post Types' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_exclude_posttypes'), 'sitemaps-multiple-languages', 'gomo-settings-section-exclude', array( 'value' => $options ) );
		add_settings_field( 'gomo-settings-taxonomies', __( 'Exclude Taxonomies' , 'xml-sitemap-for-stella' ), array( $this,'gomo_settings_exclude_taxonomies'), 'sitemaps-multiple-languages', 'gomo-settings-section-exclude', array( 'value' => $options ) );
	}
	
	function gomo_settings_sanitize( $input ) {
		$input['max_entries'] = absint( $input['max_entries'] );
		
		if( $input['max_entries'] > 50000 || $input['max_entries'] == 0 ) {
			$output = get_option( 'gomo_sitemap_options' );
			$input['max_entries'] = $output['max_entries'];
			add_settings_error( 'gomo_sitemap_options', 'invalid-max_entries', __( 'You have enter an invalid maximum number of entries per sitemap file (Maximum is 50.000, default is 1000, however you might want to lower this to prevent memory issues)' , 'xml-sitemap-for-stella' ) );
		}
		
		return $input;
	}
	
	function gomo_settings_section_general() {
		echo esc_html__( 'After enabling XML Sitemaps engine, you do not need to generate the XML Sitemap file manually. Whenever a post/page is published or updated the plugin will ping Bing and Google automatically.' , 'xml-sitemap-for-stella' );
	}
	function gomo_settings_section_exclude() {
		echo esc_html__( "Please check bellow if you don't want to include any of the following content in your Sitemap.", 'xml-sitemap-for-stella' );
	}
	
	
	function gomo_settings_text_input( $args ) {
		$name = esc_attr( $args['name'] );
		$value = esc_attr( $args['value'] );
		echo '<input type="text" name="'. $name.'" value="'. $value.'" style="width: 140px;"/>';
	}
	
	function gomo_settings_checkbox_enable( $args ) {
		$base = $GLOBALS[ 'wp_rewrite' ]->using_index_permalinks() ? 'index.php/' : '';
		$name = esc_attr( $args['name'] );
		$options = $args['value'];
		if( isset( $options['enable'] ) && $options['enable'] == 1 ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		echo '<input type="checkbox" name="'. $name.'" value="1" '. $checked.'/>';
		if( $checked == 'checked' ) {
			echo '<p>'. esc_html__('Find your XML Sitemap', 'xml-sitemap-for-stella' ) .' <a target="_blank" class="button-secondary" href="'. home_url( $base .'sitemap-index.xml' ).'">'. esc_html__( 'here', 'xml-sitemap-for-stella' ) .' ›</a></p>';
		} else {
			echo '<p style="color: brown;"><em>' . __( 'Turn on the engine!' , 'xml-sitemap-for-stella' ) .'</em></p>';
		}
	}
	
	function gomo_settings_checkbox_robots( $args ) {
		$base = $GLOBALS[ 'wp_rewrite' ]->using_index_permalinks() ? 'index.php/' : '';
		$name = esc_attr( $args['name'] );
		$options = $args['value'];
		if( isset( $options['enable'] ) && $options['enable'] == 1 && isset( $options['robots'] ) && $options['robots'] == 1 ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		echo '<input type="checkbox" name="'. $name.'" value="1" '. $checked.'/>';
		if( $checked == 'checked' ) {
			echo '<p style="color: #aaa;">' . __( 'Added to robots.txt' , 'xml-sitemap-for-stella' ) .': <pre>Sitemap: '. home_url($base.'sitemap-index.xml').'</pre></p>';
		} 
	}
	
	
	function gomo_settings_home_type($args) {
		$value = esc_attr( $args['value'] );
		
		echo '<select name="gomo_sitemap_options[home_type]" style="width: 140px;">';
		
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			//if ( in_array( $post_type->name, array( 'attachment' ) ) )
			//	continue;
			$selected = '';
			if( $post_type->name == $value ) {
				$selected = 'selected';
			}
			echo '<option value="'. $post_type->name .'" '.$selected.'>'.$post_type->labels->name .' ('. $post_type->name .')</option>';
		}	
		
		echo '</select>';	
	}
	
	function gomo_settings_exclude_posttypes($args) {
		$options = $args['value'];
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			if( isset( $options['exclude_types-'.$post_type->name] ) && $options['exclude_types-'.$post_type->name] == 1 ) { 
				$checked = 'checked';
			} else { 
				$checked = ''; 
			}
			echo '<label><input type="checkbox" name="gomo_sitemap_options[exclude_types-'. $post_type->name.']" value="1" '. $checked.' />    '. $post_type->labels->name .' ('. $post_type->name .')</label><br>';
		}
	}
	
	function gomo_settings_exclude_taxonomies($args) {
		$options = $args['value'];
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			if ( in_array( $taxonomy->name, array( 'link_category', 'nav_menu', 'post_format' ) ) )
				continue; 
			if( isset( $options['exclude_tax-'.$taxonomy->name] ) && $options['exclude_tax-'.$taxonomy->name] == 1 ) { 
				$checked = 'checked';
			} else { 
				$checked = ''; 
			}
			echo '<label><input type="checkbox" name="gomo_sitemap_options[exclude_tax-'. $taxonomy->name.']" value="1" '. $checked.' />    '. $taxonomy->labels->name .' ('. $taxonomy->name .')</label><br>';
		}
	}
	
	
	/**
	 * Hooked into transition_post_status. Will initiate search engine pings
	 * if the post is being published, is a post type that a sitemap is built for
	 * 
	 */
	function gomo_status_transition( $new_status, $old_status, $post ) {
		if ( $new_status != 'publish' ) {
			return;
		}
		
		$options = get_option( 'gomo_sitemap_options' );
		// check if sitemap is enabled
		if( isset( $options['enable'] ) && $options['enable'] == 0 ) { 
			return;
		}
		
		// check if post type was excluded
		if( isset( $options['exclude_types-'. $post->post_type] ) && $options['exclude_types-'.$post->post_type] == 1 ) { 
			return;
		}
		
		if ( WP_CACHE ) {
			wp_schedule_single_event( time() + 500, 'gomo_kick_sitemap_index' );
		}
	
		//do_action( 'gomo_submit_search_engines' );
		wp_schedule_single_event( (time() + 501 ), 'gomo_submit_search_engines', array() );
		
	}
	
	
}
global $gomo_sfs_admin;
$gomo_sfs_admin = new GOMO_Sitemaps_Multiple_Langs_admin;

?>