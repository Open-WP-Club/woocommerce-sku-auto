# SKU Generator for WooCommerce

A WordPress plugin that automatically generates SKUs for WooCommerce products that don't have them. Features bulk generation and customizable SKU patterns.

## Features

- Bulk SKU generation for products without SKUs
- Customizable SKU patterns
- HPOS (High-Performance Order Storage) compatible
- Batch processing to handle large product catalogs
- Progress indicator for bulk operations

## Installation

1. Upload the `sku-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the plugin settings through WooCommerce → SKU Generator

## Configuration

### Basic Settings

- **SKU Prefix**: Add a prefix to all generated SKUs
- **SKU Suffix**: Add a suffix to all generated SKUs
- **Separator Character**: Choose a character to separate SKU components (default: -)

### Pattern Options

- **Pattern Type**:
  - Alphanumeric (A-Z, 0-9)
  - Numeric Only (0-9)
  - Alphabetic Only (A-Z)
  - Custom Pattern

- **Pattern Length**: Set the length of the random part (4-32 characters)

### Additional Components

- **Include Product ID**: Use the product's ID in the SKU
- **Include Category**: Include product category code in the SKU
  - **Category Characters**: Number of characters to use from category name (1-5)
- **Include Date**: Add date to SKU
  - **Date Format Options**:
    - YYYYMMDD
    - YYMMDD
    - YYMM
    - YY

## Usage

1. Go to WooCommerce → SKU Generator
2. Configure your desired SKU pattern settings
3. Click "Generate SKUs" to start the bulk generation process
4. Monitor the progress bar for completion status

## Example SKU Patterns

Based on your settings, SKUs can be generated in various formats:

- Basic: `PRE-12345678-SUF`
- With Product ID: `PRE-123-SUF`
- With Category: `PRE-ELEC-12345678-SUF`
- With Date: `PRE-230815-12345678-SUF`
- Full Combination: `PRE-ELEC-230815-123-SUF`

## Important Notes

- The plugin only generates SKUs for products that don't have one
- Existing SKUs are never overwritten
- Process runs in batches to prevent timeouts on large catalogs
- All generated SKUs are guaranteed to be unique

## Technical Requirements

- WordPress 6.0 or higher
- WooCommerce 8.0 or higher
- PHP 7.4 or higher

## Support

For support, feature requests, or bug reports, please create an issue in the plugin's repository.

## License

This plugin is licensed under the GPL v2 or later.


