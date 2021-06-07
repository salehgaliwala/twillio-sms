<?php
// Add your custom order status action button (for orders with "processing" status)
add_filter( 'woocommerce_admin_order_actions', 'add_custom_order_status_actions_button', 100, 2 );
function add_custom_order_status_actions_button( $actions, $order ) {
    // Display the button for all orders that have a 'processing' status
    if ( $order->has_status( array( 'on-hold' ) ) ) {

        // Get Order ID (compatibility all WC versions)
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
        // Set the action button
        $actions['parcial'] = array(
            'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=sendsms&status=hold&order_id=' . $order_id ), 'woocommerce-mark-order-status' ),
            'name'      => __( 'Send SMS', 'woocommerce' ),
            'action'    => "sendsms", // keep "view" class for a clean button CSS
            
        );
    }
    return $actions;
}

// Set Here the WooCommerce icon for your action button
add_action( 'admin_head', 'add_custom_order_status_actions_button_css' );
function add_custom_order_status_actions_button_css() {
    echo '<style>.sendsms::after { font-family: woocommerce !important; content: "\e005" !important; }</style>';
}
add_action('wp_ajax_sendsms', 'sendsms' );
function sendsms()
{
// Require the bundled autoload file - the path may need to change
// based on where you downloaded and unzipped the SDK


// Use the REST API Client to make requests to the Twilio REST API


// Your Account SID and Auth Token from twilio.com/console
$sid = 'Account_SID';
$token = 'Token';


$order_id = $_REQUEST['order_id'];

$order = wc_get_order( $order_id );
$billing_phone  = $order->get_billing_phone();

// Use the client to do fun stuff like send text messages!
$billing_first_name = $order->get_billing_first_name();
$billing_last_name  = $order->get_billing_last_name();
$id = $sid;
$token = $token;
$url = "https://api.twilio.com/2010-04-01/Accounts/$id/SMS/Messages";
$from = "From_number";
$to = $billing_phone ; // twilio trial verified number
$template =  get_option('woocommerce_order_sms_template');
$template = str_replace('{{billing_first_name}}',$billing_first_name,$template);
$template = str_replace('{{order_id}}',$order_id,$template);
//$body = "Dear ".$billing_first_name.", Your transfer for Order #".$order_id." is not confirmed yet. Confirm it to proceed your order. cigsnet.com";
$body = $template;
$data = array (
    'From' => $from,
    'To' => $to,
    'Body' => $body,
);
$post = http_build_query($data);
$x = curl_init($url );
curl_setopt($x, CURLOPT_POST, true);
curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($x, CURLOPT_USERPWD, "$id:$token");
curl_setopt($x, CURLOPT_POSTFIELDS, $post);
$y = curl_exec($x);
curl_close($x);
var_dump($post);
var_dump($y);
}

add_action( 'admin_footer', 'smsJS' );

function smsJS()
{
    ?>
<script>
jQuery(document).ready(function() {
    jQuery('body').on('click', '.wc-action-button-sendsms', function(e) {
        e.preventDefault();
        var url = jQuery(this).attr('href');
        var t = jQuery(this);
        jQuery.ajax({
            url: url,
            type: 'get',
            t: t,
            data: {
                action: 'sendsms',
                data: 1
            },
            success: function(response) {
                jQuery(t).closest('.column-wc_actions').html('SMS Sent');
            }
        });

    });
});
</script>
<?php    
}

function add_order_sms_template( $settings ) {

    $updated_settings = array();
  
    foreach ( $settings as $section ) {
  
      // at the bottom of the General Options section
      if ( isset( $section['id'] ) && 'general_options' == $section['id'] &&
         isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
  
        $updated_settings[] = array(
          'name'     => __( 'Order SMS', 'wc_order_sms' ),
          'desc_tip' => __( 'This is SMS template', 'wc_order_sms' ),
          'id'       => 'woocommerce_order_sms_template',
          'type'     => 'textarea',
          'css'      => 'min-width:300px;',
          'std'      => '1',  // WC < 2.0
          'default'  => '1',  // WC >= 2.0
          'desc'     => __( '', 'wc_seq_order_numbers' ),
        );
      }
  
      $updated_settings[] = $section;
    }
  
    return $updated_settings;
  }
  add_filter( 'woocommerce_general_settings', 'add_order_sms_template' );