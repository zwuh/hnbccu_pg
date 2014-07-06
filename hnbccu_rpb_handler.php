<?php
/**
 * hnbccu_rpb_handler.php : HNBCCU Payment Post-Back Handler
 * 
 * @package hnbccu_pg
 * @copyright 2012 National Taiwan University
 * @license GNU General Public License v2
 * @version $Id: hnbccu_int_page.php 1 2012-02-09 13:30:00Z zh $
 */
  // load common libraries
  require('includes/application_top.php');
  require(DIR_WS_CLASSES . 'payment.php');
  require(DIR_WS_CLASSES . 'order.php');

  if (!is_object($upper_payment = new payment('hnbccu'))) {
    echo "Please tell the admin:<br />\n";
    var_dump($_POST);
    echo "<br />\n";
    die ("HNBCCU: rpb-handler: cannot instantiate HNBCCU module");
  }
  if (!is_object($payment = $upper_payment->paymentClass)) {
    echo "Please tell the admin:<br />\n";
    var_dump($_POST);
    echo "<br />\n";
    die ("HNBCCU: rpb-handler: cannot down cast to HNBCCU class");
  }

  // fetch various posted parameters
  $mer_id = isset($_POST['merID']) ? $_POST['merID'] : -1;
  $lidm = isset($_POST['lidm']) ? $_POST['lidm'] : '';
  $xid = isset($_POST['xid']) ? $_POST['xid'] : '';
  $status = isset($_POST['status']) ? intval($_POST['status']) : -1;
  $Last4digitPAN = isset($_POST['Last4digitPAN']) ? $_POST['Last4digitPAN'] : '';
  $errDesc = isset($_POST['errDesc']) ? $_POST['errDesc'] : '';
  $errcode = isset($_POST['errcode']) ? $_POST['errcode'] : '';
  $authCode = isset($_POST['authCode']) ? $_POST['authCode'] : '';
  $authAmt = isset($_POST['authAmt']) ? intval($_POST['authAmt']) : -1;
  $order_id = 0;
  $order_total = 0;
  $this_order = null;
  $start_time = $_SERVER['REQUEST_TIME'];
  $remote_addr = $_SERVER['REMOTE_ADDR'];

  $payment->log_event("rpb-handler: $start_time INCOMING merID=$mer_id lidm=$lidm xid=$xid status=$status PAN=$Last4digitPAN errcode=$errcode authCode=$authCode authAmt=$authAmt remoteAddr=$remote_addr errdesc=$errDesc", $start_time);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<html>
<head>
<title><?php echo STORE_NAME.' - '.MODULE_PAYMENT_HNBCCU_TITLE; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
//  echo '<hr />POST<hr />'; var_dump($_POST); echo "<hr />\n";
//  echo '<hr />GET<hr />'; var_dump($_GET); echo "<hr />\n";
?>
<?php
  echo "<h1>".STORE_NAME." Payment Step #3:</h1>\n";
  echo "(Step #2 was done on the website of Hua Nan Bank)<br /><br />\n";
  $failed = false;
  // Process posted-back transaction info

  // ! It is posted back by CUSTOMER browser, not the Payment Gateway

  // verify that this is indeed a request for us (by checking merID)
  if (MODULE_PAYMENT_HNBCCU_MER_ID != $mer_id) {
    $payment->log_event("rpb-handler: $start_time ERROR merID mismatch", $start_time);
    die ('HNBCCU: ERROR: (early fatal) merID mismatch');
  }
  // verify that there is indeed such an order (extract from lidm)
  if ($lidm == '') {
    $payment->log_event("rpb-handler: $start_time ERROR no lidm", $start_time);
    echo 'HNBCCU: ERROR: no lidm ?'."<br />\n";
    $failed = true;
  } else {
//    echo 'HNBCCU: INFO: lidm = '.$lidm."<br />\n";
  }
  // get the order
  $order_id = $payment->extract_order_id($lidm);
//  echo 'HNBCCU: INFO: order_id = '.$order_id."<br />\n";
  if ($order_id <= 0 || !is_object($this_order = new order($order_id))) {
    $payment->log_event("rpb-handler: $start_time ERROR invalid oID=$order_id", $start_time);
    die ('HNBCCU: ERROR: (early fatal) invalid oID='.$order_id);
    $failed = true;
  }
  $order_total = intval($this_order->info['total']);
  if ($order_total <= 0) {
    $payment->log_event("rpb-handler: $start_time ERROR oID=$order_id non-positive order", $start_time);
    echo 'HNBCCU: ERROR: paying non-positive order ?'."<br />\n";
    $failed = true;
  }
//  echo 'HNBCCU: INFO: order_total: '.$order_total."<br />\n";
  if ($this_order->info['currency'] != 'NTD') {
    $payment->log_event("rpb-handler: $start_time ERROR oID=$order_id non-NTD order", $start_time);
    echo 'HNBCCU: ERROR: only NTD order is allowed'."<br />\n";
    $failed = true;
  }
  if ($this_order->info['orders_status'] != 'Pending') {
    $payment->log_event("rpb-handler: $start_time ERROR oID=$order_id non-Pending order", $start_time);
    echo 'HNBCCU: ERROR: non-Pending order'."<br />\n";
    $failed = true;
  }
  // trasaction id
  if ($xid == '') {
    $payment->log_event("rpb-handler: $start_time ERROR no xid", $start_time);
    echo 'HNBCCU: ERROR: no xid ?'."<br />\n";
    $failed = true;
  } else {
    // as of Mar.2012, it is observed that xid has following format:
    //  [PG_internal_hex_reference_code]_[lidm]
    // *maybe* we can take advantage of this ? (TODO)
//    echo 'HNBCCU: INFO: xid = '.$xid."<br />\n";
  }
  // last 4 digit of the card number
  if ($Last4digitPAN == '') {
    $payment->log_event("rpb-handler: $start_time ERROR no PAN", $start_time);
    echo 'HNBCCU: ERROR: no last 4 digit PAN ?'."<br />\n";
    $failed = true;
  } else {
//    echo 'HNBCCU: INFO: Last4digitPAN = '.$Last4digitPAN."<br />\n";
  }
  // error description
  if ($errDesc == '') {
    echo 'HNBCCU: WARNING: errDesc not sent?'."<br />\n";
//  $failed = true;
  }

  if ($status == -1) {
    $payment->log_event("rpb-handler: $start_time ERROR no status", $start_time);
    echo 'HNBCCU: WARNING: no status code ?!'."<br />\n";
    $failed = true;
  }

  if ($failed == true) {
    die ('HNBCCU: ERROR: (early fatal) Incoming request cannot be valid, process terminated');
  }

  // success or failure ?
//  echo 'HNBCCU: INFO: status = '.$status."<br />\n";
  if ($status != 0) {
    // failure
    if ($errcode == '') {
      echo 'HNBCCU: WARNING: error with no errcode ?'."<br />\n";
    }
    $err_msg = $payment->get_error_message($status, $errcode);
    $payment->log_event("rpb-handler: $start_time FAIL oID=$order_id xid=$xid", $start_time);
    // notify customer
//    $retry_url = $payment->intermediate_url.'?oID='.$order_id;
    $mail_text = 'Failed Payment Notice from '.STORE_NAME."\n\n".
      'Order Number: '.$order_id."\n".
      'Purchase Date: '.$this_order->info['date_purchased']."\n".
      'Total Amount: '.$order_total."\n\n".
      'Payment Detail:'."\n--------\n".
      'Payment Method: '.$payment->title."\n".
      'Merchant ID: '.MODULE_PAYMENT_HNBCCU_MERCHANT_ID."\n".
      'Terminal ID: '.MODULE_PAYMENT_HNBCCU_TERMINAL_ID."\n".
      'Reference Code: '.$lidm."\n".
      'Transaction Code: '.$xid."\n".
      'Status Code: '.$status."\n".'Error Code: '.$errcode."\n".
      'Error Description: '.$errDesc."\n".
      'Error Text: '.$err_msg."\n\n";
      // HNCB system in does not allow retry in production mode
//      'You may try to complete the payment again:'."\n".
//      '<a href="'.$retry_url.'">'.$retry_url."</a>\n";
    zen_mail('', $this_order->customer['email_address'], 'Failed Payment Notice - Order #'.$order_id, $mail_text, STORE_NAME, EMAIL_FROM, null, 'hnbccu:rpb-handler', null);
//    echo 'HNBCCU: INFO: e-mail notification to customer sent'."<br />\n";
    echo '<h3>Payment Failed!</h3><br />'.nl2br($mail_text);
//    die ("HNBCCU: ERROR: FAIL oId=$order_id xid=$xid status=$status errcode=$errcode error:$err_msg");
  }
  else { // success
    $err_msg = '';
    if ($authCode == '') {
      $payment->log_event("rpb-handler: $start_time ERROR no authCode", $start_time);
      echo 'HNBCCU: ERROR: no Auth Code ?'."<br />\n";
      $err_msg .= "No Auth Code\n";
    } else {
//      echo 'HNBCCU: INFO: authCode = '.$authCode."<br />\n";
    }
    if ($authAmt == -1) {
      $payment->log_event("rpb-handler: $start_time ERROR no authAmt", $start_time);
      echo 'HNBCCU: ERROR: no Auth Amount ?'."<br />\n";
      $err_msg .= "No Auth Amount\n";
    } else {
//      echo 'HNBCCU: INFO: authAmt = '.$authAmt."<br />\n";
    }
    if ($authAmt != $order_total) {
      $err_msg .= "Authorized amount does not equal to order total\n";
      $payment->log_event("rpb-handler: $start_time ERROR authAmt != order_total($order_total)", $start_time);
      echo 'HNBCCU: ERROR: Authorized amount does not equal to order total'."<br />\n";
    }
    if ($err_msg != '') { // this is an invalid response with status=0
      $payment->log_event("rpb-handler: $start_time FAIL invalid response received", $start_time);
//      $retry_url = $payment->intermediate_url.'?oID='.$order_id;
      $mail_text = 'Payment Error Notice from '.STORE_NAME."\n\n".
        'Order Number: '.$order_id."\n".
        'Purchase Date: '.$this_order->info['date_purchased']."\n".
        'Total Amount: '.$order_total."\n\n".
        'Payment Detail:'."\n--------\n".
	'Payment Method: '.$payment->title."\n".
        'Merchant ID: '.MODULE_PAYMENT_HNBCCU_MERCHANT_ID."\n".
        'Terminal ID: '.MODULE_PAYMENT_HNBCCU_TERMINAL_ID."\n".
        'Reference Code: '.$lidm."\n".
	'Transaction ID: '.$xid."\n".
        'Status Code: '.$status."\n".'Error Code: '.$errcode."\n".
        'Error Description: '.$errDesc."\n".
	'Auth Code: '.$authCode."\n".
	'Auth Amount: '.$authAmt."\n".
	'Last four digits of cart number: '.$Lasr4digitPAN."\n".
        'Error Text: '.$err_msg."\n\n";
      // notify customer
      // HNCB system does not allow retry in production mode
//      $cust_text = $mail_text.'You may retry the payment at '.$retry_url."\n";
      $cust_text = $mail_text;
      zen_mail('', $this_order->customer['email_address'], 'Payment Error Notice - Order #'.$order_id, $cust_text, STORE_NAME, EMAIL_FROM, null, 'hnbccu:rpb-handler', null);
      // notify admin
      zen_mail('', SEND_EXTRA_ORDER_EMAILS_TO, '[ADMIN COPY]Payment Error Notice - Order #'.$order_id, $mail_text, STORE_NAME, EMAIL_FROM, null, 'hnbccu:rpb-handler', null);
      echo '<h3>Payment Error!</h3><br />'.nl2br($cust_text);
//      die ("HNBCCU: ERROR: Invalid response received.<br />\nerr_msg:".$err_msg);
    } else { // paid
//      echo 'HNBCCU: INFO: success'."<br />\n";
      $payment->log_event("rpb-handler: $start_time SUCCESS oID=$order_id", $start_time);
      // record transaction
      $db_data = array('orders_id'=>$order_id, 'lidm'=>$lidm, 'status'=>$status,
        'errcode'=>$errcode, 'authCode'=>$authCode, 'authAmt'=>$authAmt,
	'xid'=>$xid, 'merId'=>$merID, 'Lasr4digitPAN'=>$Last4digitPAN,
	'oper_time'=>$start_time, 'errDesc'=>$errDesc);
      zen_db_perform(TABLE_HNBCCU_PAYMENT_RECORD, $db_data);
      $payment->log_event("rpb-handler: $start_time INFO oID=$order_id response recorded", $start_time);
//      echo 'HNBCCU: INFO: response recorded'."<br />\n";
      // advance order status,  2 = Processing
      $db_data = array('orders_status'=>2);
      zen_db_perform(TABLE_ORDERS, $db_data, 'update',
        ' orders_id='.$order_id.' limit 1');
      $payment->log_event("rpb-handler: $start_time INFO oID=$order_id status advanced", $start_time);
//      echo 'HNBCCU: INFO: order status advanced'."<br />\n";
      // record in order status history
      $db_data = array ('orders_id'=>$order_id,
        'orders_status_id'=>2, 'date_added'=>'now()',
	'comments'=>"HNBCCU: L=$lidm X=$xid C=$authCode S=$start_time",
	'customer_notified'=>1);
      zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $db_data);
      $payment->log_event("rpb-handler: $start_time INFO oID=$order_id order history set", $start_time);
//      echo 'HNBCCU: INFO: order history set'."<br />\n";
      $mail_text = 'Payment Compelete Notice from '.STORE_NAME."\n\n".
        'Order Number: '.$order_id."\n".
        'Purchase Date: '.$this_order->info['date_purchased']."\n".
        'Total Amount: '.$order_total."\n\n".
        'Payment Detail:'."\n--------\n".
	'Payment Method: '.$payment->title."\n".
        'Merchant ID: '.MODULE_PAYMENT_HNBCCU_MERCHANT_ID."\n".
        'Terminal ID: '.MODULE_PAYMENT_HNBCCU_TERMINAL_ID."\n".
        'Reference Code: '.$lidm."\n".
	'Transaction ID: '.$xid."\n".
        'Status Code: '.$status."\n".'Error Code: '.$errcode."\n".
        'Error Description: '.$errDesc."\n".
	'Auth Code: '.$authCode."\n".
	'Auth Amount: '.$authAmt."\n".
	'Last four digits of cart number: '.$Last4digitPAN."\n\n".
	'Order Status: Pending -> Processing'."\n";
      // notify customer
      zen_mail('', $this_order->customer['email_address'], 'Payment Complete Notice - Order #'.$order_id, $mail_text, STORE_NAME, EMAIL_FROM, null, 'hnbccu:rpb-handler', null);
      // notify admin
      zen_mail('', SEND_EXTRA_ORDER_EMAILS_TO, '[ADMIN COPY]Payment Complete Notice - Order #'.$order_id, $mail_text, STORE_NAME, EMAIL_FROM, null, 'hnbccu:rpb-handler', null);
      echo '<h3>Payment Succeeded!</h3><br />'.nl2br($mail_text);
    } // end of  if ($status == 0)
  } // end of status processing
?>
</body>
</html>
<?php
  // common bottom
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
