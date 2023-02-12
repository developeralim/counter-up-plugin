<?php
/**
 * @package Word Counter
 * @version 1.2.0
 */
/*
	Plugin Name: word counter
	Plugin URI: http://example.com
	Description: This plugin is used for matching excel given word with target page word and show how many times correspondence word
	exist in that page content
	Author: Hridoy Rehemen
	Version: 1.2.0
	Author URI: http://example.com
*/
	namespace Counter;

 	class Counter {
	
	private const PLUGIN_VERSIONS   = '1.2.0';
	private const SHORTCODE         = "initialize_counter";
	private const OPTION_SLUG 	  = 'tool_slug'; //set option in option table with this key
	private const OPTION_POSITION   = 'tool_shortcode_position'; //set option in option table with this key
	private const OPTION_ID         = 'tool_page_id'; //set option in option table with this key
	private const OPTION_SHORTCODE  = 'tool_shortcode'; //set option in option table with this key
	private const OPTION_PATTERN    = 'shortcode_match_pattern'; //set option in option table with this key
	private const OPTION_TABLE      = 'tool_table'; //set option in option table with this key
	private const TABLE_NAME        = 'matching_history';

	public function __construct( ) 
	{
		$this->boot();
	}


	/**
	 * boot the whole application
	 * @param null no params
	 * @return void nothing to return
	 */
	private function boot ( ) : void
	{
		// activation and deactivation hooks
		register_activation_hook(__FILE__,[$this,'activate']);
		register_deactivation_hook(__FILE__,[$this,'deactivated']);

		// enqueue styles
		add_action('wp_enqueue_scripts',[ $this,'enqueue' ]);

		$this->EventsBeforeBoot();
		$this->booting();
		$this->EventsAfterBoot();
	}

	/**
	 * when plugin active
	 * @param null no params
	 * @return void nothing to return
	 */
	public function activate ( ) : void  
	{
		global $wpdb;

		flush_rewrite_rules( );

		$table_name 	= $wpdb->prefix . self::TABLE_NAME;
		$charset_collate  = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user varchar(55) NOT NULL,
			ref varchar(1080) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );
	}
	
	/**
	 * when plugin deactivate
	 * @param null no params
	 * @return void nothing to return
	 */
	public function deactivated ( ) : void  
	{
		flush_rewrite_rules( );

		// remove added options from database when plugin going to be deactivated

		delete_option(self::OPTION_POSITION);
		delete_option(self::OPTION_SLUG);
		delete_option(self::OPTION_SHORTCODE);
		delete_option(self::OPTION_PATTERN);
		delete_option(self::OPTION_ID);
		delete_option(self::OPTION_TABLE);
	}
	
	/**
	 * Enqueue styles and scripts
	 * @param null no params
	 * @return void nothing to return
	 */
	public function enqueue ( ) : void  
	{	
		//TODO enqueue styles if available
	}

	/**
	 * Event should be triggerd before booting 
	 * @param null no params
	 * @return void noting to return 
	 */

	private function EventsBeforeBoot ( )
	{
		global $wpdb;
		$pages = get_posts(array(
			'post_type' 	=> 'page',
			'post_status' 	=> 'publish',
			'posts_per_page' 	=> -1
		));


		foreach( $pages as $page) :

			$pattern = '#\[(.)*'.self::SHORTCODE.'(.)*\]#i';
			$hasShortCode = preg_match($pattern,$page->post_content);
			
			if ( ! $hasShortCode ) continue;

			// set some options in database
			add_option(self::OPTION_POSITION,strpos($page->post_content,'['.self::SHORTCODE.']'));
			add_option(self::OPTION_SLUG,$page->post_name);
			add_option(self::OPTION_SHORTCODE,self::SHORTCODE);
			add_option(self::OPTION_PATTERN,$pattern);
			add_option(self::OPTION_ID,$page->ID);
			add_option(self::OPTION_TABLE,$wpdb->prefix.self::TABLE_NAME);

		endforeach;
	}

	/**
	 * Booting the plugin 
	 * @param null no param
	 * @return void noting to return
	 */

	private function booting(  ) {
		
		//retrive page slug from database which referes to in which page tool is added
		$page_slug = get_option(self::OPTION_SLUG);

		if ( isset($_GET['page_id']) ) {

			/* Retrive URL slug */
			$url_slug = get_post_field( 'post_name',$_GET['page_id'] ?? 0 );	

		} else{

			/* Directory name in which wordpress installed */
			$dirname  = str_replace($_SERVER['DOCUMENT_ROOT'],"",str_replace('\\','/',ABSPATH));
			/* Retrive URL slug */
			$url_slug = str_replace($dirname,'',$_SERVER['REDIRECT_URL'] ?? '');

		}

		/* Remove Trailing slash from slug name */
		$url_slug = trim($url_slug,'/');
		
		if ( $url_slug !== $page_slug ) return;

		/* Rendered our custom template */
		add_filter('template_include',function ($template){

			if ( file_exists( $file = plugin_dir_path( __FILE__ ) . 'views/counter-view.php') ) {
				return $file;
			}
			
			return $template;
		});
	}

	/**
	 * Events after Booting 
	 * @param null no params
	 * @return void noting to return
	 */

	 private function eventsAfterBoot() {
		// TODO events after booted plugins
	}

}

new Counter;