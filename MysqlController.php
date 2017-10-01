<!DOCTYPE html>
<html>
<head>
<meta charset='utf8'>
<title>MysqlController</title>
</head>


<body>


<p>
<?php echo "php test"; ?>
</p>

<?php
$host = 'mysql1.php.xdomain.ne.jp';
$dbname = 'tyagiserver_bbs';
$charset = 'utf8';
$dsn = 'mysql:host='.$host.';dbname='.$dbname.';charset='.$charset;
$username = 'tyagiserver_root';
$password = 'password';


try{
  $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_EMULATE_PREPARES => false));
  echo '<p> データベース接続成功。</p>';
}catch(PDOException $e){
$message =  'データベース接続失敗。' .$e->getMessage();
    echo '<p> ' . $message . '</p>';
    exit($message);
    }
?>


</body>
</html>
