<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
POWERTOOLS
widget_shortcodes
widget_oembed
rss_thumbnails
page_excerpts
save_with_keyboard
move_scripts_footer
defer_all_scripts
disable_feeds
*/

// enable shortcodes in widgets
if (in_array('widget_shortcodes',$this->settings) && !is_admin() ) {
  //add_filter( 'widget_text', 'shortcode_unautop' );
  add_filter( 'widget_text', 'do_shortcode', 11 );
}

// enable oembed in text widgets
if (in_array('widget_oembed',$this->settings)) {
	global $wp_embed;
	add_filter( 'widget_text', array( $wp_embed, 'run_shortcode' ), 8 );
	add_filter( 'widget_text', array( $wp_embed, 'autoembed'), 8 );
}

// enable rss thumbnails
if (in_array('rss_thumbnails',$this->settings) && !is_admin() ) {
  function machete_add_rss_thumbnail($content) {
    global $post;
    
    if(has_post_thumbnail($post->ID)) {
        $content = '<div class="post-thumbnail-feed">' . get_the_post_thumbnail($post->ID, 'full') . '</div>' . $content;
    }
    return $content;
  }
  add_filter('the_excerpt_rss', 'machete_add_rss_thumbnail');
  add_filter('the_content_feed', 'machete_add_rss_thumbnail');
}

// enable page_excerpts
if (in_array('page_excerpts', $this->settings)) {
    add_post_type_support( 'page', 'excerpt' );
}

// save with keyboard
if (in_array('save_with_keyboard',$this->settings) && is_admin() ) {
    add_action('admin_enqueue_scripts', function(){
    	wp_register_script('machete_save_with_keyboard',MACHETE_BASE_URL.'vendor/save-with-keyboard/saveWithKeyboard.js',array('jquery'));
    	$translation_array = array(
    		'save_button_tooltip' => __( 'Ctrl+S or Cmd+S to click', 'machete' ),
    		'preview_button_tooltip' => __( 'Ctrl+P or Cmd+P to preview', 'machete' )
    	);
    	wp_localize_script( 'machete_save_with_keyboard', 'l10n_strings', $translation_array );
    	wp_enqueue_script( 'machete_save_with_keyboard' );

    });
}


// Script to Move JavaScript from the Head to the Footer
if (in_array('move_scripts_footer',$this->settings)) {
  function machete_remove_head_scripts() { 
     remove_action('wp_head', 'wp_print_scripts'); 
     remove_action('wp_head', 'wp_print_head_scripts', 9); 
     remove_action('wp_head', 'wp_enqueue_scripts', 1);

     add_action('wp_footer', 'wp_print_scripts', 5);
     add_action('wp_footer', 'wp_enqueue_scripts', 5);
     add_action('wp_footer', 'wp_print_head_scripts', 5); 
  } 
  add_action( 'wp_enqueue_scripts', 'machete_remove_head_scripts' );
}

//Defer all JS
if (in_array('defer_all_scripts',$this->settings)) {
  function machete_js_defer_attr($tag){
    return str_replace( ' src', ' defer="defer" src', $tag );
  }
  add_filter( 'script_loader_tag', 'machete_js_defer_attr', 10 );
}

// disable RSS feeds
if (in_array('disable_feeds',$this->settings) && !is_admin() ) {
	function machete_disable_feed() {
		wp_die( sprintf(__('No feed available, please visit our <a href="%s">homepage</a>!', 'machete'), get_bloginfo('url')) );
	}

	add_action('do_feed', 'machete_disable_feed', 1);
	add_action('do_feed_rdf', 'machete_disable_feed', 1);
	add_action('do_feed_rss', 'machete_disable_feed', 1);
	add_action('do_feed_rss2', 'machete_disable_feed', 1);
	add_action('do_feed_atom', 'machete_disable_feed', 1);
}