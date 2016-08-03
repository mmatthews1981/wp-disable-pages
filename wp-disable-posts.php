<?php
/*
Plugin Name: WP Disable Pages
Plugin URI: https://github.com/mmatthews1981/wp-disable-pages/
Description: This plugin disables the built-in WordPress Post Type `page`, adapted from http://tonykwon.com/wordpress-plugins/wp-disable-posts/
Version: 0.1
Author: Meredith Matthews
Author URI: https://github.com/mmatthews1981/
License: GPLv3
*/
class WP_Disable_Pages
{
	public function __construct()
	{
		global $pagenow;
		/* checks the request and redirects to the dashboard */
		add_action( 'init', array( __CLASS__, 'disallow_post_type_page') );
		/* removes Post Type `Page` related menus from the sidebar menu */
		add_action( 'admin_menu', array( __CLASS__, 'remove_post_type_page' ) );
		if ( !is_admin() && ($pagenow != 'wp-login.php') ) {
			/* need to return a 404 when post_type `page` objects are found */
			add_action( 'posts_results', array( __CLASS__, 'check_page_type' ) );
			/* do not return any instances of post_type `page` */
			add_filter( 'pre_get_posts', array( __CLASS__, 'remove_from_search_filter' ) );
		}
	}
	/**
	 * checks the request and redirects to the dashboard
	 * if the user attempts to access any `page` related links
	 *
	 * @access public
	 * @param none
	 * @return void
	 */
	public static function disallow_post_type_page()
	{
		global $pagenow, $wp;
		switch( $pagenow ) {
			case 'edit.php':
			case 'edit-tags.php':
			case 'post-new.php':
				if ( !array_key_exists('post_type', $_GET) && !array_key_exists('taxonomy', $_GET) && !$_POST ) {
					wp_safe_redirect( get_admin_url(), 301 );
					exit;
				}
				break;
		}
	}
	/**
	 * loops through $menu and $submenu global arrays to remove any `page` related menus and submenu items
	 *
	 * @access public
	 * @param none
	 * @return void
	 *
	 */
	public static function remove_post_type_page()
	{
		global $menu, $submenu;
		/*
			edit.php
			post-new.php
			edit-tags.php?taxonomy=category
			edit-tags.php?taxonomy=page_tag
		 */
		$done = false;
		foreach( $menu as $k => $v ) {
			foreach($v as $key => $val) {
				switch($val) {
					case 'Pages':
						unset($menu[$k]);
						$done = true;
						break;
				}
			}
			/* bail out as soon as we are done */
			if ( $done ) {
				break;
			}
		}
		$done = false;
		foreach( $submenu as $k => $v ) {
			switch($k) {
				case 'edit.php':
					unset($submenu[$k]);
					$done = true;
					break;
			}
			/* bail out as soon as we are done */
			if ( $done ) {
				break;
			}
		}
	}
	/**
	 * checks the SQL statement to see if we are trying to fetch post_type `page`
	 *
	 * @access public
	 * @param array $posts,  found pages based on supplied SQL Query ($wp_query->request)
	 * @return array $posts, found pages
	 */
	public static function check_post_type( $posts = array() )
	{
		global $wp_query;
		$look_for = "wp_posts.post_type = 'page'";
		$instance = strpos( $wp_query->request, $look_for );
		/*
			http://localhost/?m=2013		- yearly archives
			http://localhost/?m=201303		- monthly archives
			http://localhost/?m=20130327	- daily archives
			http://localhost/?cat=1			- category archives
			http://localhost/?tag=foobar	- tag archives
			http://localhost/?p=1			- single page
		*/
		if ( $instance !== false ) {
			$posts = array(); // we are querying for post type `page`
		}
		return $posts;
	}
	/**
	 * excludes post type `page` to be returned from search
	 *
	 * @access public
	 * @param null
	 * @return object $query, wp_query object
	 */
	public static function remove_from_search_filter( $query )
	{
		if ( !is_search() ) {
			return $query;
		}
		$post_types = get_post_types();
		if ( array_key_exists('page', $post_types) ) {
			/* exclude post_type `page` from the query results */
			unset( $post_types['page'] );
		}
		$query->set( 'post_type', array_values($post_types) );
		return $query;
	}
}
new WP_Disable_Pages;
