<?php

// Check version requirement dependencies
if (false !== jeeb_requirements_check()) {
    throw new \Exception('Your server does not meet the minimum requirements to use the jeeb payment plugin. The requirements check returned this error message: ' . jeeb_requirements_check());
}

// Load upgrade file
require_once ABSPATH.'wp-admin/includes/upgrade.php';

define("PLUGIN_NAME", 'e-commerce');
define("PLUGIN_VERSION", '3.0');
define("BASE_URL", 'https://core.jeeb.io/api/');

// Load dependencies
add_action('admin_enqueue_scripts', 'admin_scripts', 999);
function admin_scripts()
{
    if (is_admin()) {
        wp_enqueue_style('jeeb_admin_style', plugins_url('jeeb/admin.css', __FILE__));
        wp_enqueue_script('jeeb_admin_script', plugins_url('jeeb/admin.js', __FILE__), array('jquery'), '1.0', true);
    }
}

$nzshpcrt_gateways[$num] = array(
        'name'                                    => 'Jeeb',
        'api_version'                             => 1.0,
        'image'                                   => get_option('btnurl'),
        'has_recurring_billing'                   => false,
        'wp_admin_cannot_cancel'                  => true,
        'display_name'                            => 'Jeeb',
        'user_defined_name[wpsc_merchant_jeeb]'   => 'Jeeb',
        'requirements'                            => array('php_version' => 5.4),
        'internalname'                            => 'wpsc_merchant_jeeb',
        'form'                                    => 'form_jeeb',
        'submit_function'                         => 'process_payment',
        'function'                                => 'gateway_jeeb',
        );

function debug_log($contents)
{
    if (true === isset($contents)) {
        if (true === is_resource($contents)) {
            error_log(serialize($contents));
        } else {
            error_log(var_export($contents, true));
        }
    }
}

function form_jeeb()
{
    // Access to Wordpress Database
    global $wpdb;

    $output = NULL;

    try {
        if (get_option('jeeb_error') != null) {
            $output = '<div style="color:#A94442;background-color:#F2DEDE;background-color:#EBCCD1;text-align:center;padding:15px;border:1px solid transparent;border-radius:4px">'.get_option('jeeb_error').'</div>';
            update_option('jeeb_error', null);
        }

        $rows = array();

        $test = $live = "";
        get_option("network") == "Testnet" ? $test = "selected" : $live = "selected" ;
        $rows[] = array(
            'Allow TestNets',
            '<select name="network"><option value="Livenet" '. $live .'>Live</option><option value="Testnet" '.$test.'>Test</option></select>',
            '<p class="description">Allows testnets such as TEST-BTC to get processed.</p>',
        );

        $signature = get_option("signature");
        $rows[] = array(
            'Signature',
            '<input name="signature" type="text" value="'.$signature.'" placeholder="Enter your signature"/>',
            '<p class="description">The signature provided by Jeeb for you merchant.</p>',
        );

        $btc = $eur = $irr = $usd = $toman = "";
        get_option("basecoin") == "btc" ? $btc = "selected" : $btc = "" ;
        get_option("basecoin") == "eur" ? $eur = "selected" : $eur = "" ;
        get_option("basecoin") == "irr" ? $irr = "selected" : $irr = "" ;
        get_option("basecoin") == "toman" ? $toman = "selected" : $toman = "" ;
        get_option("basecoin") == "usd" ? $usd = "selected" : $usd = "" ;
        $rows[] = array(
            'Base Currency',
            '<select name="basecoin"><option value="btc" '.$btc.'>BTC</option><option value="eur" '.$eur.'>EUR</option><option value="irr" '.$irr.'>IRR</option><option value="toman" '.$toman.'>TOMAN</option><option value="usd" '.$usd.'>USD</option></select>',
            '<p class="description">The base currency of your website.</p>',
        );

        $btc = $eth = $xrp = $xmr = $bch = $ltc = $test_btc = $test_ltc = "";
        get_option("btc") == "btc" ? $btc = "checked" : $btc = "";
        get_option("eth") == "eth" ? $eth = "checked" : $eth = "";
        get_option("xrp") == "xrp" ? $xrp = "checked" : $xrp = "";
        get_option("xmr") == "xmr" ? $xmr = "checked" : $xmr = "";
        get_option("bch") == "bch" ? $bch = "checked" : $bch = "";
        get_option("ltc") == "ltc" ? $ltc = "checked" : $ltc = "";
        get_option("test-btc") == "test-btc" ? $test_btc = "checked" : $test_btc = "";
        get_option("test-ltc") == "test-ltc" ? $test_ltc = "checked" : $test_ltc = "";
        $rows[] = array(
            'Payable Currency',
            '<input type="checkbox" name="btc" value="btc" '.$btc.'>BTC<br>
            <input type="checkbox" name="eth" value="eth" '.$eth.'>ETH<br>
            <input type="checkbox" name="xrp" value="xrp" '.$xrp.'>XRP<br>
            <input type="checkbox" name="xmr" value="xmr" '.$xmr.'>XMR<br>
            <input type="checkbox" name="bch" value="bch" '.$bch.'>BCH<br>
            <input type="checkbox" name="ltc" value="ltc" '.$ltc.'>LTC<br>
            <input type="checkbox" name="test-btc" value="test-btc" '.$test_btc.'>TEST-BTC<br>
            <input type="checkbox" name="test-ltc" value="test-ltc" '.$test_ltc.'>TEST-LTC<br>',
            '<p class="description">The currencies which users can use for payments.</p>',
            );

        $auto_select = $eng = $persian = "";
        get_option("lang") == "none" ? $auto_select = "selected" : $auto_select = "" ;
        get_option("lang") == "en" ? $eng = "selected" : $eng = "" ;
        get_option("lang") == "fa" ? $persian = "selected" : $persian = "" ;
        $rows[] = array(
            'Language',
            '<select name="lang"><option value="none" '.$auto_select.'>Auto-Select</option><option value="en" '.$eng.'>English</option><option value="fa" '.$persian.'>Persian</option></select>',
            '<p class="description">The language of the payment area.</p>',
        );

        $yes = $no = "";
        get_option("allow_refund") == "yes" ? $yes= "selected" : $no = "selected" ;
        $rows[] = array(
            'Allow Refund',
            '<select name="allow_refund"><option value="yes" '. $yes .'>Allow</option><option value="no" '.$no.'>Disable</option></select>',
            '<p class="description">Allows payments to be refunded.</p>',
        );

        $expiration_time = get_option("expiration_time");
        if($expiration_time==""){
          $expiration_time= "15";
        }
        $rows[] = array(
            'Expiration Time',
            '<input name="expiration_time" type="text" value="'.$expiration_time.'" placeholder="Enter Expiration Time"/>',
            '<p class="description">Expands default payments expiration time. It should be between 15 to 2880 (mins).</p>',
        );

        $jeeb_redirect = get_option("jeeb_redirect");
        if(!$jeeb_redirect)
          $jeeb_redirect = home_url();
        // Allows the merchant to specify a URL to redirect to upon the customer completing payment on the jeeb.io
        // invoice page. This is typcially the "Transaction Results" page.
        $rows[] = array(
                        'Redirect URL',
                        '<input name="jeeb_redirect" type="text" value="'.$jeeb_redirect.'" />',
                        '<p class="description"><strong>Important!</strong> Enter the URL to which you want the user to return after the payment.',
                       );

         $eng = $persian = "";
         get_option("btnlang") == "en" ? $eng = "selected" : $eng = "" ;
         get_option("btnlang") == "fa" ? $persian = "selected" : $persian = "" ;
         $rows[] = array(
             'Checkout Button Languages',
             '<select id="btnlang" name="btnlang"><option value="en" '.$eng.'>English</option><option value="fa" '.$persian.'>Persian</option></select>',
             '<p class="description">Jeeb\'s checkout button preferred language.</p>',
         );

         $blue = $white = $transparent = "";
         get_option("btntheme") == "blue" ? $blue = "selected" : $blue = "" ;
         get_option("btntheme") == "white" ? $white = "selected" : $white = "" ;
         get_option("btntheme") == "transparent" ? $transparent = "selected" : $transparent = "" ;
         $rows[] = array(
             'Checkout Button Theme',
             '<select id="btntheme" name="btntheme"><option value="blue" '.$blue.'>Blue</option><option value="white" '.$white.'>White</option><option value="transparent" '.$transparent.'>Transparent</option></select>',
             '<p class="description">Jeeb\'s checkout button preferred theme.</p>',
         );

         $rows[] = array(
                         'Checkout Button',
                         '<input id="btnurl" name="btnurl" type="text"/>',
                         '',
                        );



        $output .= '<tr>' .
            '<td colspan="2">' .
                '<p class="description">' .
                    '<img src="' . WPSC_URL . '/wpsc-merchants/jeeb/assets/img/bitcoin.png" /><br /><strong>The minimum price of the product sold in your market should be atleast 10,000 IRR.<br>'.
                    'Have more questions? Need assistance? Please visit our website <a href="https://jeeb.io" target="_blank">https://jeeb.io</a> or send an email to <a href="mailto:support@jeeb.io" target="_blank">support@jeeb.com</a> for prompt attention. Thank you for choosing jeeb!</strong>' .
                '</p>' .
            '</td>' .
        '</tr>';

        foreach ($rows as $r) {
            $output .= '<tr> <td>' . $r[0] . '</td> <td>' . $r[1];

            if (true === isset($r[2])) {
                $output .= $r[2];
            }

            $output .= '</td></tr>';
        }
        $output .='<input type="hidden" name="jeebCurBtnUrl" id="jeebCurBtnUrl" value="' .get_option('btnurl') . '"/>';


        return $output;

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function process_payment()
{
    global $wpdb;

    try {
        if (true  === isset($_POST['submit'])              &&
            false !== stristr($_POST['submit'], 'Update'))
        {
            $params = array(
                            'signature',
                            'network',
                            'jeeb_redirect',
                            'basecoin',
                            'btc',
                            'xrp',
                            'xmr',
                            'ltc',
                            'bch',
                            'eth',
                            'test-btc',
                            'test-ltc',
                            'lang',
                            'expiration_time',
                            'allow_refund'
                           );

            foreach ($params as $p) {
                if($_POST[$p]){
                  if ($_POST[$p] != null) {
                    if($p=='expiration_time'){
                      if(isset($_POST[$p]) === false ||
                      is_numeric($_POST[$p]) === false ||
                      $_POST[$p]< 15 ||
                      $_POST[$p]> 2880)
                        $_POST[$p] = 15;
                    }
                    update_option($p, $_POST[$p]);
                  }
                  else {
                    if($p!='btc'&&$p!='xrp'&&$p!='xmr'&&$p!='ltc'&&$p!='bch'&&$p!='eth'&&$p!='test-btc'&&$p!='test-ltc'){
                      add_settings_error($p, 'error', 'The setting '. $p.' cannot be blank! Please enter a value for this field', 'error');
                  }
                }
              }
              else{
                update_option($p, NULL);
              }
            }
        }

        return true;

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function confirm_payment($signature, $options = array()){
    $post = json_encode($options);
    $ch = curl_init(BASE_URL . 'payments/' . $signature . '/confirm/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'User-Agent:' . PLUGIN_NAME . '/' . PLUGIN_VERSION,
    ));
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    return (bool) $data['result']['isConfirmed'];
}

function convertBaseToTarget($amount, $signature, $baseCur) {
    debug_log("Entered into Convert API");
    $ch = curl_init(BASE_URL.'currency?'.$signature.'&value='.$amount.'&base='.$baseCur.'&target=btc');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'User-Agent:'.PLUGIN_NAME.'/'.PLUGIN_VERSION)
  );

  $result = curl_exec($ch);
  $data = json_decode( $result , true);
  // Return the equivalent bitcoin value acquired from Jeeb server.
  return (float) $data["result"];

  }

function createInvoice($options = array(), $signature) {
      debug_log("Entered into Create Invoice");
      $post = json_encode($options);

      $ch = curl_init(BASE_URL.'payments/' . $signature . '/issue/');
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($post),
          'User-Agent:'.PLUGIN_NAME.'/'.PLUGIN_VERSION)
      );

      $result = curl_exec($ch);
      $data = json_decode( $result , true);

      return $data['result']['token'];

  }

function redirectPayment($token) {
    debug_log("Entered into auto submit-form");
    // $redirect_url = BASE_URL . "payments/invoice?token=" . $token;
    // header('Location: ' . $redirect_url);
    echo "<form id='form' method='post' action='https://core.jeeb.io/api/payments/invoice'>".
            "<input type='hidden' autocomplete='off' name='token' value='".$token."'/>".
           "</form>".
           "<script type='text/javascript'>".
                "document.getElementById('form').submit();".
           "</script>";
  }


function gateway_jeeb($seperator, $sessionid)
{
    global $wpdb;
    global $wpsc_cart;

    try {
        // This grabs the purchase log id from
        // the database that refers to the $sessionid
        $purchase_log = $wpdb->get_row(
                                       "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
                                       "` WHERE `sessionid`= " . $sessionid . " LIMIT 1",
                                       ARRAY_A
                                       );

        // price
        $price = number_format($wpsc_cart->total_price, 2, '.', '');

        // Configure the rest of the invoice
        $purchase_log = $wpdb->get_row("SELECT * FROM `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `sessionid`= " . $sessionid. " LIMIT 1", ARRAY_A);

        if (true === is_null(get_option('jeeb_redirect'))) {
            update_option('jeeb_redirect', get_site_url());
        }

        $baseCur      = get_option('basecoin');
        $signature    = get_option('signature'); // Signature
        $callBack     = get_option('jeeb_redirect').'/?process_response=true'; // Callback Url
        $notification = get_option('siteurl').'/?process_webhook=true';  // Notification Url
        $order_total  = $price;  // Total price in irr
        $params = array(
                        'btc',
                        'xrp',
                        'xmr',
                        'ltc',
                        'bch',
                        'eth',
                        'test-btc',
                        'test-ltc'
                       );

        foreach ($params as $p) {
          get_option($p) != NULL ? $target_cur .= get_option($p) . "/" : get_option($p) ;
        }

        if($baseCur=='toman'){
          $baseCur='irr';
          $order_total *= 10;
        }

        $amount = convertBaseToTarget($order_total, $signature, $baseCur);

        $params = array(
          'orderNo'        => $purchase_log['id'],
          'value'          => (float) $amount,
          'webhookUrl'     => $notification,
          'callbackUrl'    => $callBack,
          'expiration'     => get_option("expiration_time"),
          'allowReject'    => get_option("allow_refund") == "yes" ? true : false,
          "coins"          => $target_cur,
          "allowTestNet"   => get_option("network") == "Testnet" ? true : false,
          "language"       => get_option("lang") == "none" ? NUll : get_option("lang")
        );

        $token = createInvoice($params, $signature);

        redirectPayment($token);

    } catch (\Exception $e) {
        error_log('[Error] In jeeb plugin, form_jeeb() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function process_webhook()
{
    global $wpdb;
    global $wpsc_cart;

      $postdata = file_get_contents("php://input");
      $json = json_decode($postdata, true);

      $signature = $json['signature'];

      if($signature == get_option("signature")){
        debug_log("Entered into Notification");
      $table_name = $wpdb->prefix.'jeeb_keys';

      $orderNo = $json['orderNo'];

      $purchase_log = $wpdb->get_row("SELECT * FROM `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `id`= " . $orderNo. " LIMIT 1", ARRAY_A);

      // Call Jeeb
      if ( $json['stateId']== 2 ) {

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice created.' WHERE `id`=". $orderNo;
        $wpdb->query($sql);


        transaction_results($purchase_log['sessionid'], false);
      }
      else if ( $json['stateId']== 3 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '2' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'The payment has been received, but the transaction has not been confirmed on the blockchain network. This will be updated when the transaction has been confirmed. Reference No : ".$json["referenceNo"]."' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);
      }
      else if ( $json['stateId']== 4 ) {
        $data = array(
          "token" => $json["token"]
        );

        $is_confirmed = confirm_payment($signature, $data);

        if($is_confirmed){
          debug_log('Payment confirmed by jeeb');
          $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '3' WHERE `id`=". $orderNo;
          $wpdb->query($sql);

          $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'The payment has been confirmed by Jeeb. You are now safe to deliver the order. Reference No : ".$json["referenceNo"]."' WHERE `id`=". $orderNo;
          $wpdb->query($sql);

          transaction_results($purchase_log['sessionid'], false);
        }
        else {
          debug_log('Payment rejected by jeeb');
        }
      }
      else if ( $json['stateId']== 5 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '5' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was expired and the transaction failed.' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);

      }
      else if ( $json['stateId']== 6 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '7' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was over paid and the transaction was rejected by Jeeb' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);

      }
      else if ( $json['stateId']== 7 ) {
        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `processed`= '7' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        $sql = "UPDATE `" . WPSC_TABLE_PURCHASE_LOGS . "` SET `notes`= 'Invoice was partially paid and the transaction was rejected by Jeeb' WHERE `id`=". $orderNo;
        $wpdb->query($sql);

        transaction_results($purchase_log['sessionid'], false);
      }
      else{
        debug_log('Cannot read state id sent by Jeeb');
      }
    }
}

function EDD_Jeeb_checkCurl()
{
    return function_exists('curl_version');
}

function jeeb_requirements_check()
{
    global $wp_version;

    $errors = array();

    // Curl required
    if (!EDD_Jeeb_checkCurl()) {
        $errors[] = 'cUrl needs to be installed/enabled for Jeeb plugin for WP-eCommerce to function properly';
    }

    // PHP 5.4+ required
    if (true === version_compare(PHP_VERSION, '5.4.0', '<')) {
        $errors[] = 'Your PHP version is too old. The jeeb payment plugin requires PHP 5.4 or higher to function. Please contact your web server administrator for assistance.';
    }

    // Wordpress 3.9+ required
    if (true === version_compare($wp_version, '3.9', '<')) {
        $errors[] = 'Your WordPress version is too old. The jeeb payment plugin requires Wordpress 3.9 or higher to function. Please contact your web server administrator for assistance.';
    }

    // GMP or BCMath required
    if (false === extension_loaded('gmp') && false === extension_loaded('bcmath')) {
        $errors[] = 'The jeeb payment plugin requires the GMP or BC Math extension for PHP in order to function. Please contact your web server administrator for assistance.';
    }

    if (false === empty($errors)) {
        return implode("<br>\n", $errors);
    } else {
        return false;
    }
}

add_action('init', 'process_webhook');

function process_response()
{
    global $wpsc_cart;

    if ($_REQUEST["stateId"] == 3)
      $wpsc_cart->empty_cart();

}

add_action('init', 'process_response');
