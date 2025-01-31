<?php

/**
 * Plugin Name: SKU Generator
 * Description: Automatically generates SKUs for WooCommerce products
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: sku-generator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

class SKU_Generator
{
  private $options;
  private $batch_size = 50; // Process products in batches to avoid timeouts

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_ajax_generate_bulk_skus', array($this, 'ajax_generate_bulk_skus'));
  }

  public function add_admin_menu()
  {
    add_submenu_page(
      'woocommerce',
      __('SKU Generator', 'sku-generator'),
      __('SKU Generator', 'sku-generator'),
      'manage_woocommerce',
      'sku-generator',
      array($this, 'admin_page')
    );
  }

  public function register_settings()
  {
    register_setting('sku_generator_options', 'sku_generator_options');

    add_settings_section(
      'sku_generator_main',
      __('SKU Generator Settings', 'sku-generator'),
      null,
      'sku-generator'
    );

    // Basic Settings
    add_settings_field(
      'prefix',
      __('SKU Prefix', 'sku-generator'),
      array($this, 'prefix_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'suffix',
      __('SKU Suffix', 'sku-generator'),
      array($this, 'suffix_field'),
      'sku-generator',
      'sku_generator_main'
    );

    // Pattern Settings
    add_settings_field(
      'include_product_id',
      __('Include Product ID', 'sku-generator'),
      array($this, 'include_product_id_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'pattern_type',
      __('SKU Pattern Type', 'sku-generator'),
      array($this, 'pattern_type_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'pattern_length',
      __('Pattern Length', 'sku-generator'),
      array($this, 'pattern_length_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'include_category',
      __('Include Category', 'sku-generator'),
      array($this, 'include_category_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'category_chars',
      __('Category Characters', 'sku-generator'),
      array($this, 'category_chars_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'include_date',
      __('Include Date', 'sku-generator'),
      array($this, 'include_date_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'date_format',
      __('Date Format', 'sku-generator'),
      array($this, 'date_format_field'),
      'sku-generator',
      'sku_generator_main'
    );

    add_settings_field(
      'separator',
      __('Separator Character', 'sku-generator'),
      array($this, 'separator_field'),
      'sku-generator',
      'sku_generator_main'
    );
  }

  public function prefix_field()
  {
    $options = get_option('sku_generator_options');
    $prefix = isset($options['prefix']) ? $options['prefix'] : '';
    echo "<input type='text' name='sku_generator_options[prefix]' value='" . esc_attr($prefix) . "' />";
  }

  public function suffix_field()
  {
    $options = get_option('sku_generator_options');
    $suffix = isset($options['suffix']) ? $options['suffix'] : '';
    echo "<input type='text' name='sku_generator_options[suffix]' value='" . esc_attr($suffix) . "' />";
    echo "<p class='description'>" . __('Add a suffix to the end of each SKU.', 'sku-generator') . "</p>";
  }

  public function include_product_id_field()
  {
    $options = get_option('sku_generator_options');
    $include_product_id = isset($options['include_product_id']) ? $options['include_product_id'] : '0';
    echo "<input type='checkbox' name='sku_generator_options[include_product_id]' value='1' " . checked($include_product_id, '1', false) . "/>";
    echo "<p class='description'>" . __('Include product ID in SKU', 'sku-generator') . "</p>";
  }

  public function pattern_type_field()
  {
    $options = get_option('sku_generator_options');
    $pattern_type = isset($options['pattern_type']) ? $options['pattern_type'] : 'alphanumeric';
?>
    <select name="sku_generator_options[pattern_type]">
      <option value="alphanumeric" <?php selected($pattern_type, 'alphanumeric'); ?>><?php _e('Alphanumeric (A-Z, 0-9)', 'sku-generator'); ?></option>
      <option value="numeric" <?php selected($pattern_type, 'numeric'); ?>><?php _e('Numeric Only (0-9)', 'sku-generator'); ?></option>
      <option value="alphabetic" <?php selected($pattern_type, 'alphabetic'); ?>><?php _e('Alphabetic Only (A-Z)', 'sku-generator'); ?></option>
      <option value="custom" <?php selected($pattern_type, 'custom'); ?>><?php _e('Custom Pattern', 'sku-generator'); ?></option>
    </select>
  <?php
  }

  public function pattern_length_field()
  {
    $options = get_option('sku_generator_options');
    $length = isset($options['pattern_length']) ? $options['pattern_length'] : '8';
    echo "<input type='number' min='4' max='32' name='sku_generator_options[pattern_length]' value='" . esc_attr($length) . "' />";
    echo "<p class='description'>" . __('Length of the random part of the SKU (4-32 characters)', 'sku-generator') . "</p>";
  }

  public function include_category_field()
  {
    $options = get_option('sku_generator_options');
    $include_category = isset($options['include_category']) ? $options['include_category'] : '0';
    echo "<input type='checkbox' name='sku_generator_options[include_category]' value='1' " . checked($include_category, '1', false) . "/>";
    echo "<p class='description'>" . __('Include product category code in SKU', 'sku-generator') . "</p>";
  }

  public function category_chars_field()
  {
    $options = get_option('sku_generator_options');
    $category_chars = isset($options['category_chars']) ? $options['category_chars'] : '2';
    echo "<input type='number' min='1' max='5' name='sku_generator_options[category_chars]' value='" . esc_attr($category_chars) . "' />";
    echo "<p class='description'>" . __('Number of characters to use from category name (1-5)', 'sku-generator') . "</p>";
  }

  public function include_date_field()
  {
    $options = get_option('sku_generator_options');
    $include_date = isset($options['include_date']) ? $options['include_date'] : '0';
    echo "<input type='checkbox' name='sku_generator_options[include_date]' value='1' " . checked($include_date, '1', false) . "/>";
    echo "<p class='description'>" . __('Include date in SKU', 'sku-generator') . "</p>";
  }

  public function date_format_field()
  {
    $options = get_option('sku_generator_options');
    $date_format = isset($options['date_format']) ? $options['date_format'] : 'Ymd';
  ?>
    <select name="sku_generator_options[date_format]">
      <option value="Ymd" <?php selected($date_format, 'Ymd'); ?>><?php _e('YYYYMMDD', 'sku-generator'); ?></option>
      <option value="ymd" <?php selected($date_format, 'ymd'); ?>><?php _e('YYMMDD', 'sku-generator'); ?></option>
      <option value="ym" <?php selected($date_format, 'ym'); ?>><?php _e('YYMM', 'sku-generator'); ?></option>
      <option value="y" <?php selected($date_format, 'y'); ?>><?php _e('YY', 'sku-generator'); ?></option>
    </select>
  <?php
  }

  public function separator_field()
  {
    $options = get_option('sku_generator_options');
    $separator = isset($options['separator']) ? $options['separator'] : '-';
    echo "<input type='text' maxlength='1' name='sku_generator_options[separator]' value='" . esc_attr($separator) . "' />";
    echo "<p class='description'>" . __('Character to separate SKU parts (e.g., -)', 'sku-generator') . "</p>";
  }

  public function admin_page()
  {
  ?>
    <div class="wrap">
      <h1><?php echo esc_html(__('SKU Generator', 'sku-generator')); ?></h1>

      <form method="post" action="options.php">
        <?php
        settings_fields('sku_generator_options');
        do_settings_sections('sku-generator');
        submit_button();
        ?>
      </form>

      <div class="sku-generator-bulk">
        <h2><?php echo esc_html(__('Bulk Generate SKUs', 'sku-generator')); ?></h2>
        <p><?php echo esc_html(__('Generate SKUs for all products that don\'t have one.', 'sku-generator')); ?></p>
        <button id="generate-skus" class="button button-primary">
          <?php echo esc_html(__('Generate SKUs', 'sku-generator')); ?>
        </button>
        <div id="progress-bar" style="display: none;">
          <progress value="0" max="100"></progress>
          <span id="progress-text">0%</span>
        </div>
      </div>
    </div>
<?php
  }

  public function enqueue_scripts($hook)
  {
    if ('woocommerce_page_sku-generator' !== $hook) {
      return;
    }

    wp_enqueue_script(
      'sku-generator',
      plugins_url('js/sku-generator.js', __FILE__),
      array('jquery'),
      '1.0.0',
      true
    );

    wp_localize_script('sku-generator', 'skuGeneratorAjax', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('sku_generator_nonce')
    ));
  }

  public function ajax_generate_bulk_skus()
  {
    check_ajax_referer('sku_generator_nonce', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
      wp_send_json_error('Insufficient permissions');
      return;
    }

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    // Get products without SKUs or with empty SKUs
    $query = new \WC_Product_Query(array(
      'limit' => $this->batch_size,
      'offset' => $offset,
      'orderby' => 'date',
      'order' => 'DESC',
      'status' => 'publish',
      'return' => 'objects',
      'sku' => '',  // This gets both empty and non-existent SKUs
      'meta_query' => array(
        'relation' => 'OR',
        array(
          'key' => '_sku',
          'value' => '',
          'compare' => '='
        ),
        array(
          'key' => '_sku',
          'compare' => 'NOT EXISTS'
        )
      )
    ));

    $products = $query->get_products();
    $total_products = count(wc_get_products(array(
      'limit' => -1,
      'return' => 'ids',
      'sku' => '',
      'meta_query' => array(
        'relation' => 'OR',
        array(
          'key' => '_sku',
          'value' => '',
          'compare' => '='
        ),
        array(
          'key' => '_sku',
          'compare' => 'NOT EXISTS'
        )
      )
    )));

    if (empty($products)) {
      wp_send_json_success(array(
        'complete' => true,
        'message' => __('All SKUs generated successfully!', 'sku-generator')
      ));
      return;
    }

    $options = get_option('sku_generator_options', array());
    $prefix = isset($options['prefix']) ? $options['prefix'] : '';
    $suffix = isset($options['suffix']) ? $options['suffix'] : '';

    foreach ($products as $product) {
      // Double check that the product doesn't already have a non-empty SKU
      $current_sku = $product->get_sku();
      if (empty($current_sku)) {
        $sku = $this->generate_unique_sku($prefix, $suffix, $product);
        $product->set_sku($sku);
        $product->save();
      }
    }

    $progress = min(100, round(($offset + $this->batch_size) / $total_products * 100));

    wp_send_json_success(array(
      'complete' => false,
      'offset' => $offset + $this->batch_size,
      'progress' => $progress,
      'total' => $total_products
    ));
  }

  private function generate_unique_sku($prefix, $suffix, $product = null)
  {
    $options = get_option('sku_generator_options');
    $pattern_type = isset($options['pattern_type']) ? $options['pattern_type'] : 'alphanumeric';
    $length = isset($options['pattern_length']) ? intval($options['pattern_length']) : 8;
    $separator = isset($options['separator']) ? $options['separator'] : '-';

    // Build character set based on pattern type
    switch ($pattern_type) {
      case 'numeric':
        $chars = '0123456789';
        break;
      case 'alphabetic':
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        break;
      case 'custom':
        // You can add custom pattern logic here
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        break;
      default: // alphanumeric
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    do {
      $sku_parts = array();

      // Add prefix if set
      if (!empty($prefix)) {
        $sku_parts[] = $prefix;
      }

      // Add category if enabled
      if (!empty($options['include_category']) && $options['include_category'] == '1' && $product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if ($categories && !is_wp_error($categories)) {
          $first_cat = reset($categories);
          $cat_chars = isset($options['category_chars']) ? intval($options['category_chars']) : 2;
          $sku_parts[] = strtoupper(substr($first_cat->slug, 0, $cat_chars));
        }
      }

      // Add date if enabled
      if (!empty($options['include_date']) && $options['include_date'] == '1') {
        $date_format = isset($options['date_format']) ? $options['date_format'] : 'Ymd';
        $sku_parts[] = date($date_format);
      }

      // Add product ID if enabled
      if (!empty($options['include_product_id']) && $options['include_product_id'] == '1' && $product) {
        $sku_parts[] = $product->get_id();
      }

      // Add random part if product ID is not used or pattern type is not set to just use ID
      if (empty($options['include_product_id']) || $options['include_product_id'] != '1') {
        $random = substr(str_shuffle($chars), 0, $length);
        $sku_parts[] = $random;
      }

      // Add suffix if set
      if (!empty($suffix)) {
        $sku_parts[] = $suffix;
      }

      // Combine all parts with separator
      $sku = implode($separator, array_filter($sku_parts));
    } while ($this->sku_exists($sku));

    return $sku;
  }

  private function sku_exists($sku)
  {
    return wc_get_product_id_by_sku($sku) !== 0;
  }
}

// Initialize the plugin
add_action('plugins_loaded', function () {
  if (class_exists('WooCommerce')) {
    new SKU_Generator();
  }
});
