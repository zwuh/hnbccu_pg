<?php
/**
 * hnbccu_dummy.php : HNBCCU Payment Module Dummy Payment Gateway
 * 
 * @package pg_hnbccu
 * @copyright 2012 National Taiwan University
 * @license GNU General Public License v2
 * @version $Id: hnbccu_dummy.php 1 2012-02-09 13:30:00Z zh $
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>HNBCCU - Dummy Payment Gateway</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
echo "<hr />POST<hr />\n"; var_dump($_POST); echo "<hr />\n";
echo "<hr />GET<hr />\n"; var_dump($_GET); echo "<hr />\n";

$lidm = isset($_POST['lidm']) ? $_POST['lidm'] : '';
$amount = isset($_POST['purchAmt']) ? $_POST['purchAmt'] : '0';
$xid = sha1(mt_rand().$lidm.$amount); // SHA-1 sum text length is 40
$authCode = substr(md5(mt_rand().$lidm.$amount), 4, 10);
$mer_id = isset($_POST['merID']) ? $_POST['merID'] : '';
$authResURL = isset($_POST['AuthResURL']) ? $_POST['AuthResURL'] : 'about:blank';

$frm = '<form action="'.$authResURL.'" method="post">'."\n"
  . 'lidm[19]:<input name="lidm" type="text" value="'.$lidm.'" /><br />'."\n"
  . 'xid[40]:<input name="xid" type="text" value="'.$xid.'" /><br />'."\n"
  . 'merID[3]:<input name="merID" type="text" value="'.$mer_id.'" /><br />'."\n"
  . 'Last4digitPAN[4]:<input name="Last4digitPAN" type="text" value="0000" /><br />'."\n"
  . 'errDesc[128]:<input name="errDesc" type="text" value="" /><br />'."\n";

?>

<!-- first form: success -->
<?php echo $frm; ?>
 status[int]: <input name="status" type="text" value="0" /><br />
 authAmt[int]:<input name="authAmt" type="text" value="<?php echo $amount; ?>" /><br />
 authCode[6]:<input name="authCode" type="text" value="<?php echo $authCode; ?>" /><br />
 <input type="submit" value="GRANT" />
 </form>
<!-- second form: fail -->
<hr />
<?php echo $frm; ?>
 status[int]: <input name="status" type="text" value="" /><br />
 errcode[2]: <input name="errcode" type="text" value="" /><br />
 <input type="submit" value="DENY" />
 </form>
</body>
</html>
