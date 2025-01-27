<?php
/**
 * Clean up wp_head()
 *
 * Remove unnecessary <link>'s
 * Remove inline CSS used by Recent Comments widget
 * Remove inline CSS used by posts with galleries
 * Remove self-closing tag and change ''s to "'s on rel_canonical()
 */
function spring_head_cleanup() {
  // Originally from http://wpengineer.com/1438/wordpress-header/
  remove_action( 'wp_head', 'feed_links', 2 );
  remove_action( 'wp_head', 'feed_links_extra', 3 );
  remove_action( 'wp_head', 'rsd_link' );
  remove_action( 'wp_head', 'wlwmanifest_link' );
  remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
  remove_action( 'wp_head', 'wp_generator' );
  remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );

  global $wp_widget_factory;
  remove_action( 'wp_head', array( $wp_widget_factory->widgets[ 'WP_Widget_Recent_Comments' ], 'recent_comments_style' ) );

  if ( !class_exists( 'WPSEO_Frontend' ) ) {
    remove_action( 'wp_head', 'rel_canonical' );
    add_action( 'wp_head', 'spring_rel_canonical' );
  }
}

function spring_rel_canonical() {
  global $wp_the_query;

  if ( !is_singular() ) {
    return;
  }

  if ( !$id = $wp_the_query->get_queried_object_id() ) {
    return;
  }

  $link = get_permalink( $id );
  echo "\t<link rel=\"canonical\" href=\"$link\">\n";
}
add_action( 'init', 'spring_head_cleanup' );

/**
 * Remove the WordPress version from RSS feeds
 */
add_filter( 'the_generator', '__return_false' );

/**
 * Clean up language_attributes() used in <html> tag
 *
 * Change lang="en-US" to lang="en"
 * Remove dir="ltr"
 */
function spring_language_attributes() {
  $attributes = array();
  $output = '';

  if ( function_exists( 'is_rtl' ) ) {
    if ( is_rtl() == 'rtl' ) {
      $attributes[] = 'dir="rtl"';
    }
  }

  $lang = get_bloginfo( 'language' );

  if ( $lang && $lang !== 'en-US' ) {
    $attributes[] = "lang=\"$lang\"";
  } else {
    $attributes[] = 'lang="en"';
  }

  $output = implode( ' ', $attributes );
  $output = apply_filters( 'spring_language_attributes', $output );

  return $output;
}
add_filter( 'language_attributes', 'spring_language_attributes' );

/**
 * Manage output of wp_title()
 */
function spring_wp_title( $title ) {
  if ( is_feed() ) {
    return $title;
  }

  $title .= get_bloginfo( 'name' );

  return $title;
}
add_filter( 'wp_title', 'spring_wp_title', 10 );

/**
 * Clean up output of stylesheet <link> tags
 */
function spring_clean_style_tag( $input ) {
  preg_match_all( "!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!", $input, $matches );
  // Only display media if it is meaningful
  $media = $matches[3][0] !== '' && $matches[3][0] !== 'all' ? ' media="' . $matches[3][0] . '"' : '';
  return '<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";
}
add_filter( 'style_loader_tag', 'spring_clean_style_tag' );

/**
 * Add and remove body_class() classes
 */
function spring_body_class( $classes ) {
  // Add post/page slug
  if ( is_single() || is_page() && !is_front_page() ) {
    $classes[] = basename( get_permalink() );
  }

  // Remove unnecessary classes
  $home_id_class = 'page-id-' . get_option('page_on_front');
  $remove_classes = array(
    'page-template-default',
    $home_id_class
  );
  $classes = array_diff( $classes, $remove_classes );

  return $classes;
}
add_filter( 'body_class', 'spring_body_class' );

/**
 * Wrap embedded media as suggested by Readability
 * Able to change based on URL source
 *
 * @link https://gist.github.com/965956
 * @link http://www.readability.com/publishers/guidelines#publisher
 * @link https://wordpress.stackexchange.com/questions/254583/add-wrapper-to-only-youtube-videos-via-embed-oembed-html-filter-function
 */
function spring_embed_wrap($cache, $url, $attr = '', $post_ID = '') {
  $classes = array();

    // Add these classes to all embeds.
    $classes_all = array(
        'entry-content-asset',
    );

    // Check for different providers and add appropriate classes.

    if ( false !== strpos( $url, 'vimeo.com' ) ) {
        $classes[] = 'vimeo video-asset';
    }

    if ( false !== strpos( $url, 'youtube.com' ) ) {
        $classes[] = 'youtube video-asset';
    }

    $classes = array_merge( $classes, $classes_all );

    return '<div class="' . esc_attr( implode( $classes, ' ' ) ) . '">' . $cache . '</div>';
}
add_filter('embed_oembed_html', 'spring_embed_wrap', 10, 4);

/**
* Wrap Gutenberg blocks in a container so we can target them with scroll ScrollReveal
* https://wordpress.stackexchange.com/questions/329587/add-a-containing-div-to-core-gutenberg-blocks
*/

add_filter( 'render_block', function( $block_content, $block ) {
    // Uncomment to only target core/* and core-embed/* blocks.
    //if ( preg_match( '~^core/|core-embed/~', $block['blockName'] ) ) {
       $block_content = sprintf( '<div class="single--block">%s</div>', $block_content );
    //}
    return $block_content;
}, PHP_INT_MAX - 1, 2 );

/**
 * Add thumbnail styling to images with captions
 * Use <figure> and <figcaption>
 *
 * @link http://justintadlock.com/archives/2011/07/01/captions-in-wordpress
 */
function spring_caption( $output, $attr, $content ) {
  if ( is_feed() ) {
    return $output;
  }

  $defaults = array(
    'id'      => '',
    'align'   => 'alignnone',
    'width'   => '',
    'caption' => ''
  );

  $attr = shortcode_atts( $defaults, $attr );

  // If the width is less than 1 or there is no caption, return the content wrapped between the [caption] tags
  if ( $attr['width'] < 1 || empty( $attr['caption'] ) ) {
    return $content;
  }

  // Set up the attributes for the caption <figure>
  $attributes  = ( !empty($attr['id'] ) ? ' id="' . esc_attr( $attr['id'] ) . '"' : '' );
  $attributes .= ' class="thumbnail wp-caption ' . esc_attr( $attr['align'] ) . '"';
  // $attributes .= ' style="width: ' . esc_attr($attr['width']) . 'px"';

  $output  = '<figure' . $attributes .'>';
  $output .= do_shortcode( $content );
  $output .= '<figcaption class="caption wp-caption-text">' . $attr['caption'] . '</figcaption>';
  $output .= '</figure>';

  return $output;
}
add_filter('img_caption_shortcode', 'spring_caption', 10, 3);

/**
 * Remove unnecessary dashboard widgets
 *
 * @link http://www.deluxeblogtips.com/2011/01/remove-dashboard-widgets-in-wordpress.html
 */
function spring_remove_dashboard_widgets() {
  remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
}
add_action('admin_init', 'spring_remove_dashboard_widgets');

/**
 * Clean up the_excerpt()
 */
function spring_excerpt_length( $length ) {
  return POST_EXCERPT_LENGTH;
}

function spring_excerpt_more( $more ) {
  return '... <a class="read-more" href="'. get_permalink( get_the_ID() ) . '">' . __( 'Read More', 'spring' ) . '</a>';
}
add_filter('excerpt_length', 'spring_excerpt_length');
add_filter('excerpt_more', 'spring_excerpt_more');

/**
 * Remove unnecessary self-closing tags
 */
function spring_remove_self_closing_tags( $input ) {
  return str_replace( ' />', '>', $input );
}
add_filter( 'get_avatar',          'spring_remove_self_closing_tags' ); // <img />
add_filter( 'comment_id_fields',   'spring_remove_self_closing_tags' ); // <input />
add_filter( 'post_thumbnail_html', 'spring_remove_self_closing_tags' ); // <img />

/**
 * Don't return the default description in the RSS feed if it hasn't been changed
 */
function spring_remove_default_description( $bloginfo ) {
  $default_tagline = 'Just another WordPress site';
  return ( $bloginfo === $default_tagline ) ? '' : $bloginfo;
}
add_filter( 'get_bloginfo_rss', 'spring_remove_default_description' );

/**
 * Redirects search results from /?s=query to /search/query/, converts %20 to +
 *
 * @link http://txfx.net/wordpress-plugins/nice-search/
 */
function spring_nice_search_redirect() {
  global $wp_rewrite;
  if ( !isset( $wp_rewrite ) || !is_object( $wp_rewrite ) || !$wp_rewrite->using_permalinks()) {
    return;
  }

  $search_base = $wp_rewrite->search_base;
  if ( is_search() && !is_admin() && strpos( $_SERVER['REQUEST_URI'], "/{$search_base}/" ) === false ) {
    wp_redirect( home_url( "/{$search_base}/" . urlencode( get_query_var( 's' ) ) ) );
    exit();
  }
}
if ( current_theme_supports( 'nice-search' ) ) {
  add_action( 'template_redirect', 'spring_nice_search_redirect' );
}

/**
 * Fix for empty search queries redirecting to home page
 *
 * @link http://wordpress.org/support/topic/blank-search-sends-you-to-the-homepage#post-1772565
 * @link http://core.trac.wordpress.org/ticket/11330
 */
function spring_request_filter( $query_vars ) {
  if ( isset( $_GET['s'] ) && empty( $_GET['s'] ) ) {
    $query_vars['s'] = ' ';
  }

  return $query_vars;
}
add_filter( 'request', 'spring_request_filter' );

/**
 * Tell WordPress to use searchform.php from the templates/ directory
 */
function spring_get_search_form( $form ) {
  $form = '';
  locate_template( '/templates/searchform.php', true, false );
  return $form;
}
add_filter( 'get_search_form', 'spring_get_search_form' );

/**
* From http://wordpress.stackexchange.com/questions/115368/overide-gallery-default-link-to-settings
* Default image links in gallery (not the same as image_default_link_type)
*/
function spring_gallery_default_type_set_link( $settings ) {
    $settings['galleryDefaults']['link'] = 'file';
    return $settings;
}
add_filter( 'media_view_settings', 'spring_gallery_default_type_set_link' );

/**
* Remove the overly opinionated gallery styles
*/
add_filter( 'use_default_gallery_style', '__return_false' );



/**
* Gets rid of current_page_parent class mistakenly being applied to Blog pages while on Custom Post Types
* via https://wordpress.org/support/topic/post-type-and-its-children-show-blog-as-the-current_page_parent
*/
function is_blog() {
  global $post;
  $posttype = get_post_type( $post );
  return ( ( $posttype == 'post' ) && ( is_home() || is_single() || is_archive() || is_category() || is_tag() || is_author() ) ) ? true : false;
}

function fix_blog_link_on_cpt( $classes, $item, $args ) {
  if( !is_blog() ) {
    $blog_page_id = intval( get_option( 'page_for_posts' ) );

    if( $blog_page_id != 0 && $item->object_id == $blog_page_id ) {
      if ( in_array( 'current_page_parent', $classes ) ) {
        unset( $classes[ array_search( 'current_page_parent', $classes ) ] );
      }
    }
  }
  return $classes;
}
add_filter( 'nav_menu_css_class', 'fix_blog_link_on_cpt', 10, 3 );

/**
* remove width attribute of thumbnails
*/
add_filter( 'post_thumbnail_html', 'remove_width_attribute', 10 );
add_filter( 'image_send_to_editor', 'remove_width_attribute', 10 );

function remove_width_attribute( $html ) {
    $html = preg_replace( '/(width|height)="\d*"\s/', "", $html );
    return $html;
}
