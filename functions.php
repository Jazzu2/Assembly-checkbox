// Display the custom checkbow field in checkout
add_action( 'woocommerce_review_order_before_order_total', 'fee_installment_checkbox_field', 20 );
function fee_installment_checkbox_field(){
    echo '<tr class="packing-select"><th>';

    woocommerce_form_field( 'installment_fee', array(
        'type'          => 'checkbox',
        'class'         => array('installment-fee form-row-wide'),
        'label'         => __('Montaža v 14 dneh (predpripravljen prostor) - 170€'),
        'placeholder'   => __('Za montažo mora biti prostor predpripravljen!'),
    ), WC()->session->get('installment_fee') ? '1' : '' );

    echo '</th><td>';
}

// jQuery - Ajax script
add_action( 'wp_footer', 'checkout_fee_script' );
function checkout_fee_script() {
    // Only on Checkout
    if( is_checkout() && ! is_wc_endpoint_url() ) :

    if( WC()->session->__isset('installment_fee') )
        WC()->session->__unset('installment_fee')
    ?>
    <script type="text/javascript">
    jQuery( function($){
        if (typeof wc_checkout_params === 'undefined')
            return false;

        $('form.checkout').on('change', 'input[name=installment_fee]', function(){
            var fee = $(this).prop('checked') === true ? '1' : '';

            $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                    'action': 'installment_fee',
                    'installment_fee': fee,
                },
                success: function (result) {
                    $('body').trigger('update_checkout');
                },
            });
        });
    });
    </script>
    <?php
    endif;
}

// Get Ajax request and saving to WC session
add_action( 'wp_ajax_installment_fee', 'get_installment_fee' );
add_action( 'wp_ajax_nopriv_installment_fee', 'get_installment_fee' );
function get_installment_fee() {
    if ( isset($_POST['installment_fee']) ) {
        WC()->session->set('installment_fee', ($_POST['installment_fee'] ? true : false) );
    }
    die();
}


// Add a custom calculated fee conditionally
add_action( 'woocommerce_cart_calculate_fees', 'set_installment_fee' );
function set_installment_fee( $cart ){
    if ( is_admin() && ! defined('DOING_AJAX') || ! is_checkout() )
        return;

    if ( did_action('woocommerce_cart_calculate_fees') >= 2 )
        return;

    if ( 1 == WC()->session->get('installment_fee') ) {
        $items_count = WC()->cart->get_cart_contents_count();
        $fee_label   = sprintf( __( "Montaža" ), '&times;', $items_count );
        $fee_amount  = 170 * $items_count;
        WC()->cart->add_fee( $fee_label, $fee_amount );
    }
}

add_filter( 'woocommerce_form_field' , 'remove_optional_txt_from_installment_checkbox', 10, 4 );
function remove_optional_txt_from_installment_checkbox( $field, $key, $args, $value ) {
    // Only on checkout page for Order notes field
    if( 'installment_fee' === $key && is_checkout() ) {
        $optional = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
        $field = str_replace( $optional, '', $field );
    }
    return $field;
}












/* SECOND OPTION FOR THE CHECKBOX */

// Part 1
// Display Radio Buttons
add_action( 'woocommerce_review_order_before_payment', 'ahirwp_checkout_radio_choice' );
function ahirwp_checkout_radio_choice() {
   $chosen = WC()->session->get( 'radio_chosen' );
   $chosen = empty( $chosen ) ? WC()->checkout->get_value( 'radio_choice' ) : $chosen;
   $chosen = empty( $chosen ) ? '0' : $chosen;
   $args = array(
   'type' => 'radio',
   'class' => array( 'form-row-wide', 'update_totals_on_change' ),
   'options' => array(
      '0' => 'Brez montaže',
      '170' => 'Montaža - 170 €',
   ),
   'default' => $chosen
   );
   echo '<div id="checkout-radio">';
   echo '<h3>Montaža v 14 dneh (prostor mora biti predpripravljen!)</h3>';
   woocommerce_form_field( 'radio_choice', $args, $chosen );
   echo '</div>';
}
// Part 2
// Add Fee and Calculate Total
add_action( 'woocommerce_cart_calculate_fees', 'ahirwp_checkout_radio_choice_fee', 20, 1 );
function ahirwp_checkout_radio_choice_fee( $cart ) {
   if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
   $radio = WC()->session->get( 'radio_chosen' );
   if ( $radio ) {
      $cart->add_fee( 'Montaža', $radio );
   }
}
// Part 3
// Add Radio Choice to Session
add_action( 'woocommerce_checkout_update_order_review', 'ahirwp_checkout_radio_choice_set_session' );
function ahirwp_checkout_radio_choice_set_session( $posted_data ) {
    parse_str( $posted_data, $output );
    if ( isset( $output['radio_choice'] ) ){
        WC()->session->set( 'radio_chosen', $output['radio_choice'] );
    }
}
