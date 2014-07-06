<?php
/**
 * hnbccu.php : Hua Nan Bank Credit Card POS_URL Payment Module
 *
 * @package hnbccu_pg
 * @copyright 2012 National Taiwan University
 * @license GNU General Public License v2
 * @version $Id: hnbccu.php 1 2012-02-09 13:00:00Z zh $
 */

/* Companion files:
 Top Level:
  hnbccu_int_page.php , hnbccu_rpb_handler.php , hnbccu_dummy.php
 Language: includes/languages/english/modules/payment/
  hnbccu.php (this is an empty file)
 Admin area:
  hnbccu_view.php

 Notice:
  order id is embedded in 'lidm' parameter.
*/

define ('MODULE_PAYMENT_HNBCCU_TITLE', 'Credit Card (via Hua Nan Bank)');
define ('MODULE_PAYMENT_HNBCCU_DESCRIPTION', '華南銀行信用卡網路刷卡POS_URL整合<br /><a href="hnbccu_view.php">View Records</a>');
define ('MODULE_PAYMENT_HNBCCU_POST_BACK_FILENAME', 'hnbccu_rpb_handler.php');
define ('MODULE_PAYMENT_HNBCCU_INTERMEDIATE_FILENAME', 'hnbccu_int_page.php');
define ('TABLE_HNBCCU_PAYMENT_RECORD', DB_PREFIX.'hnbccu_payment_record');
define ('TABLE_HNBCCU_LOG', DB_PREFIX.'hnbccu_log');
//define ('STRICT_ERROR_REPORTING', true);

  class hnbccu {
    var $code, $title, $description, $enabled;
    var $intermediate_url, $post_back_url, $gateway_url;
    var $errCat, $errDesc;

// class constructor
    function hnbccu() {
      global $order;

      $this->code = 'hnbccu';
      $this->title = MODULE_PAYMENT_HNBCCU_TITLE;
      if (MODULE_PAYMENT_HNBCCU_STATUS == 'Testing') {
       $this->title .= ' (Testing)';
       define('MODULE_PAYMENT_HNBCCU_MERCHANT_ID', MODULE_PAYMENT_HNBCCU_TESTING_MERCHANT_ID);
       define('MODULE_PAYMENT_HNBCCU_TERMINAL_ID', MODULE_PAYMENT_HNBCCU_TESTING_TERMINAL_ID);
       define('MODULE_PAYMENT_HNBCCU_MER_ID', MODULE_PAYMENT_HNBCCU_TESTING_MER_ID);
       define('MODULE_PAYMENT_HNBCCU_PAYMENT_GATEWAY_URL', MODULE_PAYMENT_HNBCCU_TESTING_PAYMENT_GATEWAY_URL);
      } else {
       define('MODULE_PAYMENT_HNBCCU_MERCHANT_ID', MODULE_PAYMENT_HNBCCU_PRODUCTION_MERCHANT_ID);
       define('MODULE_PAYMENT_HNBCCU_TERMINAL_ID', MODULE_PAYMENT_HNBCCU_PRODUCTION_TERMINAL_ID);
       define('MODULE_PAYMENT_HNBCCU_MER_ID', MODULE_PAYMENT_HNBCCU_PRODUCTION_MER_ID);
       define('MODULE_PAYMENT_HNBCCU_PAYMENT_GATEWAY_URL', MODULE_PAYMENT_HNBCCU_PRODUCTION_PAYMENT_GATEWAY_URL);
      }
      $this->description = MODULE_PAYMENT_HNBCCU_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_HNBCCU_SORT_ORDER;
      if (MODULE_PAYMENT_HNBCCU_STATUS != 'Disabled') {
        $this->enabled = true;
	$this->gateway_url = MODULE_PAYMENT_HNBCCU_PAYMENT_GATEWAY_URL;
      } else {
        $this->enabled = false;
      }
      $t_url = str_replace('index.php?main_page=index', MODULE_PAYMENT_HNBCCU_POST_BACK_FILENAME, zen_href_link('index', '', 'SSL', false));
      $this->post_back_url = str_replace('&amp;','',$t_url);
      $t_url = str_replace('index.php?main_page=index', MODULE_PAYMENT_HNBCCU_INTERMEDIATE_FILENAME, zen_href_link('index', '', 'SSL', false));
      $this->intermediate_url = str_replace('&amp;','',$t_url);

      $this->set_error_message();

      if (is_object($order)) $this->update_status();
    }

// class methods
    function update_status() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: update_status()');
//      echo 'HNBCCU: update_status() NONBLOCK';
      global $order, $db;

// disable the module if the order only contains virtual products
      if ($this->enabled == true) {
        if ($order->content_type != 'physical') {
          $this->enabled = false;
//	  echo 'HNBCCU: update_status(): disabled HNBCCU';
        }
      }
    }

    function javascript_validation() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: javascript_validation()');
//      echo 'HNBCCU: javascript_validation() NONBLOCK';
      return false;
    }

    function selection() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: selection()');
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function pre_confirmation_check() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: pre_confirmation_check()');
//      echo 'HNBCCU: pre_confirmation_check() NONBLOCK';
      return false;
    }

    function confirmation() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: confirmation()');
//      echo 'HNBCCU: confirmation() NONBLOCK';
      return false;
    }

    function process_button() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: process_button()');
//      echo 'HNBCCU: process_button() NONBLOCK';
      return false;
    }

    function before_process() {
//      var_dump(debug_backtrace());
//      die ('HNBCCU: before_process()');
//      echo 'HNBCCU: before_process() NONBLOCK';
      return false;
    }

    function after_order_create($order_id) {
//      die ('HNBCCU: after_order_create(): oid: '.$order_id);
//      echo 'HNBCCU: after_order_create() NONBLOCK: oid: '.$order_id;
      $_SESSION['zc_hnbccu_order_id'] = $order_id;
      return false;
    }

    function admin_notification($order_id) {
//      die ('HNBCCU: admin_notification(): oid: '.$order_id);
//      echo 'HNBCCU: admin_notification() NONBLOCK: oid: '.$order_id;
    }

    function after_process() {
        // If we var_dump() here, the checkout will not proceed.
//      var_dump(debug_backtrace());
//      die ('HNBCCU: after_process()');
//      echo 'HNBCCU: after_process() NONBLOCK';

      // At this point, two 'confirmation' e-mails have been sent to
      // the customer, and the order is in the state 'PENDING'
      // We will have to explicitly 'advance' the state in the post-back
      // handler.
      if (!isset($_SESSION['zc_hnbccu_order_id'])) {
        die ('HNBCCU: after_process(): ERROR: broken session, order in session not set');
      }
      $order_id = intval($_SESSION['zc_hnbccu_order_id']);
//      echo 'HNBCCU: after_process(): oid fetched: '.$order_id."\n";
      // redirect the client to the intermediate page
      zen_redirect($this->intermediate_url);
      die ('HNBCCU: after_process(): cannot redirect');
      return false;
    }

    function get_error() {
      var_dump(debug_backtrace());
      die ('HNBCCU: get_error()');
      return false;
    }

    function check() {
      global $db;
      if (!isset($this->_check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HNBCCU_STATUS'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;
    }

    function install() {
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_HNBCCU_STATUS')) {
        $messageStack->add_session('HNBCCU module already installed.', 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=hnbccu', 'NONSSL'));
        return 'failed';
      }
      $db->Execute("create table if not exists `".TABLE_HNBCCU_PAYMENT_RECORD."` (`hn_id` int(8) auto_increment, `orders_id` int(11) not null, lidm varchar(19) not null default '', `status` varchar(3) not null, `errcode` varchar(4) default null, `authCode` varchar(6) default null, `authAmt` int(6) default 0, `xid` varchar(40) not null, `merID` varchar(8) not null, `Lasr4digitPAN` varchar(4) not null, `oper_time` varchar(16) not null, `timestamp` timestamp default now(), `errDesc` varchar(128), constraint primary key (`hn_id`), constraint `key_xid` unique key (`xid`), index `id_lidm` (`lidm`), index `id_oid` (`orders_id`) )");
      $db->Execute("create table if not exists `".TABLE_HNBCCU_LOG."` (`id` int(11) auto_increment, `key` varchar(16), `timestamp` timestamp default now(), `message` varchar(255), constraint primary key (`id`) )");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display', 'MODULE_PAYMENT_HNBCCU_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Operation Mode', 'MODULE_PAYMENT_HNBCCU_STATUS', 'Production', '正式上線(Production)、測試模式(Testing)、關閉(Disabled)，請與銀行確認上線狀態', '6', '1', 'zen_cfg_select_option(array(\'Production\', \'Testing\', \'Disabled\'), ', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID (Testing)', 'MODULE_PAYMENT_HNBCCU_TESTING_MERCHANT_ID', '0', '測試用的商店代號(長度固定15位數字)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Terminal ID (Testing)', 'MODULE_PAYMENT_HNBCCU_TESTING_TERMINAL_ID', '0', '測試用的端末機代號(長度固定8位數字)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mer ID (Testing)', 'MODULE_PAYMENT_HNBCCU_TESTING_MER_ID', '0', '測試用特約商店網站代碼(4位數字)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Payment Gateway URL (Testing)', 'MODULE_PAYMENT_HNBCCU_TESTING_PAYMENT_GATEWAY_URL', '0', '測試收單POS URL (SSLAuthUI網址)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_HNBCCU_PRODUCTION_MERCHANT_ID', '0', '銀行授權使用的商店代號(長度固定15位數字)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Terminal ID', 'MODULE_PAYMENT_HNBCCU_PRODUCTION_TERMINAL_ID', '0', '銀行授權使用的端末機代號(長度固定8位數字)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mer ID', 'MODULE_PAYMENT_HNBCCU_PRODUCTION_MER_ID', '0', '特約商店網站代碼(4位數字)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Payment Gateway URL', 'MODULE_PAYMENT_HNBCCU_PRODUCTION_PAYMENT_GATEWAY_URL', '0', '收單POS URL (SSLAuthUI網址)', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Name', 'MODULE_PAYMENT_HNBCCU_MERCHANT_NAME', '0', '刷卡時要顯示的商店名稱', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Encryption secret', 'MODULE_PAYMENT_HNBCCU_SECRET', 'some secret here', '加密秘語', '6', '0', now())");
   }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array(
        'MODULE_PAYMENT_HNBCCU_STATUS',
	'MODULE_PAYMENT_HNBCCU_SORT_ORDER',
	'MODULE_PAYMENT_HNBCCU_PRODUCTION_MERCHANT_ID',
	'MODULE_PAYMENT_HNBCCU_PRODUCTION_TERMINAL_ID',
	'MODULE_PAYMENT_HNBCCU_PRODUCTION_MER_ID',
	'MODULE_PAYMENT_HNBCCU_PRODUCTION_PAYMENT_GATEWAY_URL',
	'MODULE_PAYMENT_HNBCCU_TESTING_MERCHANT_ID',
	'MODULE_PAYMENT_HNBCCU_TESTING_TERMINAL_ID',
	'MODULE_PAYMENT_HNBCCU_TESTING_MER_ID',
	'MODULE_PAYMENT_HNBCCU_TESTING_PAYMENT_GATEWAY_URL',
	'MODULE_PAYMENT_HNBCCU_MERCHANT_NAME',
	'MODULE_PAYMENT_HNBCCU_SECRET'
	);
    }

    // generate an 'lidm' for use with the bank
    // Format: [[SHA-1 hash]['X'][order_id]]
    function generate_reference_code($order_id = 0) {
//      echo 'HNBCCU: generate_reference_code(): oid: '.$order_id."\n";
      // Assumption 1: order_id  is always numeric
      //   we use the X before order_id as the anchor to extract order id
      //   in post-back handler
      // Assumption 2: order_id  will never exceed 11 digits
      //   (it is int(11) in database schema)
      if (strlen($order_id) > 11)
      { die('HNBCCU: generate_reference_code(): strlen(order_id) > 12'); }
      $t_str = sha1(MODULE_PAYMENT_HNBCCU_SECRET.$order_id);
      return str_pad('X'.$order_id, 19, $t_str, STR_PAD_LEFT);
    }

    function extract_order_id($lidm = '') {
      $oid_anchor = strrpos($lidm, 'X');
      if (FALSE === $oid_anchor || $oid_anchor == (strlen($lidm) - 1)) {
        echo 'HNBCCU: extract_order_id(): illegal reference code: no anchor ?';
	return 0;
      }
      $order_id = intval(substr($lidm, $oid_anchor+1));
      if ($order_id <= 0) {
        echo 'HNBCCU: extract_order_id(): why is there non-positive order id ?!';
	return 0;
      }
      if ($this->generate_reference_code($order_id) != $lidm) {
        echo 'HNBCCU: extract_order_id(): lidm - order_id verification failed.';
	return 0;
      }
      return $order_id;
    }

    function log_event($message = '', $key = '') {
      global $db;
//      echo 'HNBCCU: log_event(): not implemented.'."<br />\n";
//      return false;
      $s_message = zen_db_input($message);
      $s_key = zen_db_input($key);
      $sql = "insert into ".TABLE_HNBCCU_LOG." (`key`,`message`) values ('$s_key', '$s_message')";
//      die ($sql);
      $db->Execute($sql);
    }

    function get_error_message($status = '0', $errcode = '') {
      $msg = '';
      if (array_key_exists($status, $this->errCat)) {
        $msg .= 'CAT=('.$status.')'.$this->errCat[$status];
	if (!array_key_exists($status, $this->errDesc)) {
	  die ('HNBCCU: get_error_message: assertion failed: errDescMsg/status');
	}
	if (array_key_exists($errcode, $this->errDesc[$status])) {
	  $msg .= ' REA=('.$errcode.')'.$this->errDesc[$status][$errcode];
	} else {
	  $msg .= ' NO SUCH REASON';
	}
      } else {
        $msg .= 'NO SUCH CATEGORY';
      }
      return $msg;
    }

  // put all new stuff above this, it is a long, long list ...
  function set_error_message() {
    // Error Message Map
    $this->errCat = array(
  "0"=>"交易成功",
  "1"=>"P.G 資料庫錯誤 (Payment Gateway DataBase Error)",
  "2"=>"P.G 系統錯誤 (Payment Gateway Error)",
  "3"=>"P.G 拒絕交易錯誤(Payment Gateway Reject)",
  "4"=>"收單銀行連線錯誤/拒絕 (TM Error/Reject)",
  "8"=>"Call Bank 錯誤 (BankError)",
  "9"=>"EZPOS 系統錯誤 (SSL EZPOS Server Error)",
  "10"=>"EZPOS 系統錯誤 (SSL EZPOS System Error)",
  "12"=>"3D 安全驗證錯誤 (3D Secure Error)"
 );
    $this->errDesc = array(
 "0" => array(),
 "1" => array(
  "00"=>"(1:00)交易資料有誤,無法寫入資料庫"
 ),
 "2" => array(
  "1"=>"(2:1)P.G.系統記憶體配置異常",
  "2"=>"(2:2)P.G.系統開啟系統資料檔異常",
  "3"=>"(2:3)P.G.系統存取系統資料檔異常",
  "4"=>"(2:4)P.G.系統連結資料庫系統異常",
  "5"=>"(2:5)P.G.系統無法回傳交易結果,發動 Cancel 交易",
  "6"=>"(2:6)Reserved",
  "7"=>"(2:7)P.G.系統等待授權結果逾時",
  "8"=>"(2:8)P.G.系統初始化交易資料格式異常",
  "9"=>"(2:9)P.G.系統存取批次交易資料異常",
  "10"=>"(2:10)P.G.系統啟動過期資料清檔程序異常",
  "11"=>"(2:11)P.G.系統產生該交易批次報表異常",
  "12"=>"(2:12)P.G.系統時間早於 1970,需重新設定",
  "13"=>"(2:13)Reserved",
  "14"=>"(2:14)P.G.系統啟動時產生 Log 或 QID 目錄異常",
  "15"=>"(2:15)P.G.系統參數資料長度超過 2048 位元",
  "16"=>"(2:16)P.G.系統產生 reconcile report 逾時",
  "20"=>"(2:20)P.G.系統讀取 DB Server 資料設定異常",
  "21"=>"(2:21)P.G.系統讀取 HYWEBPGDIR 環境變數異常",
  "22"=>"(2:22)P.G.系統 Config 內容設定異常",
  "30"=>"(2:30)P.G.系統正在進行關機程序,無法處理新進的交易",
  "40"=>"(2:40)P.G.同時進行處理的交易數量已達上限,請稍後再試"
 ),
 "3" => array(
  "1"=>"(3:1)P.G.系統拒絕商家進行該型態的交易指令",
  "2"=>"(3:2)違反交易法則,P.G.系統無法作業",
  "3"=>"(3:3)違反交易法則,P.G.系統無法 void 原已取消的交易",
  "4"=>"(3:4)交易指令不在 P.G.系統的 CAT/EDC 定義範圍內",
  "5"=>"(3:5)違反交易法則,P.G.系統無法判讀交易完成狀態",
  "6"=>"(3:6)P.G.系統無法儲存未 approved 的成功交易",
  "7"=>"(3:7)交易金額違反法則,P.G.系統無法處理",
  "8"=>"(3:8)MerchantID 資料長度超過有效範圍",
  "9"=>"(3:9)TerminalID 資料長度超過有效範圍",
  "10"=>"(3:10)交易型態不在 P.G.系統的 SET/SSL 類別定義內",
  "11"=>"(3:11)P.G.系統設定交易資料內 EC_INDICATOR 異常",
  "12"=>"(3:12)分期付款中交易資料內信用卡 BRAND 不一致",
  "13"=>"(3:13)P.G.系統設定交易資料內分期期數異常",
  "14"=>"(3:14)P.G.系統拒絕重複的交易代碼 XID",
  "15"=>"(3:15)該筆 SSL 交易代 XID 交易正被處理中",
  "16"=>"(3:16)P.G.系統偵測到交易中的資料未進行 Lock 保護機制",
  "17"=>"(3:17)P.G.系統無法處理原 void 的交易資料",
  "18"=>"(3:18)授權已被取消",
  "19"=>"(3:19)該授權交易已超過有效期限,P.G.系統無法繼續執行該交易",
  "20"=>"(3:20)該退款交易已超過有效期限,P.G.系統無法繼續執行該交易",
  "21"=>"(3:21)交易資料的授權金額比對有問題",
  "22"=>"(3:22)交易資料的信用卡卡號比對有問題",
  "23"=>"(3:23)交易資料的信用卡有效期限比對有問題",
  "24"=>"(3:24)交易資料的授權碼比對有問題",
  "25"=>"(3:25)P.G.系統無法判別前次的交易資料",
  "26"=>"(3:26)Reserved:分期付款的金額有誤",
  "27"=>"(3:27)批次結帳作業無回應狀態值",
  "28"=>"(3:28)批次結帳作業無回應狀態值",
  "29"=>"(3:29)批次結帳結果的狀態值不在定義範圍內",
  "30"=>"(3:30)批次結帳結果的狀態值不在定義範圍內",
  "31"=>"(3:31)該商家目前有交易正在進行,P.G.系統無法鎖定該特店",
  "32"=>"(3:32)P.G.系統無法判別商家目前的 MerchantID 及 TerminalID",
  "33"=>"(3:33)P.G.系統無法判別商家目前欲進行的交易批次",
  "34"=>"(3:34)該批次編號需先進行 BatchOpen 的動作才能使用",
  "36"=>"(3:36)商家的交易批次編號已達 P.G.系統的上限,需進行批次清除的作業",
  "37"=>"(3:37)P.G.系統無法對已啟用的批次編號再進行開啟作業",
  "38"=>"(3:38)P.G.系統無法對已關閉的批次編號再進行結帳作業",
  "39"=>"(3:39)Reserved",
  "40"=>"(3:40)P.G.系統拒絕在目前的批次狀態執行該交易",
  "41"=>"(3:41)該商家的交易批次編號已使用中",
  "42"=>"(3:42)P.G.系統無法判別商家目前使用中的交易批次編號",
  "43"=>"(3:43)商家尚無啟用中的批次資料可供結帳作業",
  "44"=>"(3:44)交易資料無 CVV2 驗證碼",
  "45"=>"(3:45)授權金額超過單筆交易金額限制",
  "46"=>"(3:46)批次請款總金額超過批次金額限制",
  "47"=>"(3:47)批次退款總金額超過批次金額限制",
  "51"=>"(3:51)分期交易,請款或退款金額必須等於授權金額",
  "52"=>"(3:52)重複請款",
  "53"=>"(3:53)重複退款",
  "54"=>"(3:54)重複取消授權",
  "55"=>"(3:55)重複取消請款",
  "56"=>"(3:56)重複取消退款",
  "71"=>"(3:71)此交易將使批次退款總金額大於請款總金額",
  "72"=>"(3:72)對應的 Cap 交易,尚未確認跟銀行請款成功"
 ),
 "4" => array(
  "1"=>"(4:1)銀行收單系統逾時回應訊息",
  "2"=>"(4:2)P.G.系統上傳的交易型態異常",
  "3"=>"(4:3)P.G.系統上傳的交易資料格式異常",
  "4"=>"(4:4)P.G.系統與銀行收單系統系統連線失敗",
  "5"=>"(4:5)銀行收單系統回應訊息異常",
  "6"=>"(4:6)P.G.系統交易處理滿載中",
  "7"=>"(4:7)信用卡效期過期,或卡號長度不對(目前只允許 16 碼)",
  "8"=>"(4:8)Reserved:交易批次資料上傳銀行收單系統異常",
  "9"=>"(4:9)P.G.系統不允許該交易進行 Cancel 作業",
  "10"=>"(4:10)P.G.系統等不到銀行收單系統系統回應交易結果*",
  "11"=>"(4:11)P.G.系統重新啟動中*",
  "12"=>"(4:12)P.G 主機作業系統異常*",
  "13"=>"(4:13)P.G.無法對其他系統特店進行收單",
  "14"=>"(4:14)P.G 系統與銀行收單系統間網路忙線中,請稍後重新交易",
  "30"=>"(4:30)銀行收單系統所回應的該資料欄位異常",
  "31"=>"(4:31)銀行收單系統所回應的該資料欄位異常",
  "32"=>"(4:32)銀行收單系統所回應的該資料欄位異常",
  "33"=>"(4:33)銀行收單系統所回應的該資料欄位異常",
  "34"=>"(4:34)銀行收單系統所回應的該資料欄位異常",
  "35"=>"(4:35)銀行收單系統所回應的該資料欄位異常",
  "36"=>"(4:36)銀行收單系統所回應的該資料欄位異常",
  "37"=>"(4:37)銀行收單系統所回應的該資料欄位異常",
  "38"=>"(4:38)銀行收單系統所回應的該資料欄位異常",
  "39"=>"(4:39)銀行收單系統所回應的該資料欄位異常",
  "40"=>"(4:40)銀行收單系統所回應的該資料欄位異常",
  "41"=>"(4:41)銀行收單系統所回應的該資料欄位異常",
  "42"=>"(4:42)銀行收單系統所回應的該資料欄位異常",
  "43"=>"(4:43)銀行收單系統所回應的該資料欄位異常"
 ),
 "5" => array(),
 "6" => array(),
 "7" => array(),
 "8" => array(
  "00"=>"(8:00)Reserved",
  "01"=>"(8:01)請與您的發卡系統聯絡有關網路交易授權失敗的原因",
  "02"=>"(8:02)特殊狀況,請與發卡系統聯絡有關網路交易授權失敗的原因",
  "03"=>"(8:03)未經授權使用的 Merchant ID",
  "05"=>"(8:05)發卡系統無任何因素地拒絕該卡號的網路交易*",
  "06"=>"(8:06)RESERVED TO ISO USE",
  "07"=>"(8:07)特殊狀況,失卡,請與發卡系統聯絡",
  "08"=>"(8:08)RESERVED TO ISO USE",
  "09"=>"(8:09)RESERVED TO ISO USE",
  "10"=>"(8:10)RESERVED TO ISO USE",
  "11"=>"(8:11)RESERVED TO ISO USE",
  "12"=>"(8:12)無效的交易,交易資料異常",
  "13"=>"(8:13)無效的金額,消費金額異常",
  "14"=>"(8:14)無效的卡號資料",
  "15"=>"(8:15)無效的發卡系統",
  "16"=>"(8:16)RESERVED TO ISO USE",
  "17"=>"(8:17)RESERVED TO ISO USE",
  "18"=>"(8:18)RESERVED TO ISO USE",
  "19"=>"(8:19)系統系統拒絕重複的交易",
  "20"=>"(8:20)Reserved",
  "30"=>"(8:30)系統無法判別交易的資料格式",
  "31"=>"(8:31)Reserved, bank not supported by switch",
  "32"=>"(8:32)Reserved, completed partially",
  "33"=>"(8:33)發卡系統:您的信用卡有效期限輸入錯誤",
  "34"=>"(8:34)Reserved, suspected fraud 故意欺騙",
  "35"=>"(8:35)Reserved, card acceptor call acquirer security",
  "36"=>"(8:36)Reserved, restricted card 受限卡",
  "37"=>"(8:37)Reserved, card acceptor call acquirer security",
  "38"=>"(8:38)Reserved, allowable PIN tries exceeded 錯誤三次",
  "39"=>"(8:39)系統無法獲得持卡者的信用資料",
  "40"=>"(8:40)Reserved, requested function not supported",
  "41"=>"(8:41)信用卡已掛失",
  "42"=>"(8:42)Reserved, no universal account",
  "43"=>"(8:43)拾獲失竊卡",
  "44"=>"(8:44)Reserved, no investment account",
  "45"=>"(8:45)RESERVED TO ISO USE",
  "46"=>"(8:46)RESERVED TO ISO USE",
  "47"=>"(8:47)RESERVED TO ISO USE",
  "48"=>"(8:48)RESERVED TO ISO USE",
  "49"=>"(8:49)RESERVED TO ISO USE",
  "50"=>"(8:50)RESERVED TO ISO USE",
  "51"=>"(8:51)發卡系統:消費額度不足",
  "52"=>"(8:52)No chequing account",
  "53"=>"(8:53)No saving account",
  "54"=>"(8:54)信用卡有效期限過期",
  "55"=>"(8:55)Reserved, incorrect personal identification number",
  "56"=>"(8:56)系統無法獲得持卡者的信用卡紀錄",
  "57"=>"(8:57)拒絕持卡者進行該網路交易",
  "58"=>"(8:58)拒絕商家進行該網路交易",
  "59"=>"(8:59)嫌疑卡",
  "60"=>"(8:60)Reserved, card acceptor call acquirer",
  "61"=>"(8:61)Reserved, amount too high",
  "62"=>"(8:62)Reserved, card have to check",
  "63"=>"(8:63)信用卡安全識別碼錯誤, security violation",
  "64"=>"(8:64)相關交易的金額前後不符",
  "65"=>"(8:65)Reserved, exceeds withdrawal frequency limit",
  "66"=>"(8:66)Reserved, card acceptor call acquirer’s security department",
  "67"=>"(8:67)Reserved, requires that card be picked up at ATM",
  "68"=>"(8:68)Reserved",
  "69"=>"(8:69)RESERVED TO ISO USE",
  "70"=>"(8:70)RESERVED TO ISO USE",
  "71"=>"(8:71)RESERVED TO ISO USE",
  "72"=>"(8:72)RESERVED TO ISO USE",
  "73"=>"(8:73)RESERVED TO ISO USE",
  "74"=>"(8:74)RESERVED TO ISO USE",
  "75"=>"(8:75)Reserved, pin try too many times",
  "76"=>"(8:76)RESERVED TO ISO USE",
  "77"=>"(8:77)RESERVED TO ISO USE",
  "78"=>"(8:78)RESERVED TO ISO USE",
  "79"=>"(8:79)RESERVED TO ISO USE",
  "80"=>"(8:80)RESERVED TO ISO USE",
  "81"=>"(8:81)RESERVED TO ISO USE",
  "82"=>"(8:82)RESERVED TO ISO USE",
  "83"=>"(8:83)RESERVED TO ISO USE",
  "84"=>"(8:84)RESERVED TO ISO USE",
  "85"=>"(8:85)RESERVED TO ISO USE",
  "86"=>"(8:86)RESERVED TO ISO USE",
  "87"=>"(8:87)RESERVED TO ISO USE",
  "88"=>"(8:88)RESERVED TO ISO USE",
  "89"=>"(8:89)Reserved,未經授權使用的 Terminal ID",
  "90"=>"(8:90)Reserved, cutoff is in process, transaction can be sent again in a few miniutes",
  "91"=>"(8:91)Reserved, issuer or switch center is inoperative",
  "92"=>"(8:92)Reserved, financial institution or intermediate net. facility cannot be found for routing",
  "93"=>"(8:93)Reserved, transaction cannot be completed",
  "94"=>"(8:94)Reserved",
  "95"=>"(8:95)Reserved, batch upload started",
  "96"=>"(8:96)Reserved"
 ),
 "9" => array(
  "nc"=>"(9:nc)伺服器忙碌中",
  "nd"=>"(9:nd)交易資料在商家 HyPOS 系統的資料不一致",
  "ne"=>"(9:ne)該筆為已請款交易,無法再進行請款",
  "ng"=>"(9:ng)缺少所必須的欄位",
  "ni"=>"(9:ni)伺服器系統錯誤",
  "nm"=>"(9:nm)從 API 或 P.G.系統接收到的資料或格式錯誤",
  "no"=>"(9:no)接收資料逾時",
  "np"=>"(9:np)從 P.G.系統接收資料時發生錯誤",
  "nq"=>"(9:nq)無法連結至 P.G.系統或資料傳送失敗",
  "ns"=>"(9:ns)在伺服器中找不到該特店相關資料",
  "nt"=>"(9:nt)商家 HyPOS 系統接收到的資料有誤",
  "nv"=>"(9:nv)Reserved",
  "nz"=>"(9:nz)交易連線來自未經授權的用戶端",
  "ed"=>"(9:ed)信用卡有效期限過期",
  "bc"=>"(9:bc)收到 BatchClose 交易,但該機台的批次狀態是「未開啟」",
  "bo"=>"(9:bo)進行 BatchOpen 交易,但該機台的批次狀態是「已開啟」"
 ),
 "10" => array(
  "1"=>"(10:1)HyPOSEZ Server 系統設定異常",
  "2"=>"(10:2)HyPOSEZ Server 系統設定檔不存在",
  "3"=>"(10:3)HyPOSEZ Server 系統記憶體配置異常",
  "4"=>"(10:4)HyPOSEZ 無法連結到 SSL HyPOS Server",
  "5"=>"(10:5)Reserved",
  "6"=>"(10:6)Reserved",
  "7"=>"(10:7)該筆交易正在 HyPOSEZ Server 進行中",
  "8"=>"(10:8)Reserved",
  "10"=>"(10:10)其他不明原因的錯誤",
  "11"=>"(10:11)HyPOSEZ 帳管系統無此訂單編號",
  "12"=>"(10:12)HyPOSEZ 系統設定異常",
  "13"=>"(10:13)Reserved",
  "14"=>"(10:14)HyPOSEZ 無此交易相關紀錄檔",
  "15"=>"(10:15)HyPOSEZ 交易紀錄中 XID 交易序號格式異常",
  "16"=>"(10:16)HyPOSEZ 開啟交易紀錄檔異常",
  "17"=>"(10:17)HyPOSEZ 帳管系統交易查詢異常中斷",
  "18"=>"(10:18)URL-Link 網頁整合格式異常─網路特店編號",
  "19"=>"(10:19)URL-Link 網頁整合格式異常─商場訂單編號",
  "20"=>"(10:20)URL-Link 網頁整合格式異常─訂單交易金額",
  "21"=>"(10:21)URL-Link 網頁整合格式異常─交易幣別指數",
  "22"=>"(10:22)信用卡有效期限長度格式異常",
  "23"=>"(10:23)信用卡卡號長度格式異常",
  "24"=>"(10:24)URL-Link 網頁整合格式異常─訂單交易幣別",
  "25"=>"(10:25)HyPOSEZ Internal Error:系統設定檔異常",
  "26"=>"(10:26)HyPOSEZ Internal Error:XID 交易序號異常",
  "27"=>"(10:27)HyPOSEZ Internal Error:AUTHRRPID 交易序號異常",
  "28"=>"(10:28)HyPOSEZ Internal Error:CREDRRPID 交易序號異常",
  "29"=>"(10:29)HyPOSEZ Internal Error:交易批次編號異常",
  "30"=>"(10:30)HyPOSEZS 開啟交易紀錄檔異常",
  "31"=>"(10:31)HyPOSEZ 與 SSL HyPOS Server 連線交易異常",
  "32"=>"(10:32)交易網頁資料已超過有效時間,請重新執行",
  "77"=>"(10:77)卡號次數超過交易次數限制",
  "80"=>"(10:80)URL 特店帶入參數格式錯誤",
  "88"=>"(10:88)使用者取消訂單",
  "95"=>"(10:95)帶入的 txType 參數與特店交易方式不符合",
  "99"=>"(10:99)持卡人在 POS URL 刷卡頁所輸入的圖形驗證碼不正確",
  "100"=>"(10:100)無客製化授權頁",
  "101"=>"(10:101)無此特店的資料",
  "102"=>"(10:102)merID 不一致",
  "105"=>"(10:105)資料庫連線失敗"
 ),
 "11" => array(),
 "12" => array(
  "11"=>"(12:11)銀行系統檢核交易 3D 驗證碼失敗",
  "21"=>"(12:21)單日累積授權金額超過 P.G.系統上限",
  "22"=>"(12:22)單日累積授權筆數超過 P.G.系統上限",
  "23"=>"(12:23)單筆授權金額超過 P.G.系統上限",
  "24"=>"(12:24)P.G.系統暫時停止特店交易",
  "25"=>"(12:25)P.G.系統或特店目前不接受該類信用卡交易",
  "26"=>"(12:26)P.G.系統或特店目前不接受該卡號交易",
  "27"=>"(12:27)P.G.系統尚未收到特店的收單申請",
  "28"=>"(12:28)P.G.系統尚未收到特店申請該項服務",
  "29"=>"(12:29)銀行 PG 未開放此類交易功能",
  "40"=>"(12:40)3D 驗證失敗",
  "41"=>"(12:41)持卡人輸入 3D 密碼驗證錯誤",
  "42"=>"(12:42)持卡人未申請 3D 驗證服務",
  "43"=>"(12:43)無法判讀卡號所屬的 3D 發卡行資訊",
  "44"=>"(12:44)無法收到 3D 發卡行的認證回應",
  "45"=>"(12:45)無法判讀 3D 發卡行回應的認證資料",
  "46"=>"(12:46)單卡累計授權金額超過限制",
  "47"=>"(12:47)機台單月累計授權金額超過限制"
 )
 );
  } // end of ::set_error_message()

  } // end of class hnbccu

