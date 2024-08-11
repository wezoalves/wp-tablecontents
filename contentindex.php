<?php
/*
Plugin Name: WezoAlves - Table of Contents
Plugin URI: https://wezo.com.br/
Description: Automatically create a navigable table of contents for your articles based on headers (h2, h3, h4, h5, and h6). This plugin enhances the reading experience and navigation of your posts by adding a table of contents before the first header found.
Version: 1.0
Author: Weslley Alves
Author URI: https://wezo.com.br
Text Domain: wezoalves
*/

// Check if WordPress functions are available
if (! defined('ABSPATH') && ! function_exists('add_action')) {
  exit('This plugin requires WordPress to be installed and running.');
}
/**
 * Class TableContents
 * 
 * This class creates a navigable table of contents for WordPress articles based on headers (h2, h3, h4, h5, and h6).
 * It enhances the reading experience and navigation by adding a table of contents before the first header found in the post content.
 */
class WezoAlvesTableContents
{
  /**
   * Constructor to initialize the plugin by adding WordPress hooks.
   */
  public function __construct()
  {
    add_action('plugins_loaded', array($this, 'load_textdomain'));
    add_action('add_meta_boxes', array($this, 'add_custom_meta_box'));
    add_action('save_post', array($this, 'save_custom_meta_box'));
    add_filter('the_content', array($this, 'content_index'));
  }

  /**
   * Load the plugin text domain for translation.
   */
  public function load_textdomain()
  {
    load_plugin_textdomain('wezoalves', false, dirname(plugin_basename(__FILE__)) . '/languages/');
  }

  /**
   * Add a custom meta box to the post editing screen.
   */
  public function add_custom_meta_box()
  {
    add_meta_box(
      'header_selector_meta_box',
      __('Index Selection', 'wezoalves'),
      array($this, 'display_custom_meta_box'),
      'post',
      'side',
      'low'
    );
  }

  /**
   * Display the custom meta box.
   * The option to generate an index for the **h1** element is not provided because it should be unique and prioritized on the page. 
   * It should be used for the page title and not for header elements within the content.
   * @param WP_Post $post The current post object.
   */
  public function display_custom_meta_box($post)
  {
    $selected_headers = get_post_meta($post->ID, 'header_selector', true);
    if (! is_array($selected_headers)) {
      $selected_headers = [];
    }
    $headers = ['h2', 'h3', 'h4', 'h5', 'h6'];
    foreach ($headers as $header) {
      $checked = in_array($header, $selected_headers) ? 'checked' : '';
      $header = strtoupper($header);
      $html = <<<HTML
            <!-- Header Meta Box Admin WordPress -->
            <p>
                <label>
                    <input type="checkbox" name="header_selector[]" value="{$header}" {$checked}>
                    {$header}
                </label>
            </p>
            <!-- End Header Meta Box Admin WordPress -->
            HTML;
      echo $html;
    }
  }

  /**
   * Save the custom meta box selection when the post is saved.
   *
   * @param int $post_id The ID of the current post.
   */
  public function save_custom_meta_box($post_id)
  {
    if (array_key_exists('header_selector', $_POST)) {
      update_post_meta($post_id, 'header_selector', $_POST['header_selector']);
    } else {
      delete_post_meta($post_id, 'header_selector');
    }
  }

  /**
   * Add the table of contents to the post content.
   *
   * @param string $content The original post content.
   * @return string The modified post content with the table of contents.
   */
  public function content_index($content)
  {
    if (get_post_type() == 'post' && is_singular()) {

      global $post;

      $selected_headers = get_post_meta($post->ID, 'header_selector', true);

      if (empty($selected_headers)) {
        return $content;
      }

      $stringIndex = __('index', 'wezoalves');

      $stringHeaderIndex = __('What you will find in this article', 'wezoalves');

      // Convert headers to lowercase for the pattern
      $header_pattern = implode('|', array_map('strtolower', $selected_headers));
      $regex = '/<(' . $header_pattern . ')\b[^>]*>(.*?)<\/\1>/si'; // 'i' for case-insensitive
      preg_match_all($regex, $content, $matches, PREG_SET_ORDER);

      $indexContentHtml = '';
      if (count($matches)) {
        $indexContentHtml .= <<<HTML
          <!-- Table of Contents Html -->
          <p class='title-table-content'>{$stringHeaderIndex}</p>
          <ol class='table-content'>
        HTML;
        $replacements = [];
        foreach ($matches as $match) {
          $level = $match[1];
          $text = $match[2];
          $text = strip_tags($text);
          $slug = sanitize_title($text);
          $slug = "{$stringIndex}-{$slug}";

          $indexContentHtml .= <<<HTML
            <!-- Table of Contents Item Index -->
            <li class='index-header-{$level}'>
              <a class='index-header-link' href='#{$slug}'>
                {$text}
              </a>
            </li>
            <!-- End Table of Contents Item Index -->
          HTML;

          $new_header = <<<HTML
            <!-- Table of Contents Anchor Header -->
            <span class='index-anchor' id='{$slug}'></span>
            <{$level}>{$text}</{$level}>
            <!-- End Table of Contents Anchor Header -->
          HTML;

          $replacements[$match[0]] = $new_header;
        }

        $indexContentHtml .= <<<HTML
          </ol>
          <!-- End Table of Contents Html -->
        HTML;

        foreach ($replacements as $original => $new) {
          $content = str_replace($original, $new, $content);
        }
      }

      $content = "{$indexContentHtml}{$content}";
    }
    return $content;
  }
}

// Initialize the plugin
new WezoAlvesTableContents();