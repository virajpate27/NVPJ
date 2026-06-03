<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'ogg','jws-jwsicon','jws-default','magnificPopup','slick','awesome' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION






// Add the PAN Card Field
// add_filter('woocommerce_checkout_fields', 'add_pan_card_checkout_field');

// function add_pan_card_checkout_field($fields) {
//     $fields['billing']['billing_pan_card'] = array(
//         'label'       => __('PAN Card Number is required for orders above ₹2,00,000', 'woocommerce'),
//         'placeholder' => _x('ABCDE1234F', 'placeholder', 'woocommerce'),
//         'required'    => true,
//         'class'       => array('form-row-wide'),
//         'clear'       => true,
//         'priority'    => 120,
//     );
//     return $fields;
// }
// Save the Field to Order Meta
add_action('woocommerce_checkout_update_order_meta', 'save_pan_card_checkout_field');

function save_pan_card_checkout_field($order_id) {
    if (!empty($_POST['billing_pan_card'])) {
        update_post_meta($order_id, '_billing_pan_card', sanitize_text_field($_POST['billing_pan_card']));
    }
}
// Display PAN Field in Admin Order Page
add_action('woocommerce_admin_order_data_after_billing_address', 'display_pan_card_admin_order_meta', 10, 1);

function display_pan_card_admin_order_meta($order) {
    $pan_card = get_post_meta($order->get_id(), '_billing_pan_card', true);
    if ($pan_card) {
        echo '<p><strong>' . __('PAN Card Number') . ':</strong> ' . esc_html($pan_card) . '</p>';
    }
}
// Show PAN in Email
add_filter('woocommerce_email_order_meta_fields', 'add_pan_to_order_email', 10, 3);

function add_pan_to_order_email($fields, $sent_to_admin, $order) {
    $fields['billing_pan_card'] = array(
        'label' => __('PAN Card Number'),
        'value' => get_post_meta($order->get_id(), '_billing_pan_card', true),
    );
    return $fields;
}

// Update PHP to Add PAN Card Field as Not Required by Default
add_filter('woocommerce_checkout_fields', 'add_pan_card_checkout_field');

function add_pan_card_checkout_field($fields) {
    $fields['billing']['billing_pan_card'] = array(
        'label'       => __('PAN Card Number is required for orders above ₹2,00,000', 'woocommerce'),
        'placeholder' => _x('ABCDE1234F', 'placeholder', 'woocommerce'),
        'required'    => false, // Not required by default
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'priority'    => 120,
    );
    return $fields;
}
// Add JavaScript to Make It Required Only If Total > 2 Lakhs
add_action('woocommerce_after_checkout_form', 'pan_card_logic_script');

function pan_card_logic_script() {
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            function togglePanCardRequirement() {
                var total = wc_checkout_params.cart_total.replace(/[^\\d.]/g, '');
                total = parseFloat(total);

                if (total > 200000) {
                    $('#billing_pan_card').attr('required', 'required');
                    $('label[for="billing_pan_card"] .optional').remove(); // Remove optional mark if any
                } else {
                    $('#billing_pan_card').removeAttr('required');
                    if ($('label[for="billing_pan_card"] .optional').length === 0) {
                        $('label[for="billing_pan_card"]').append('<span class="optional"> (optional)</span>');
                    }
                }
            }

            togglePanCardRequirement();

            // Recheck when totals update
            $('body').on('updated_checkout', function() {
                togglePanCardRequirement();
            });
        });
    </script>
    <?php
}
// Validate Server-Side (Important!)
add_action('woocommerce_checkout_process', 'validate_pan_card_for_high_value_orders');

function validate_pan_card_for_high_value_orders() {
    $total = WC()->cart->get_total('edit');
    
    if ($total > 200000 && empty($_POST['billing_pan_card'])) {
        wc_add_notice(__('PAN Card Number is required for orders above ₹2,00,000.', 'woocommerce'), 'error');
    }
}

// PAN Validation PHP – Backend
add_action('woocommerce_checkout_process', 'validate_pan_card_format');

function validate_pan_card_format() {
    if (!empty($_POST['billing_pan_card'])) {
        $pan = strtoupper(trim($_POST['billing_pan_card'])); // Normalize input
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
            wc_add_notice(__('Please enter a valid PAN Card Number (e.g., ABCDE1234F).', 'woocommerce'), 'error');
        }
    }
}
// PAN Format Validation JavaScript – Frontend, Optional
add_action('woocommerce_after_checkout_form', 'validate_pan_card_frontend');

function validate_pan_card_frontend() {
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            $('form.checkout').on('checkout_place_order', function() {
                let pan = $('#billing_pan_card').val().toUpperCase().trim();
                let panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;

                if ($('#billing_pan_card').attr('required') && !panRegex.test(pan)) {
                    alert('Please enter a valid PAN Card Number (e.g., ABCDE1234F).');
                    return false; // Prevent submission
                }

                return true;
            });
        });
    </script>
    <?php
}




//Ring Size Guide
add_action('woocommerce_after_variations_table', 'add_ring_size_helper_text_only_rings');

function add_ring_size_helper_text_only_rings() {
    if (!is_product()) return;

    global $product;

    // Change 'rings' to your actual category slug if different
    if (!has_term('rings', 'product_cat', $product->get_id())) return;
    ?>
    <div class="ring-size-helper" style="margin-top: -20px; margin-left: 125px; margin-bottom: 20px;">
        <a href="https://nvpjewellery.com/wp-content/uploads/2026/02/NVP-Ring-sizer.pdf" target="_blank"
           style="font-size:14px; color:#000224; text-decoration:underline;">
             Ring Size Guide
        </a>
    </div>
    <?php
}

// Gold Price Break API
add_action('woocommerce_before_add_to_cart_button', 'custom_gold_karat_selector');

function custom_gold_karat_selector() {
    global $post;

    // Get meta values
    $weight_9k = get_post_meta($post->ID, 'gold_weight_9k', true);
    $making_9k = get_post_meta($post->ID, 'making_charge_9k', true);
    $weight_14k = get_post_meta($post->ID, 'gold_weight_14k', true);
    $making_14k = get_post_meta($post->ID, 'making_charge_14k', true);
    $weight_18k = get_post_meta($post->ID, 'gold_weight_18k', true);
    $making_18k = get_post_meta($post->ID, 'making_charge_18k', true);

    $rate_9k  = get_option('gold_rate_9k', 2800);
    $rate_14k = get_option('gold_rate_14k', 4300);
    $rate_18k = get_option('gold_rate_18k', 5700);
    $diamond_price = floatval(get_post_meta($post->ID, 'diamond_price', true));
    $color_stone_price = floatval(get_post_meta($post->ID, 'color_stone_price', true)); // NEW
    $gst_rate = 0.03;

    echo '
    <style>
    .accordion-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #000224;
            color: #ffffff;
            padding: 9px 15px;
            cursor: pointer;
            font-weight: 500;
            font-size: 15px;
            border-radius: 0px;
            margin-bottom: 5px;
        }
        .accordion-toggle:hover {
            background: #000224;
            color: #ccad50 !important;
        }
    </style>
    
    <div style="margin-bottom:20px;">
        <label style="margin-right:45px;">Purity</label>
        <label class="9kc hdd" style="margin-left:20px;"><input type="radio" name="karat" value="9k"> 9Kt</label>
        <label class="14kc hdd" style="margin-left:10px;"><input type="radio" name="karat" value="14k"> 14Kt</label>
        <label class="18kc" style="margin-left:10px;"><input type="radio" name="karat" value="18k" checked> 18Kt</label>
        <input type="hidden" id="selected_karat" name="selected_karat" value="18k">
        <input type="hidden" id="diamond_price_hidden" value="' . esc_attr($diamond_price) . '">
        <input type="hidden" id="color_stone_price_hidden" value="' . esc_attr($color_stone_price) . '"> <!-- NEW -->
    </div>

    <div class="accordion-wrapper" style="margin-top: 10px;">
        <div class="accordion-toggle" style="background-color: #000224; color: #fff; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
            <span>Price Breakup</span>
            <span class="arrow" style="font-size: 18px;">&#9660;</span>
        </div>
        <div class="accordion-content" style="display: block; background: #fff9f594; padding: 15px; border-radius: 0 0 6px 6px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tbody>
            <tr style="border-bottom: 1px dashed #ccad50;">
                <th style="text-align: left; padding: 6px; font-size: 14px;">Gold Karat</th>
                <td style="padding: 6px; font-size: 14px;"><span id="karat-label"></span>t</td>
            </tr>
            <tr style="border-bottom: 1px dashed #ccad50;">
                <th style="text-align: left; padding: 6px; font-size: 14px;">Weight</th>
                <td style="padding: 6px; font-size: 14px;"><span id="weight"></span>gm</td>
            </tr>
            <tr style="border-bottom: 1px dashed #ccad50;">
                <th style="text-align: left; padding: 6px; font-size: 14px;">Gold Price</th>
                <td style="padding: 6px; font-size: 14px;">₹<span id="base"></span></td>
            </tr>
            <tr style="border-bottom: 1px dashed #ccad50;" id="diamond-price-row">
                <th style="text-align:left; padding:6px; font-size: 14px;">Diamond Price</th>
                <td style="padding:6px; font-size: 14px;">₹<span id="diamond-price"></span></td>
            </tr>
            <tr style="border-bottom: 1px dashed #ccad50;" id="color-stone-price-row"> <!-- NEW ROW -->
                <th style="text-align:left; padding:6px; font-size: 14px;">Color Stone Price</th>
                <td style="padding:6px; font-size: 14px;">₹<span id="color-stone-price"></span></td>
            </tr>
            <tr style="border-bottom: 1px dashed #ccad50;">
                <th style="text-align: left; padding: 6px; font-size: 14px;">Making Charges</th>
                <td style="padding: 6px; font-size: 14px;">₹<span id="making"></span></td>
            </tr>
            <tr style="border-bottom: 1px dashed #000000;">
                <th style="text-align: left; padding: 6px; font-size: 14px;">GST</th>
                <td style="padding: 6px; font-size: 14px;">₹<span id="gst"></span></td>
            </tr>
            <tr>
                <th style="text-align: left; padding: 6px; font-size: 16px; color: #000000; font-weight: 500;">Total Price</th>
                <td style="padding: 6px; color: #000000; font-size: 16px; font-weight: 500;">₹<span id="total"></span></td>
            </tr>
        </tbody>
    </table>
</div>

    </div>

    <script>
        const rates = {
         "9k": ' . esc_js($rate_9k) . ',
         "14k": ' . esc_js($rate_14k) . ',
         "18k": ' . esc_js($rate_18k) . '
        };
        const gstRate = ' . esc_js($gst_rate) . ';
        const weights = {
    "9k": ' . floatval($weight_9k) . ',
    "14k": ' . floatval($weight_14k) . ',
    "18k": ' . floatval($weight_18k) . '
        };
        const makings = {
    "9k": ' . floatval($making_9k) . ',
    "14k": ' . floatval($making_14k) . ',
    "18k": ' . floatval($making_18k) . '
    };

    function formatINR(value) {
        return Math.round(value).toLocaleString("en-IN");
    }

    function updatePriceBreakup(karat) {
        const rate = rates[karat];
        const weight = weights[karat];
        const making = makings[karat];
        const diamond = Number(document.getElementById("diamond_price_hidden")?.value) || 0;
        const colorStone = Number(document.getElementById("color_stone_price_hidden")?.value) || 0; // NEW

        const base = rate * weight;
        const gst = (base + making + diamond + colorStone) * gstRate; // INCLUDE COLOR STONE
        const total = base + making + diamond + colorStone + gst; // INCLUDE COLOR STONE

        document.getElementById("karat-label").innerText = karat.toUpperCase();
        document.getElementById("weight").innerText = weight;
        document.getElementById("base").innerText = formatINR(base);
        
        // Diamond row
        const diamondRow = document.getElementById("diamond-price-row");
        if (diamond > 0) {
            document.getElementById("diamond-price").innerText = formatINR(diamond);
            diamondRow.style.display = "";
        } else {
            diamondRow.style.display = "none";
        }
        
        // Color Stone row - NEW
        const colorStoneRow = document.getElementById("color-stone-price-row");
        if (colorStone > 0) {
            document.getElementById("color-stone-price").innerText = formatINR(colorStone);
            colorStoneRow.style.display = "";
        } else {
            colorStoneRow.style.display = "none";
        }
        
        document.getElementById("making").innerText = formatINR(making);
        document.getElementById("gst").innerText = formatINR(gst);
        document.getElementById("total").innerText = formatINR(total);

        const priceEl = document.getElementById("custom-total-price");
        if (priceEl) {
            priceEl.innerText = "₹" + formatINR(total);
        }
    }

        // Default selection
        updatePriceBreakup("18k");

        // Change on radio
        document.querySelectorAll("input[name=karat]").forEach(radio => {
    radio.addEventListener("change", function () {
        document.getElementById("selected_karat").value = this.value;
        updatePriceBreakup(this.value);
    });
});

        // Accordion toggle
        document.querySelector(".accordion-toggle").addEventListener("click", function () {
            const content = document.querySelector(".accordion-content");
            const arrow = document.querySelector(".accordion-toggle .arrow");
            if (content.style.display === "none") {
                content.style.display = "block";
                arrow.innerHTML = "&#9650;";
            } else {
                content.style.display = "none";
                arrow.innerHTML = "&#9660;";
            }
        });
    </script>';
}


// Hide Default WooCommerce Price & Inject Custom One
add_filter('woocommerce_get_price_html', 'override_gold_shop_price_display', 999, 2);
function override_gold_shop_price_display($price, $product) {
    if (is_product()) {
        return '<span id="custom-total-price" style="font-size: 24px; color: #000224;"></span>';
    }

    if (is_shop() || is_product_category() || is_product_tag() || is_archive() || is_home()) {
        $product_id = $product->get_id();

        // Define rates
       $rate_9k  = get_option('gold_rate_9k', 2800);
       $rate_14k = get_option('gold_rate_14k', 4300);
       $rate_18k = get_option('gold_rate_18k', 5700);


        // Get meta values
        $weight_9k = floatval(get_post_meta($product_id, "gold_weight_9k", true));
        $making_9k = floatval(get_post_meta($product_id, "making_charge_9k", true));
		
		$weight_14k = floatval(get_post_meta($product_id, "gold_weight_14k", true));
        $making_14k = floatval(get_post_meta($product_id, "making_charge_14k", true));

        $weight_18k = floatval(get_post_meta($product_id, "gold_weight_18k", true));
        $making_18k = floatval(get_post_meta($product_id, "making_charge_18k", true));

        // Calculate totals
        $gst_9k = ($rate_9k * $weight_9k + $making_9k) * 0.03;
        $total_9k = ($rate_9k * $weight_9k) + $making_9k + $gst_9k;
		
		$gst_14k = ($rate_14k * $weight_14k + $making_14k) * 0.03;
        $total_14k = ($rate_14k * $weight_14k) + $making_14k + $gst_14k;

        $gst_18k = ($rate_18k * $weight_18k + $making_18k) * 0.03;
        $total_18k = ($rate_18k * $weight_18k) + $making_18k + $gst_18k;

        // Only show if values exist
        if ($total_9k > 0 && $total_18k > 0) {
            $min = min($total_9k, $total_18k);
            $max = max($total_9k, $total_18k);
            return '<span class="custom-price-shop">₹' . number_format(round($min)) . ' – ₹' . number_format(round($max)) . '</span>';
        } elseif ($total_9k > 0) {
            return '<span class="custom-price-shop" style="font-size: 16px; color: #000224;">₹' . number_format(round($total_9k)) . ' (14KT)</span>';
        } elseif ($total_18k > 0) {
            return '<span class="custom-price-shop" style="font-size: 16px; color: #000224;">₹' . number_format(round($total_18k)) . ' (18KT)</span>';
        }
    }

    return $price;
}

// Save selected karat to cart item
add_filter('woocommerce_add_cart_item_data', 'custom_gold_add_cart_item_data', 10, 3);
function custom_gold_add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    if (!empty($_POST['selected_karat'])) {
        $cart_item_data['gold_karat'] = sanitize_text_field($_POST['selected_karat']);
    }
    return $cart_item_data;
}

// Save diamond price to cart item
add_filter('woocommerce_add_cart_item_data', 'custom_add_diamond_price_to_cart', 10, 2);
function custom_add_diamond_price_to_cart($cart_item_data, $product_id) {

    $diamond_price = floatval(get_post_meta($product_id, 'diamond_price', true));

    if ($diamond_price > 0) {
        $cart_item_data['diamond_price'] = $diamond_price;
    }

    return $cart_item_data;
}

// Restore karat from session
add_filter('woocommerce_get_cart_item_from_session', 'custom_gold_get_cart_item_from_session', 10, 2);
function custom_gold_get_cart_item_from_session($item, $values) {
    if (!empty($values['gold_karat'])) {
        $item['gold_karat'] = $values['gold_karat'];
    }
    return $item;
}

// Change the price in cart based on selected karat
add_action('woocommerce_before_calculate_totals', 'custom_gold_set_cart_price', 10);
function custom_gold_set_cart_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!isset($cart_item['gold_karat'])) continue;

        $karat = $cart_item['gold_karat'];
        $product_id = $cart_item['product_id'];

        $rate_9k  = get_option('gold_rate_9k', 2800);
        $rate_14k = get_option('gold_rate_14k', 4300);
        $rate_18k = get_option('gold_rate_18k', 5700);
        $rate = ($karat === '9k') ? $rate_9k : (($karat === '14k') ? $rate_14k : $rate_18k);

        $weight = floatval(get_post_meta($product_id, 'gold_weight_' . $karat, true));
        $making = floatval(get_post_meta($product_id, 'making_charge_' . $karat, true));
        $diamond = !empty($cart_item['diamond_price']) ? $cart_item['diamond_price'] : 0;
        $color_stone = floatval(get_post_meta($product_id, 'color_stone_price', true)); // NEW

        $gst = (($rate * $weight) + $making + $diamond + $color_stone) * 0.03; // INCLUDE COLOR STONE
        $total = ($rate * $weight) + $making + $diamond + $color_stone + $gst; // INCLUDE COLOR STONE

        if ($total > 0) {
           $cart_item['data']->set_price(round($total));
        }
    }
}

// Save color stone price to cart item
add_filter('woocommerce_add_cart_item_data', 'custom_add_color_stone_price_to_cart', 10, 2);
function custom_add_color_stone_price_to_cart($cart_item_data, $product_id) {
    $color_stone_price = floatval(get_post_meta($product_id, 'color_stone_price', true));
    if ($color_stone_price > 0) {
        $cart_item_data['color_stone_price'] = $color_stone_price;
    }
    return $cart_item_data;
}


// Show selected karat in cart/checkout
add_filter('woocommerce_get_item_data', 'custom_gold_cart_item_display', 10, 2);
function custom_gold_cart_item_display($item_data, $cart_item) {
    if (!empty($cart_item['gold_karat'])) {
        $item_data[] = array(
            'key' => 'Gold Karat',
            'value' => strtoupper($cart_item['gold_karat']) .'t', 
        );
    }
    return $item_data;
}

// Add Breakdown to Cart and Checkout
add_filter('woocommerce_get_item_data', 'custom_gold_price_breakdown_cart_qty', 10, 2);
function custom_gold_price_breakdown_cart_qty($item_data, $cart_item) {
    if (!isset($cart_item['gold_karat'])) return $item_data;

    $karat = $cart_item['gold_karat'];
    $product_id = $cart_item['product_id'];
    $qty = isset($cart_item['quantity']) ? intval($cart_item['quantity']) : 1;

    $rate_9k  = get_option('gold_rate_9k', 2800);
    $rate_14k = get_option('gold_rate_14k', 4300);
    $rate_18k = get_option('gold_rate_18k', 5700);
    $rate = ($karat === '9k') ? $rate_9k : (($karat === '14k') ? $rate_14k : $rate_18k);

    $weight = floatval(get_post_meta($product_id, "gold_weight_{$karat}", true));
    $making = floatval(get_post_meta($product_id, "making_charge_{$karat}", true));
    $diamond = !empty($cart_item['diamond_price']) ? $cart_item['diamond_price'] : 0;
    $color_stone = floatval(get_post_meta($product_id, 'color_stone_price', true)); // NEW

    $base = $rate * $weight;
    $gst = round(($base + $making + $diamond + $color_stone) * 0.03); // INCLUDE COLOR STONE
    $total = round($base + $making + $diamond + $color_stone + $gst); // INCLUDE COLOR STONE

    // Multiply by quantity
    $base_total = $base * $qty;
    $making_total = $making * $qty;
    $diamond_total = $diamond * $qty;
    $color_stone_total = $color_stone * $qty; // NEW
    $gst_total = $gst * $qty;
    $grand_total = $total * $qty;

    $item_data[] = ['key' => 'Weight', 'value' => ($weight * $qty) . ' gm'];
    $item_data[] = ['key' => 'Gold Price ', 'value' => '₹' . number_format(round($base_total))];
    
    if ($diamond > 0) {
        $item_data[] = ['key' => 'Diamond Price ', 'value' => '₹' . number_format(round($diamond_total))];
    }
    
    if ($color_stone > 0) { // NEW
        $item_data[] = ['key' => 'Color Stone Price ', 'value' => '₹' . number_format(round($color_stone_total))];
    }
    
    $item_data[] = ['key' => 'Making Charges ', 'value' => '₹' . number_format(round($making_total))];
    $item_data[] = ['key' => 'GST', 'value' => '₹' . number_format(round($gst_total))];
    $item_data[] = ['key' => 'Total Price', 'value' => '₹' . number_format(round($grand_total))];

    return $item_data;
}


// Show Breakdown in Order Details (Thank You Page & My Account)
add_action('woocommerce_order_item_meta_end', 'custom_gold_price_breakdown_order_qty', 10, 4);

function custom_gold_price_breakdown_order_qty($item_id, $item, $order, $plain_text) {

    $karat = $item->get_meta('gold_karat');
    if (!$karat) return;

    $qty = $item->get_quantity();

    // GET SAVED VALUES (NOT PRODUCT META)
    $weight = floatval($item->get_meta('weight'));
    $making = floatval($item->get_meta('making'));
    $diamond = floatval($item->get_meta('diamond'));
    $color_stone = floatval($item->get_meta('color_stone'));

    // GET RATE
    $rate_9k  = get_option('gold_rate_9k', 2800);
    $rate_14k = get_option('gold_rate_14k', 4300);
    $rate_18k = get_option('gold_rate_18k', 5700);

    $rate = ($karat === '9k') ? $rate_9k : (($karat === '14k') ? $rate_14k : $rate_18k);

    // CALCULATION
    $base = $rate * $weight;
    $gst = round(($base + $making + $diamond + $color_stone) * 0.03);
    $total = round($base + $making + $diamond + $color_stone + $gst);

    // MULTIPLY BY QTY
    $base_total = $base * $qty;
    $making_total = $making * $qty;
    $diamond_total = $diamond * $qty;
    $color_stone_total = $color_stone * $qty;
    $gst_total = $gst * $qty;
    $grand_total = $total * $qty;

    echo '<div style="margin-top:8px;">';
	echo '<strong>Gold Karat:</strong> ' . strtoupper($karat) . '<br>';
    echo '<strong>Weight:</strong> ' . ($weight * $qty) . ' gm<br>';
    echo '<strong>Gold Price :</strong> ₹' . number_format(round($base_total)) . '<br>';

    if ($diamond > 0) {
        echo '<strong>Diamond Price :</strong> ₹' . number_format(round($diamond_total)) . '<br>';
    }

    if ($color_stone > 0) {
        echo '<strong>Color Stone Price :</strong> ₹' . number_format(round($color_stone_total)) . '<br>';
    }

    echo '<strong>Making Charges :</strong> ₹' . number_format(round($making_total)) . '<br>';
    echo '<strong>GST:</strong> ₹' . number_format(round($gst_total)) . '<br>';
    echo '<strong>Total:</strong> ₹' . number_format(round($grand_total)) . '<br>';
    echo '</div>';
}

// Store Karat in Order Meta (If not already)
add_action('woocommerce_checkout_create_order_line_item', 'save_gold_karat_to_order', 10, 4);
function save_gold_karat_to_order($item, $cart_item_key, $values, $order) {

    if (!empty($values['gold_karat'])) {
        $item->add_meta_data('gold_karat', $values['gold_karat'], true);
    }

    // SAVE ALL DATA
    $product_id = $values['product_id'];
    $karat = $values['gold_karat'];

    $weight = floatval(get_post_meta($product_id, "gold_weight_{$karat}", true));
    $making = floatval(get_post_meta($product_id, "making_charge_{$karat}", true));
    $diamond = !empty($values['diamond_price']) ? $values['diamond_price'] : 0;
    $color_stone = !empty($values['color_stone_price']) ? $values['color_stone_price'] : 0;

    $item->add_meta_data('weight', $weight, true);
    $item->add_meta_data('making', $making, true);
    $item->add_meta_data('diamond', $diamond, true);
    $item->add_meta_data('color_stone', $color_stone, true);
}

// ---- make these gold rates editable from the WordPress admin -----
// Create Settings Fields for Gold Rates
// Add a settings section and fields to WooCommerce > Settings > Products
add_filter('woocommerce_get_sections_products', 'add_gold_rates_section');
function add_gold_rates_section($sections) {
    $sections['gold_rates'] = __('Gold Rates', 'your-textdomain');
    return $sections;
}

// Add fields to the new section
add_filter('woocommerce_get_settings_products', 'add_gold_rate_fields', 10, 2);
function add_gold_rate_fields($settings, $current_section) {
    if ($current_section === 'gold_rates') {
        $settings = [
            [
                'name' => __('Gold Rate Settings', 'your-textdomain'),
                'type' => 'title',
                'desc' => 'Enter gold price per gram for each karat.',
                'id'   => 'gold_rate_section_title'
            ],
            [
    'name' => __('9Kt Gold Rate', 'your-textdomain'),
    'id'   => 'gold_rate_9k',
    'type' => 'number',
    'css'  => 'width:100px;',
    'desc' => 'Price per gram for 9Kt',
    'default' => 2800
],
[
    'name' => __('14Kt Gold Rate', 'your-textdomain'),
    'id'   => 'gold_rate_14k',
    'type' => 'number',
    'css'  => 'width:100px;',
    'desc' => 'Price per gram for 14Kt',
    'default' => 4300
],
[
    'name' => __('18Kt Gold Rate', 'your-textdomain'),
    'id'   => 'gold_rate_18k',
    'type' => 'number',
    'css'  => 'width:100px;',
    'desc' => 'Price per gram for 18Kt',
    'default' => 5700
],
            [
                'type' => 'sectionend',
                'id' => 'gold_rate_section_end'
            ]
        ];
    }
    return $settings;
}













add_action('woocommerce_product_meta_end', 'custom_product_meta_message');

function custom_product_meta_message() {
    echo '<p class="ctest">
        Have a customization request? We’re here to help—feel free to reach out to us at 
        <a href="tel:+919136152476" style="color:#000224; font-weight:500;">+91 91361 52476</a>.
    </p>';
}

// Custom text for specific WooCommerce category

add_action('woocommerce_before_add_to_cart_button', 'custom_category_text_message', 25);

function custom_category_text_message() {

    global $product;

    // Category slug
    if ( has_term('rings', 'product_cat', $product->get_id()) ) {

        echo '<div class="custom-category-message">
                The displayed price is for Ring Size 7. Pricing for other sizes may vary based on the final metal weight.
              </div>';
    }

}

// Custom text for specific Specific products
add_action('woocommerce_product_meta_end', 'custom_text_for_specific_products');

function custom_text_for_specific_products() {

    global $product;

    $product_ids = array(18184, 18191, 18101); // Replace with your product IDs

    if ( in_array($product->get_id(), $product_ids) ) {

        echo '<div class="custom-product-message">
                Please contact us at +91 91361 52476
              </div>';
    }
}

@include_once dirname(__FILE__) . '/more-functions.php';
