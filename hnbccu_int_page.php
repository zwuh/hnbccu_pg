<?php
/**
 * hnbccu_int_page.php : HNBCCU Payment Module Intermediate Page
 * 
 * @package hnbccu_pg
 * @copyright 2012 National Taiwan University
 * @license GNU General Public License v2
 * @version $Id: hnbccu_int_page.php 1 2012-02-09 13:30:00Z zh $
 */
  // Load common library stuff 
  require('includes/application_top.php');
  require_once(DIR_WS_CLASSES . 'payment.php');
  require_once(DIR_WS_CLASSES . 'order.php');

  $upper_payment = null;
  $payment = null;
  $this_order = null;
  $order_id = 0;
  $lidm = '';
  $amount = 0;
  // Load payment module
  if (!is_object($upper_payment = new payment('hnbccu'))) {
    echo "Please tell the admin:<br />\n";
    var_dump($_POST);
    echo "<br />\n";
    die ("HNBCCU: intermediate_page: cannot instantiate HNBCCU module");
  }
  if (!is_object($payment = $upper_payment->paymentClass)) {
    echo "Please tell the admin:<br />\n";
    var_dump($_POST);
    echo "<br />\n";
    die ("HNBCCU: intermediate_page: cannot down cast to HNBCCU class");
  }
  // Load order
  // this is a manual payment
  if (isset($_GET['oID'])) {
   $order_id = intval($_GET['oID']);
  }
  // this is a redirected session from checkout process
  else if (isset($_SESSION['zc_hnbccu_order_id'])) {
   $order_id = intval($_SESSION['zc_hnbccu_order_id']);
  }
  if ($order_id <= 0) {
    die ('HNBCCU: intermediate_page: you are coming withoud an order');
  }
  if (!is_object($this_order = new order($order_id))) {
    die ('HNBCCU: intermediate_page: cannot instantiate order object');
  }
  if (($amount = intval($this_order->info['total'])) <= 0) {
    die ('HNBCCU: intermediate_page: must be positive amount');
  }
  if ($this_order->info['currency'] != 'NTD') {
    die ('HNBCCU: intermediate_page: must be NTD order');
  }
  // check if the order is in 'Processing' status
  if ($this_order->info['orders_status'] != 'Pending') {
    die ('HNBCCU: intermediate_page: this order is not \'Pending\'');
  }
  // Generate lidm
  $lidm = $payment->generate_reference_code($order_id);
  if (strlen($lidm) > 19) {
    die ('HNBCCU: intermediate_page: lidm length > 19');
  }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo STORE_NAME.' - Payment:'.MODULE_PAYMENT_HNBCCU_TITLE; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
  echo "<h1>".STORE_NAME." Payment Step #1:</h1>\n";
  // warn if this is not production mode
  if (MODULE_PAYMENT_HNBCCU_STATUS != 'Production') {
    echo "<h3>NOTICE - NOT IN PRODUCTION MODE (mode:".MODULE_PAYMENT_HNBCCU_STATUS.")</h3><br />\n";
  }
  // print payment url
  echo "If you want to pay later, or have someone else pay for you, ".
       "please use following URL:<br />\n";
  $int_url = $payment->intermediate_url."?oID=".$order_id;
  echo "<a href=\"$int_url\">$int_url</a><br />\n";
  // print brief order summary
  echo "<h3>Order Brief</h3>\n";
  echo "Total Amount to pay: NTD ".$amount."<br />\n";
  echo "Items:<br />\n------<br />\n";
  foreach ($this_order->products as $item)
  {
    echo $item['qty'].'x '.$item['name'].'  NTD '.$item['final_price']."<br />\n";
  }
  echo "------<br />\n";
?>
<?php
  // produce form
  $form = zen_draw_form('hnccb_post_to_bank', $payment->gateway_url, 'post');
  $form .= zen_draw_hidden_field('MerchantID', MODULE_PAYMENT_HNBCCU_MERCHANT_ID)."\n";
  $form .= zen_draw_hidden_field('TerminalID', MODULE_PAYMENT_HNBCCU_TERMINAL_ID)."\n";
  $form .= zen_draw_hidden_field('MerchantName', MODULE_PAYMENT_HNBCCU_MERCHANT_NAME)."\n";
  $form .= zen_draw_hidden_field('lidm', $lidm)."\n";
  $form .= zen_draw_hidden_field('merID', MODULE_PAYMENT_HNBCCU_MER_ID)."\n";
  $form .= zen_draw_hidden_field('purchAmt', $amount)."\n";
  $form .= zen_draw_hidden_field('txType', '0'."\n");
  $form .= zen_draw_hidden_field('encode', 'UTF-8')."\n";
  $form .= zen_draw_hidden_field('AutoCap', '1')."\n";
  $form .= zen_draw_hidden_field('AuthResURL', $payment->post_back_url)."\n";
  echo $form;
  echo "Click 'Proceed' to go to Credit Card Payment Gateway of Hua Nan Bank.<br />\n";
  echo '<input type="submit" value="Proceed" />'."\n</form>\n";
?>
</body>
</html>
<?php
 require(DIR_WS_INCLUDES . 'application_bottom.php');
?>

