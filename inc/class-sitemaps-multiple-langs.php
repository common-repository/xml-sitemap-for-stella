<?php
/**
 * @package XML_Sitemaps_multiple_langs
 */

if ( !defined( 'GOMO_SFS_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

class GOMO_Sitemaps_Multiple_Langs {
	// Content of the sitemap to output.
	private $sitemap = '';

	// XSL stylesheet for styling a sitemap for web browsers
	private $stylesheet = '';

	// Flag to indicate if this is an invalid or empty sitemap.
	private $bad_sitemap = false;

	// The maximum number of entries per sitemap page
	private $max_entries = 1000;
	
	// Sitemap Array of Languages (default is english)
	private $sitemap_langs = array('en');
	
	// Sitemap default language (default is english)
	private $sitemap_default_lang = 'en';
	
	// Custom hosts per language // $custom_hosts = array('LL' => 'host1.example.com')
	private $use_hosts = false;
	private $custom_hosts = array();
	
	//(Custom)post type sitemap which includes the homepage
	private $home_cpt = 'page';
	
	

	function __construct() {
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'gomo_sitemap_start', array( $this, 'load_settings'), 1, 4 );
		add_action( 'template_redirect', array( $this, 'sitemaps_redirect' ) );
		add_filter( 'redirect_canonical', array( $this, 'sitemaps_canonical' ) );
		
		add_action( 'gomo_kick_sitemap_index', array( $this, 'xmlsitemap_kick_sitemap_index' ) );
		add_action( 'gomo_submit_search_engines', array( $this, 'xmlsitemap_ping_search_engines' ) );

		// default stylesheet
		$this->stylesheet = '<?xml-stylesheet type="text/xsl" href="' . GOMO_SFS_URL . 'css/xml-sitemap-xsl.php"?>';
		
	}

	
	/**
	 * Initialize sitemaps multiple languages. Load plugin options. Add sitemap rewrite rules and query var
	 */
	function init() {
		add_rewrite_tag('%xmlsitemap%','([^/]+?)');
		add_rewrite_tag('%xmlsitemap_lang%','([^/]+?)');
		add_rewrite_tag('%xmlsitemap_page%','([0-9]+)?');
		
		// Add Rewrite rules
		add_rewrite_rule( 'sitemap-index\.xml$', 'index.php?xmlsitemap=1', 'top' );
		add_rewrite_rule( 'sitemap-([^/]+?)-([^/]+?)-([0-9]+)?\.xml$', 'index.php?xmlsitemap=$matches[1]&xmlsitemap_lang=$matches[2]&xmlsitemap_page=$matches[3]', 'top' );
		
		
		// Load Options
		$options = get_option( 'gomo_sitemap_options' );
		
		if( is_array( $options ) ) {
			if ( ( isset( $options['enable'] ) && $options['enable'] == 0 ) || !isset( $options['enable'] ) ) { $this->bad_sitemap = true; return;}
			if ( isset( $options['robots'] ) && $options['robots'] == 1 ) {
				add_filter('robots_txt', array( $this, 'gomo_add_sitemap2robots' ),10,2);
			} else {
				remove_filter('robots_txt', array( $this, 'gomo_add_sitemap2robots' ));
			}
			if ( isset( $options['max_entries'] ) ) { $this->max_entries = $options['max_entries']; }
			if ( isset( $options['home_type'] ) ) { $this->home_cpt = $options['home_type']; }
		}
		
	}
	
	/**
	 * Load language settings
	 */
	function load_settings($dlang = 'en', $langs = array('en'), $uhosts = false, $hosts = array() ) {
		$this->sitemap_default_lang = $dlang;
		$this->sitemap_langs = $langs;
		$this->use_hosts = $uhosts;
		$this->custom_hosts = $hosts;
	}
	/**
	 * Hijack requests for potential sitemaps. Hooked at construct
	 */
	function sitemaps_redirect() {
		$type = get_query_var( 'xmlsitemap' );
		if ( empty( $type ) )
			return;
		
		// 404 for invalid or emtpy sitemaps
		if ( $this->bad_sitemap ) {
			$GLOBALS['wp_query']->is_404 = true;
			return;
		}
		
		// Generate propoer sitemap according to type
		$this->xmlsitemap_generate_bytype( $type );
		
		// 404 for invalid or emtpy sitemaps
		if ( $this->bad_sitemap ) {
			$GLOBALS['wp_query']->is_404 = true;
			return;
		}

		$this->sitemap_output();
		die();
	}

	/**
	 * Attempt to build the requested sitemap. Sets $bad_sitemap if this isn't
	 * for the root sitemap, a post type or taxonomy.
	 *
	 * @param string $type The requested sitemap's identifier.
	 */
	function xmlsitemap_generate_bytype( $type ) {

		if ( $type == 1 ) {
			$this->xmlsitemap_generate_index();
		} else if ( post_type_exists( $type ) ) {
			$this->xmlsitemap_generate_post_type( $type );
		} else if ( taxonomy_exists( $type ) ) {
			$this->xmlsitemap_generate_taxonomy( $type );
		} else {
			$this->bad_sitemap = true;
		}
	}


	/**
	 * Generates the sitemap index
	 * example.com/sitemap-index.xml
	 */
	function xmlsitemap_generate_index() {
		
		//Index Max Entries = 50.000 todo: split index files with more than 50000 entries
//		$max_index_entries = 50000;
//		$count_index_entries = 0;
		
		// Load Options
		$options = get_option( 'gomo_sitemap_options' );
		$base = $GLOBALS['wp_rewrite']->using_index_permalinks() ? 'index.php/' : '';
		
		//prepare index sitemap header
		// print sitemap header
		$this->sitemap = '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
		$this->sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" ';
		$this->sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";		
		//$this->sitemap = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		
		// generate entries for the elected post types
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			
			if ( isset( $options[ 'exclude_types-'. $post_type ] ) && $options[ 'exclude_types-'. $post_type ] )
				continue;
			
//			if ( in_array( $post_type, array( '' ) ) )
//				continue; 
				
			$count_posts = wp_count_posts( $post_type )->publish;
			
			if ( !( $count_posts > 0 ) )
				continue;
				
			// Pagination according to max_entries
			$p = ( $count_posts > $this->max_entries ) ? (int) ceil( $count_posts / $this->max_entries ) : 1;
			for ( $i = 0; $i < $p; $i++ ) {
				$sitemap_page = $i + 1;
				
				$date = $this->last_modified_date( $post_type, $this->max_entries * $i );
				
				foreach( $this->sitemap_langs as $lang ) {
					$this->sitemap .= "\t". '<sitemap>' . "\n";
					$this->sitemap .= "\t\t".'<loc>' . home_url($base . 'sitemap-'. $post_type . '-'.  $lang . '-' . $sitemap_page . '.xml' ) . '</loc>' . "\n";
					$this->sitemap .= "\t\t".'<lastmod>' . htmlspecialchars( $date ) . '</lastmod>' . "\n";
					$this->sitemap .= "\t". '</sitemap>' . "\n";
				}
			}		
		}
		
		// generate entries for the elected taxonomies
		foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			if ( in_array( $taxonomy, array( 'link_category', 'nav_menu', 'post_format' ) ) )
				continue; 
			
			if ( isset( $options[ 'exclude_tax-'. $taxonomy ] ) && $options[ 'exclude_tax-'. $taxonomy ] )
				continue;
			
			if ( !( wp_count_terms( $taxonomy ) > 0 ) )
				continue;

			// query the last modified date of a post where the taxonomy is applied
			$taxobj = get_taxonomy( $taxonomy );
			
			$date = $this->last_modified_date( $taxobj->object_type );
			
			foreach( $this->sitemap_langs as $lang ) {
				$this->sitemap .= "\t".'<sitemap>' . "\n";
				$this->sitemap .= "\t\t".'<loc>' . home_url( $base . 'sitemap-'. $taxonomy . '-'.  $lang . '-1.xml' ) . '</loc>' . "\n";
				$this->sitemap .= "\t\t".'<lastmod>' . htmlspecialchars( $date ) . '</lastmod>' . "\n";
				$this->sitemap .= "\t".'</sitemap>' . "\n";
			}
		}
	
		$this->sitemap .= '</sitemapindex>';
	}


	/**
	 * Build a sub-sitemap for a specific post type -- example.com/sitemap-$post_type-$lang-$page.xml
	 *
	 * @param string $post_type Registered post type's slug
	 */
	function xmlsitemap_generate_post_type( $post_type ) {
		// Load Options
		$options = get_option( 'gomo_sitemap_options' );
		if ( ( isset( $options[ 'exclude_types-'. $post_type ] ) && $options[ 'exclude_types-'. $post_type ] ) || in_array( $post_type, array( 'revision', 'nav_menu_item' ) ) ) {
			$this->bad_sitemap = true;
			return;
		}
		
		// get remaining variables of requested sitemap (redirect)
		$sitemap_page = get_query_var( 'xmlsitemap_page' );
		$lang = get_query_var( 'xmlsitemap_lang' );
		
		// calculate base url
		$base_url = $this->xmlsitemap_get_base_url( $lang );
	
		// print sitemap header
		$this->sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ';
		$this->sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
		$this->sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Page 1
		if ( $sitemap_page == 1 ) {
			// include homepage URL ?
			if( $post_type == $this->home_cpt ) {
				$this->sitemap .= $this->generate_sitemap_url( array(
					'loc' => $base_url,
					'priority' => 1,
					'changefreq' => 'daily',
				) );
			}
			
			if ( function_exists( 'get_post_type_archive_link' ) ) {
				$archive = get_post_type_archive_link( $post_type );
				if ( $archive ) {
					$this->sitemap .= $this->generate_sitemap_url( array(
						'loc' => $base_url .  str_replace( home_url('/'), '', $archive ) ,
						'priority' => 0.8,
						'changefreq' => 'weekly',
						'lastmod' => $this->last_modified_date( $post_type ),
					) );
				}
			}
		}
		
		//print remaining sitemap url's
		$sitemap_posts =  new WP_Query( array( 'post_type' => $post_type, 'orderby' => 'modified', 'order' => 'DESC', 'posts_per_page' => $this->max_entries, 'paged' => $sitemap_page));
		
		
		while ( $sitemap_posts->have_posts() ) {
			$sitemap_posts->the_post();
			
			$post_modified_date = get_the_modified_time('c');
			
			// images ?
			$images = array();
			
			$attachments = new WP_Query( array( 'post_type'  => 'attachment', 'posts_per_page' => -1, 'post_status' => 'any', 'post_parent' => $sitemap_posts->post->ID ));
			while ( $attachments->have_posts() ) {
				$attachments->the_post();
				$image = array();
				$image['src'] = wp_get_attachment_url($attachments->post->ID);
				$image['title'] = get_the_title();
				$image['alt'] = get_the_excerpt();				
				
				$images[] = $image;
			}
			wp_reset_postdata();
			
			$this->sitemap .= $this->generate_sitemap_url( array(
				'loc' => $base_url .  str_replace( home_url('/'), '', get_permalink($sitemap_posts->post->ID)  ),
				'priority' => 0.8,
				'changefreq' => 'weekly',
				'lastmod' => $post_modified_date,
				'images' => $images
			) );
			
		}
		
		// print sitemap footer
		$this->sitemap .= '</urlset>';
	
	}

	/**
	 * Build a sub-sitemap for a specific taxonomy -- example.com/sitemap-$taxonomy-$lang-$page.xml
	 *
	 * @param string $taxonomy Registered taxonomy's slug
	 */
	function xmlsitemap_generate_taxonomy( $taxonomy ) {
		// Load Options
		$options = get_option( 'gomo_sitemap_options' );
		if ( ( isset( $options[ 'exclude_types-'. $taxonomy ] ) && $options[ 'exclude_types-'. $taxonomy ] ) || in_array( $taxonomy, array( 'link_category', 'nav_menu', 'post_format' ) ) ) {
			$this->bad_sitemap = true;
			return;
		}
	
		// get remaining variables of requested sitemap (redirect)
		$lang = get_query_var( 'xmlsitemap_lang' );
		
		// calculate base url
		$base_url = $this->xmlsitemap_get_base_url( $lang );
		
		// print sitemap header
		$this->sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
		$this->sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
		$this->sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		
		// needed to get the post types associated to this taxonomy to query for the date
		$taxobj = get_taxonomy( $taxonomy );
		
		$terms = get_terms( $taxonomy , array( 'hide_empty' => true ) );

		foreach ( $terms as $term ) {
			// calculate priority according to relevancy
			if ( $term->count > 10 ) {
				$priority = 0.6;
			} else if ( $term->count > 3 ) {
				$priority = 0.4;
			} else {
				$priority = 0.2;
			}
			
			// Prepare to date query
			$tax_query = array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $term->slug ));	
			
			$this->sitemap .= $this->generate_sitemap_url( array(
				'loc' => $base_url .  str_replace( home_url('/'), '', get_term_link( $term, $term->taxonomy ) ) ,
				'priority' => $priority,
				'changefreq' => 'weekly',
				'lastmod' => $this->last_modified_date( $taxobj->object_type , 0, $tax_query ),
			) );
		}
		
		$this->sitemap .= '</urlset>';
	}

	/**
	 * Spit out the generated sitemap and relevant headers and encoding information.
	 */
	function sitemap_output() {
		header( 'HTTP/1.1 200 OK', true, 200 );
		// Prevent the search engines from indexing the XML Sitemap.
		header( 'X-Robots-Tag: noindex, follow', true );
		header( 'Content-Type: text/xml' );
		echo '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
		if ( $this->stylesheet )
			echo $this->stylesheet  . "\n";
		echo $this->sitemap;
		echo "\n" . '<!-- XML Sitemap generated by GOMO - XML SITEMAPS FOR STELLA PLUGIN -->';

		if ( WP_DEBUG ) {
			echo "\n" . '<!-- Built in ' . timer_stop() . ' seconds | ' . memory_get_peak_usage() . ' | ' . count( $GLOBALS['wpdb']->queries ) . ' -->';
		}
	}

	/**
	 * Generate the <url> sitemap tag for a given URL.
	 *
	 * @param array $url Array of parts that make up this entry (loc, lastmod, changefreq, priority, ...)
	 * @return string
	 */
	function generate_sitemap_url( $url ) {
		if ( isset( $url['lastmod'] ) ) {
			$date = mysql2date( "Y-m-d\TH:i:s+00:00", $url['lastmod'] );
		} else {
			$date = date( 'c' );
		}
		
		$url_output = "\t<url>\n";
		$url_output .= "\t\t<loc>" . $url['loc'] . "</loc>\n";
		$url_output .= "\t\t<lastmod>" . $date . "</lastmod>\n";
		$url_output .= "\t\t<changefreq>" . $url['changefreq'] . "</changefreq>\n";
		$url_output .= "\t\t<priority>" . str_replace( ',', '.', $url['priority'] ) . "</priority>\n";
		if ( isset( $url['images'] ) && count( $url['images'] ) > 0 ) {
			foreach ( $url['images'] as $img ) {
				if ( !isset( $img['src'] ) || empty( $img['src'] ) )
					continue;
				$url_output .= "\t\t<image:image>\n";
				$url_output .= "\t\t\t<image:loc>" . esc_html( $img['src'] ) . "</image:loc>\n";
				if ( isset( $img['title'] ) && !empty( $img['title'] ) )
					$url_output .= "\t\t\t<image:title>" . _wp_specialchars( html_entity_decode( $img['title'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . "</image:title>\n";
				if ( isset( $img['alt'] ) && !empty( $img['alt'] ) )
					$url_output .= "\t\t\t<image:caption>" . _wp_specialchars( html_entity_decode( $img['alt'], ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . "</image:caption>\n";
				$url_output .= "\t\t</image:image>\n";
			}
		}
		$url_output .= "\t</url>\n";
		return $url_output;
	}

	

	/**
	 * Hook into redirect_canonical to stop trailing slashes on sitemap.xml URLs
	 *
	 * @param string $redirect The redirect URL currently determined.
	 * @return bool|string $redirect
	 */
	function sitemaps_canonical( $redirect ) {
		$sitemap = get_query_var( 'xmlsitemap' );
		if ( !empty( $sitemap ) )
			return false;

		return $redirect;
	}

	/**
	 * Get modified date for the last modified post of post_type(s), given an offset and an optional tax_query
	 *
	 * @param array $post_types (Post types to get the last modification date for), $offset - depending on the page, $tax_query - to query a specific taxonomy/term
	 * @return string
	 */
	function last_modified_date( $post_types, $offset = 0, $tax_query = array() ) {
		
		if ( !is_array( $post_types ) )
			$post_types = array( $post_types );

		$date = 0;
		
		if( !empty( $tax_query ) ) {
			$query_date =  new WP_Query( array( 'post_type' => $post_types, $tax_query , 'orderby' => 'modified', 'order' => 'DESC', 'posts_per_page' => '1', 'offset' => $offset ));
		} else {
			$query_date =  new WP_Query( array( 'post_type' => $post_types, 'orderby' => 'modified', 'order' => 'DESC', 'posts_per_page' => '1', 'offset' => $offset ));
		}
		
		if( isset( $query_date->posts[0]->post_modified_gmt ) ) {
			$date = $query_date->posts[0]->post_modified_gmt;
		} else {
			$date = current_time('mysql', 0);
		}
		$date = date( 'c', strtotime( $date ) );
		
		return $date;
	}
	
	/**
	 * Get base url for a specific language, given $use_hosts and $hosts
	 *
	 * @param $lang = 'en'
	 * @return string http://example.com/en/ or http://host_language/
	 */
	function xmlsitemap_get_base_url( $lang ){
		if( $this->use_hosts ) {
			$base = $this->custom_hosts[$lang];
			if ( is_ssl() ) {
				$base = 'https://' . $base .'/';
			} else {
				$base = 'http://' . $base .'/';
			}
		} elseif ( $lang == $this->sitemap_default_lang ) {
			$base = home_url('/');
		} else {
			$base = home_url('/' . $lang . '/');
		}
		return $base;
	}
	
	/** 
	 * Notify search engines of the updated sitemap. 
	 */
	 
	function xmlsitemap_ping_search_engines() {
		
		$base = $GLOBALS['wp_rewrite']->using_index_permalinks() ? 'index.php/' : '';
		$sitemapurl = urlencode( home_url( $base . 'sitemap-index.xml' ) );

		// Always ping Google and Bing
		$response = wp_remote_get( 'http://www.google.com/webmasters/tools/ping?sitemap=' . $sitemapurl );
		if( WP_DEBUG && is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   error_log( "Notice: Plugin SFS: Something went wrong: $error_message" );
		}
		//$response = wp_remote_get( 'http://www.bing.com/webmaster/ping.aspx?sitemap=' . $sitemapurl );
		$response = wp_remote_get( 'http://www.bing.com/ping?sitemap=' . $sitemapurl );
		if( WP_DEBUG && is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   error_log( "Notice: Plugin SFS: Something went wrong: $error_message" );
		}		
	}

	/**
	 * Make a request for the sitemap index so as to cache it before the arrival of the search engines.
	 */
	function xmlsitemap_kick_sitemap_index() {
		$base = $GLOBALS['wp_rewrite']->using_index_permalinks() ? 'index.php/' : '';
		$url  = home_url( $base . 'sitemap-index.xml' );
		$response = wp_remote_get( $url );
		if( WP_DEBUG && is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   error_log( "Notice: Plugin SFS: Something went wrong: $error_message" );
		} 
	}
	
	/**
	 * Add sitemap entry to wordpress robots.txt
	 */
	function gomo_add_sitemap2robots($output, $public) {
		if ( '0' == $public ) {
			return $output;
		} else {
			$base = $GLOBALS['wp_rewrite']->using_index_permalinks() ? 'index.php/' : '';
			$sitemapurl = 'Sitemap: '. home_url( $base . 'sitemap-index.xml' ) ."\n";
		}
		return $sitemapurl . $output;
	}

}

?>