<?php
/**
 * hnbccu_view.php : HNBCCU Payment Module History View Page (Admin)
 * 
 * @package hnbccu_pg
 * @copyright 2012 National Taiwan University
 * @license GNU General Public License v2
 * @version $Id: hnbccu_int_page.php 1 2012-02-09 13:30:00Z zh $
 */
  // Load common library stuff 
  require('includes/application_top.php');
  require_once(DIR_FS_CATALOG_MODULES.'payment/hnbccu.php');
  $payment = null;
  // Load payment module
  if (!is_object($payment = new hnbccu())) {
    die ("HNBCCU: admin-view: cannot instantiate HNBCCU class");
  }

  if (isset($_GET['tb'])) {
   $tr_base = max(0, intval($_GET['tb']));
  } else {
   $tr_base = 0;
  }
  if (isset($_GET['eb'])) {
   $ev_base = max(0, intval($_GET['eb']));
  } else {
   $ev_base = 0;
  }

  global $db;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo STORE_NAME.' - ADMIN VIEW:'.MODULE_PAYMENT_HNBCCU_TITLE; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<h4>Transaction Logs</h4>
<a href="<?php echo $_SERVER['PHP_SELF'].'?tb='.max(0,$tr_base-20).'&eb='.$ev_base; ?>">[Prev 20]</a>
<a href="<?php echo $_SERVER['PHP_SELF'].'?tb='.($tr_base+20).'&eb='.$ev_base; ?>">[Next 20]</a>
<table border="1">
 <tr><th>#</th><th>oID</th><th>lidm</th><th>status</th><th>errcode</th><th>authCode</th><th>authAmt</th><th>xid</th><th>PAN</th><th>oper_time</th><th>errDesc</th><th>errMsg</th></tr>
<?php
  $sql = "select * from ".TABLE_HNBCCU_PAYMENT_RECORD." where 1 order by hn_id desc limit $tr_base,20";
  $tr_res = $db->Execute($sql);
  while (!$tr_res->EOF) {
   echo "<tr>\n";
    echo '<td>'.$tr_res->fields['hn_id'].'</td>'.
     '<td>'.$tr_res->fields['orders_id'].'</td>'.
     '<td>'.$tr_res->fields['lidm'].'</td>'.
     '<td>'.$tr_res->fields['status'].'</td>'.
     '<td>'.$tr_res->fields['errcode'].'</td>'.
     '<td>'.$tr_res->fields['authCode'].'</td>'.
     '<td>'.$tr_res->fields['authAmt'].'</td>'.
     '<td>'.$tr_res->fields['xid'].'</td>'.
     '<td>'.$tr_res->fields['Last4digitPAN'].'</td>'.
     '<td>'.$tr_res->fields['oper_time'].'</td>'.
     '<td>'.$tr_res->fields['errDesc'].'</td>'.
     '<td>'.''.'</td>';
   echo "</tr>\n";
   $tr_res->MoveNext();
  }
?>
</table>
<hr />
<h4>Event Logs</h4>
<a href="<?php echo $_SERVER['PHP_SELF'].'?tb='.$tr_base.'&eb='.max(0,$ev_base-50); ?>">[Prev 50]</a>
<a href="<?php echo $_SERVER['PHP_SELF'].'?tb='.$tr_base.'&eb='.($ev_base+50); ?>">[Next 50]</a>
<table border="1">
 <tr>
 <th>#</th><th>key</th><th>timestamp</th><th>message</th>
 </tr>
<?php
 $sql = 'select * from '.TABLE_HNBCCU_LOG.' where 1 order by id desc limit '.$ev_base.',50';
 $ev_res = $db->Execute($sql);
 while (!$ev_res->EOF) {
  echo "<tr>\n";
  echo '<td>'.$ev_res->fields['id'].'</td>'.
    '<td>'.$ev_res->fields['key'].'</td>'.
    '<td>'.$ev_res->fields['timestamp'].'</td>'.
    '<td>'.$ev_res->fields['message'].'</td>';
  echo "</tr>\n";
  $ev_res->MoveNext();
 }
?>
</table>
</body>
</html>
<?php
 require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
