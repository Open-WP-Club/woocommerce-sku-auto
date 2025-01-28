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

    // Get products without SKUs using WC_Product_Query
    $query = new \WC_Product_Query(array(
      'limit' => $this->batch_size,
      'offset' => $offset,
      'orderby' => 'date',
      'order' => 'DESC',
      'status' => 'publish',
      'return' => 'objects',
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
      $sku = $this->generate_unique_sku($prefix, $suffix);
      $product->set_sku($sku);
      $product->save();
    }

    $progress = min(100, round(($offset + $this->batch_size) / $total_products * 100));

    wp_send_json_success(array(
      'complete' => false,
      'offset' => $offset + $this->batch_size,
      'progress' => $progress,
      'total' => $total_products
    ));
  }

  private function generate_unique_sku($prefix, $suffix)
  {
    do {
      $random = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
      $sku = $prefix . $random . $suffix;
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
