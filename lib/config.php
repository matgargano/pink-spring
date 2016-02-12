<?php
/**
 * Enable theme features
 */
add_theme_support('root-relative-urls');            // Enable relative URLs
add_theme_support('nice-search');                   // Enable /?s= to /search/ redirect
add_theme_support('jquery-cdn');                    // Enable to load jQuery from the Google CDN
add_theme_support('html5', array('search-form'));   // Enable HTML in the search form
add_editor_style( 'assets/css/editor-style.css' );  // Add editor styles

/**
 * Configuration values
 */
define('POST_EXCERPT_LENGTH', 55); // Length in words for excerpt_length filter (http://codex.wordpress.org/Plugin_API/Filter_Reference/excerpt_length)

/**
 * .main classes
 */
function spring_main_class() {
  
  if ( spring_display_sidebar() ) {
    // Classes on pages with the sidebar
    $class = 'content-main';
  } else {
    // Classes on full width pages
    $class = 'content-full';
  }

  return $class;
}


/**
 * .sidebar classes
 */
function spring_sidebar_class() {
  return 'sidebar-main';
}

function spring_sidebar_button() {
  
  if ( spring_display_sidebar() ) {
    // Show sidebar button on pages where sidebar is enabled
    $sidebar_button = '<button id="openSidebar" class="open-sidebar open-button"><span>Open Sidebar</span></button>';
  } else {
    // Disable sidebar button where sidebar is disabled
    $sidebar_button = '';
  }

  return $sidebar_button;
}

/**
 * Define which pages shouldn't have the sidebar
 *
 * See lib/sidebar.php for more details
 */
function spring_display_sidebar() {
  $sidebar_config = new Spring_Sidebar(
    /**
     * Conditional tag checks (http://codex.wordpress.org/Conditional_Tags)
     * Any of these conditional tags that return true won't show the sidebar
     *
     * To use a function that accepts arguments, use the following format:
     *
     * array('function_name', array('arg1', 'arg2'))
     *
     * The second element must be an array even if there's only 1 argument.
     */
    array(
      'is_404'  
    ),
    /**
     * Page template checks (via is_page_template())
     * Any of these page templates that return true won't show the sidebar
     */
    array(
      'template-custom.php'
    )
  );

  return apply_filters( 'spring_display_sidebar', $sidebar_config->display);
}

/**
 * $content_width is a global variable used by WordPress for max image upload sizes
 * and media embeds (in pixels).
 *
 * Example: If the content area is 640px wide, set $content_width = 620; so images and videos will not overflow.
 * Default: 1140px is the default Bootstrap container width.
 */
if ( !isset( $content_width ) ) { $content_width = 1140; }


// WooCommerce Support
add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
    add_theme_support( 'woocommerce' );
}


/**
 * Theme code to register the required plugins.
 *
 * @see http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * @package    TGM-Plugin-Activation
 * @subpackage Example
 * @version    2.5.2
 * @author     Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright  Copyright (c) 2011, Thomas Griffin
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       https://github.com/TGMPA/TGM-Plugin-Activation
 */

add_action( 'tgmpa_register', 'spring_register_required_plugins' );

function spring_register_required_plugins() {

  $plugins = array(

    array(
        'name'          => 'ACF Options Page',
        'slug'          => 'mtm-options-page',
        'source'        => 'https://github.com/marktimemedia/acf-theme-settings/archive/master.zip',
        'external_url'  => 'https://github.com/marktimemedia/acf-theme-settings', // If set, overrides default API URL and points to an external URL.
      ),
  );

  /*
   * Array of configuration settings. Amend each line as needed.
   *
   * TGMPA will start providing localized text strings soon. If you already have translations of our standard
   * strings available, please help us make TGMPA even better by giving us access to these translations or by
   * sending in a pull-request with .po file(s) with the translations.
   *
   * Only uncomment the strings in the config array if you want to customize the strings.
   */
  $config = array(
    'id'           => 'spring',                 // Unique ID for hashing notices for multiple instances of TGMPA.
    'default_path' => '',                      // Default absolute path to bundled plugins.
    'menu'         => 'spring-install-plugins', // Menu slug.
    'parent_slug'  => 'themes.php',            // Parent menu slug.
    'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
    'has_notices'  => true,                    // Show admin notices or not.
    'dismissable'  => false,                    // If false, a user cannot dismiss the nag message.
    'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
    'is_automatic' => false,                   // Automatically activate plugins after installation or not.
    'message'      => '',                      // Message to output right before the plugins table.
    );

  tgmpa( $plugins, $config );
}