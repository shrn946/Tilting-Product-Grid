<?php
/**
 * Plugin Name: Tilting Product Grid
 * Description: Add a tilting product grid to your WordPress site.[latest_products] [latest_products columns="3" count="3" categories="category1,category2"]
 * Version: 1.0
 * Author: Hassan Naqvi
 */

function tilting_product_grid_enqueue_scripts() {
    // Enqueue scripts
    wp_enqueue_script('vanilla-tilt', 'https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.0/vanilla-tilt.min.js', array(), '1.7.0', true);
    wp_enqueue_script('tilting-product-grid-script', plugin_dir_url(__FILE__) . 'script.js', array('vanilla-tilt', 'jquery'), '1.0', true);

    // Enqueue styles
    wp_enqueue_style('tailwind-css', 'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/1.8.13/tailwind.min.css', array(), '1.8.13');
    wp_enqueue_style('tilting-product-grid-style', plugin_dir_url(__FILE__) . 'style.css', array('tailwind-css'), '1.0');
}

add_action('wp_enqueue_scripts', 'tilting_product_grid_enqueue_scripts');


function latest_products_shortcode($atts) {
    // Define default attributes
    $atts = shortcode_atts(
        array(
            'columns'    => 3,      // Number of columns in the grid
            'count'      => -1,     // Number of products to display (-1 means all)
            'categories' => '',     // Comma-separated list of specific product categories
        ),
        $atts,
        'latest_products'
    );

    // Get the latest WooCommerce products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => intval($atts['count']), // Convert count to integer
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Check if specific categories are provided
    if (!empty($atts['categories'])) {
        $categories_array = explode(',', $atts['categories']);

        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories_array,
                'operator' => 'IN',
            ),
        );
    }

    $latest_products = new WP_Query($args);

    // Array to store product HTML
    $products_html = array();

    // Loop through the latest products
    while ($latest_products->have_posts()) {
        $latest_products->the_post();

        // Get product data
        $product_id    = get_the_ID();
        $product_title = get_the_title();
        $product_link  = get_permalink();
        $product_image = get_the_post_thumbnail_url($product_id, 'full');

        // Build the product card HTML
        $product_html = '<div class="card rounded-lg hover:shadow-xl relative px-8 py-20">';
        $product_html .= '<h4 class="name absolute top-0 left-0 w-full text-center text-xl font-medium uppercase transition-all duration-500 opacity-0 z-10 text-gray-100">' . esc_html($product_title) . '</h4>';
        $product_html .= '<a href="' . esc_url($product_link) . '" class="buy absolute bottom-0 bg-gray-600 text-white font-medium hover:text-black px-4 py-2 rounded-full transition-all duration-500 opacity-0 z-10 bg-cyberpunk">Buy Now</a>';
        
        // Append a unique identifier to the image class
        $image_class = 'image-' . $product_id;

        $product_html .= '<div class="image ' . esc_attr($image_class) . '">';
        $product_html .= '<style>
            .' . esc_attr($image_class) . '::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: #000 url(\'' . esc_url($product_image) . '\') center/cover;
            }
        </style>';

        $product_html .= '<img src="' . esc_url($product_image) . '" alt="' . esc_attr($product_title) . '" class="product rounded transition-all duration-500">';
        $product_html .= '</div>';
        $product_html .= '</div>';

        // Add product HTML to the array
        $products_html[] = $product_html;
    }

    // Reset post data
    wp_reset_postdata();

    // Start building the overall output
    $output = '<div class="min-h-screen pb-20 grid">';
    $output .= '<div class="grid place-items-center lg:grid-cols-' . esc_attr($atts['columns']) . ' _container gap-y-10">';
    
    // Output each product HTML from the array
    $output .= implode('', $products_html);

    // Close the HTML tags
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

add_shortcode('latest_products', 'latest_products_shortcode');
