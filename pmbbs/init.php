<?php
#---------------------------------------
#  PM bbs make_table_file (2013/02/28)
#    copyright Rocomotion 2005          
#    http://www.rocomotion.jp           
#---------------------------------------

require_once('./ini.php');
require_once('./func.php');

#文字化け対策
@mb_language("Japanese");
@mb_internal_encoding("utf-8");

$pdo = null;
$rows = 0;
try {

    #データベースを開く
    $pdo = db_connect();
    $pdo->beginTransaction();

    #BBSデータテーブル作成
    $sql  =  "CREATE TABLE IF NOT EXISTS ".BBS_TABLE." (";
    $sql .= BBS_CNUMBER." int(32) unsigned NOT NULL auto_increment,";
    $sql .= BBS_CRENUM." int(32) unsigned NOT NULL DEFAULT 0,";
    $sql .= BBS_CNAME." VARCHAR(50) NOT NULL,";
    $sql .= BBS_CMAIL." VARCHAR(100) DEFAULT NULL,";
    $sql .= BBS_CHOME." VARCHAR(100) DEFAULT NULL,";
    $sql .= BBS_CTITLE." VARCHAR(100) NOT NULL,";
    $sql .= BBS_CCOMMENT." text NOT NULL,";
    $sql .= BBS_CPASS." VARCHAR(15) DEFAULT NULL,";
    $sql .= BBS_CDATE." datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
    $sql .= BBS_CRESUPDATE." datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
    $sql .= BBS_CIP." VARCHAR(25) DEFAULT '0.0.0.0',";
    $sql .= BBS_CHOST." VARCHAR(100) DEFAULT NULL,";
    $sql .= BBS_CUA." VARCHAR(255) DEFAULT NULL,";
    $sql .= BBS_CDEL." tinyint(1) NOT NULL DEFAULT '0',";
    $sql .= "PRIMARY KEY (".BBS_CNUMBER."))";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    #カウンタテーブル作成
    $sql =  "CREATE TABLE IF NOT EXISTS ".COUNT_TABLE." (";
    $sql .= COUNT_CNAME." VARCHAR(100) NOT NULL DEFAULT '0',";
    $sql .= COUNT_CCOUNT." int(32) NOT NULL DEFAULT 0,";
    $sql .= COUNT_CLASTIP." VARCHAR(25) NOT NULL DEFAULT '0.0.0.0',";
    $sql .= "PRIMARY KEY (".COUNT_CNAME."))";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    #カウンタへ挿入(確認してから)
    $sql =  "SELECT * FROM ".COUNT_TABLE;
    $sql .= " WHERE ".COUNT_CNAME." = '".COUNT_NAME."'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->rowCount();

    if ($rows == 0) {
        $sql =  "INSERT INTO ".COUNT_TABLE."(".COUNT_CNAME.",".COUNT_CCOUNT.",".COUNT_CLASTIP.")";
        $sql .= " VALUES ('".COUNT_NAME."','0','0.0.0.0')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $pdo->commit();

} catch (PDOException $e ){

    error($e->getMessage());

}
$pdo = null;

#ヘッダ読み込み
head();

echo '
<div align="center">
';

if($rows == 0) {

    echo '
  <br />
  テーブルの作成が完了いたしました。
  このファイルは削除してください。
  ';

} else {

    echo '
  すでに '.COUNT_NAME.'のレコードが存在していますので、<br />
  カウンタテーブルは作成されませんでした。<br />
  データテーブルは作成されました。<br />
  RocomotionのPHPはカウンタテーブルを共有しますので、<br />
  Rocomotionのほかのスクリプトをお使いの方は問題ございません。
  ';

}

echo '
</div>
';

foot();
exit;
?>