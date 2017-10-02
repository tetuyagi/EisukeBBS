<?php
/*****************************************
      PM bbs common file (2013/02/28)
         copyright Rocomotion 2005
         http://www.rocomotion.jp
*****************************************/
define('VERSION', '1.09');

#------------------------------------
#  DB接続
#------------------------------------
function db_connect() {
    try {
        $pdo = new PDO('mysql:dbname='. DB_NAME. ';host='. DB_SERVER_NAME. '', DB_USER , DB_PW,
            array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`"));
    } catch (PDOException $e) {
        die($e->getMessage());
    }
    return $pdo;
}
#------------------------------------
#  クエリ発行
#------------------------------------
function query_execute($stmt, $file, $line) {
    $flag = $stmt->execute();
    if (!$flag) {
        $info = $stmt->errorInfo();
        query_error('File:'. $file. '<br/ >Line:'. $line. '<br />Info:'. $info[2]);
        return false;
    }
    return true;

}
#------------------------------------
#  ヘッダ部分
#------------------------------------
function head()
{

    $ttl = TITLE;

echo '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
        <head>
            <title>
                ' . convert($ttl) .'
            </title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <link href="'. CSS. '" rel="stylesheet" type="text/css" />
        </head>
        <body>
';

}
#------------------------------------
#   フッタ部分
#------------------------------------
function foot()
{

    echo '
        <br />
        <div style="text-align:center;">
            + <a href="http://www.rocomotion.jp" target="_blank">PM bbs '. VERSION. ' </a> +
        </div>
    </body>
</html>
';
}
#------------------------------------
#  タイトル表示
#------------------------------------
function title()
{

    if (TGIF == "") {
        echo '
        <div class="title">
            '.TITLE.'
        </div>
        ';
    } else {
        echo '
        <img src="'. TGIF. '" width="'. TGIFW. '" height="'. TGIFH. '" alt="'. TITLE. '" />
        ';
    }
    echo '<br />';
}
#------------------------------------
#  文字コード変換
#------------------------------------
function convert($str, $chgno = '')
{

    if ($chgno == 2) {
        $chgstr = "EUC-JP";
    } elseif ($chgno == 3) {
        $chgstr = "SJIS";
    } elseif ($chgno == 4) {
        $chgstr = "JIS";
    } else {
        $chgstr = "UTF-8";
    }

    $code = mb_detect_encoding($str, "UTF-8, EUC-JP, SJIS, eucJP-win, SJIS-win, JIS, UTF-7, ASCII, ISO-2022-JP");
    if ($code == "") {
        $code = mb_detect_encoding($str, "auto");
    }
    if ($code == "") {
        $str = mb_convert_encoding($str, $chgstr);
    } else {
        $str = mb_convert_encoding($str, $chgstr, $code);
    }

    return $str;
}
#------------------------------------
#  エンコード
#------------------------------------
function encode($str)
{

    $str = htmlspecialchars(convert($str), ENT_QUOTES, 'utf-8');
    $str = str_replace("\r\n", "\r", $str);
    $str = str_replace("\r", "\n", $str);
    $str = str_replace("\n", "<br />", $str);
    if (get_magic_quotes_gpc()) {
        $str = stripslashes($str);
    }

    return $str;

}
#------------------------------------
#  改行削除
#------------------------------------
function br_delete($str)
{

    $str = str_replace("<br />", "\n", $str);
    return $str;

}
#------------------------------------
#  デコード
#------------------------------------
function decode($str)
{

    $str = str_replace("&amp;", "&", $str);
    $str = str_replace("&amp;amp", "&", $str);
    $str = str_replace("&lt;", "<", $str);
    $str = str_replace("&gt;", ">", $str);
    $str = str_replace("&quot;", "\"", $str);
    $str = stripslashes($str);

    return $str;
}
#------------------------------------
#  エラー
#------------------------------------
function query_error($err_msg)
{
    echo '
    <br />
    <div align="center">
        <span class="error">Error!</span>
        <br /><br />
        '. $err_msg. '
    </div>
    <br />
    ';
}
#------------------------------------
#  エラー
#------------------------------------
function error($err_msg)
{
    head();

    echo '
    <br />
    <div align="center">
        <span class="error">Error!</span>
        <br /><br />
        '. $err_msg. '
        <br /><br />
        <a href="javascript:history.back(-1);">Back</a>
    </div>
    <br />
    ';

    foot();
}
#------------------------------------
#  カウンタアップデート
#------------------------------------
function counter_update($chkip)
{

    $cnt = 0;
    $pdo = null;

    try {
        $pdo = db_connect();

        //値を更新
        $sql = "
        UPDATE
            ". COUNT_TABLE. "
        SET
            ". COUNT_CCOUNT. " = ". COUNT_CCOUNT. "+1, ". COUNT_CLASTIP. " = :ip
        WHERE
            ". COUNT_CNAME. " = '". COUNT_NAME. "'
            AND
            ". COUNT_CLASTIP. " <> :ip";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ip', $chkip, pdo::PARAM_STR);
        query_execute($stmt, __FILE__, __LINE__);

        //結果を得る
        $sql  = "
        SELECT
            ". COUNT_CCOUNT. "
        FROM
            ". COUNT_TABLE. "
        WHERE
            ". COUNT_CNAME. " = '". COUNT_NAME. "'
        LIMIT 1
    ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ip', $chkip, PDO::PARAM_STR);
        query_execute($stmt, __FILE__, __LINE__);

        //結果を配列へ挿入
        list($cnt) = $stmt->fetch(PDO::FETCH_NUM);

    } catch (PDOException $e) {
        error($e->getMessage());
    }
    $pdo = null;

    //配列の中のうちカウント数を返す
    return $cnt;
}
#------------------------------------
#  記事取得
#------------------------------------
function article_select($dflg, $cnum, $cpw)
{
    $pdo = null;
    $result = 0;
    try {
        $pdo = db_connect();

        if ($cpw != "")  {

            //記事を検索
            $sql = "
            SELECT
                *
            FROM
                ". BBS_TABLE. "
            WHERE
                ". BBS_CNUMBER. " = :no";

            //管理者パスワードの場合は、パスワード照合をしない。
            if ($cpw != PASSWORD && $_SESSION['spw'] != PASSWORD) {
                $sql .= "
                AND
                ". BBS_CPASS. " = :pw";
            }

            //削除を除く場合(dflg=1 → 完全削除・復活)
            if ($dflg == 0) {
                $sql .= "
                AND
                    ". BBS_CDEL. " <> 1
            ";
            }
            $sql .= "
            LIMIT 1
            ";

            //SQL実行
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':no', $cnum, PDO::PARAM_INT);

            if ($cpw != PASSWORD && $_SESSION['spw'] != PASSWORD) {
                $stmt->bindValue(':pw', $cpw, PDO::PARAM_STR);
            }

            query_execute($stmt, __FILE__, __LINE__);
            $count=$stmt->rowCount();

            if ($count > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            return 0;
        }
    } catch (PDOException $e) {
        error($e->getMessage());
    }

    $pdo = null;

    return $result;
}
#------------------------------------
#  DB登録・修正・削除・復活・完全削除
#------------------------------------
/*
 *  引数:$type = "add"     → 登録
 *       $type = "edit" → 修正
 *       $type = "del"  → 削除
 *       $type = "rev"  → 復活
 *       $type = "cdel" → 完全削除
 *       $type = "sysdel" → システム削除
 *       $dnum → 記事番号(削除・復活使用)
 */
function reg($type = '', $dnum = '')
{
    global $post;

    if ($type == "add" || $type == "edit" || $type == "res") {
        reg_check();
        reg_limit_chk($type);
    }

    //変数へ代入
    $rnum = $post['num'];
    $rrenum = $post['reno'];
    $rname = $post['name'];
    $rsubject = $post['subject'];
    $rcomment = $post['comment'];

    //$rhome = str_replace("http://","",$_POST['home'];
    $rmail = $post['mail'];
    $rhome = $post['home'];
    $rpass = $post['pass'];

    $ip = get_ip();
    $host = get_host();
    $ua = get_useragent();

    $time = time() + 9 * 60 * 60;
    $time = gmdate("Y-m-d G:i:s",$time);

    $rrenum = $rrenum ? $rrenum : 0;

    $pdo = null;

    try {

        $pdo = db_connect();

        //DB書き込み
        switch ($type) {
            case "edit":
                $sql  = "
                UPDATE
                    ". BBS_TABLE. "
                SET
                    ". BBS_CNAME. " = :name,
                    ". BBS_CMAIL. " = :mail,
                    ". BBS_CHOME. " = :home,
                    ". BBS_CTITLE. " = :title,
                    ". BBS_CCOMMENT. " = :comment
                WHERE
                    ". BBS_CNUMBER. " = :no
                LIMIT 1";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':name', $rname, PDO::PARAM_STR);
                $stmt->bindValue(':mail', $rmail, PDO::PARAM_STR);
                $stmt->bindValue(':home', $rhome, PDO::PARAM_STR);
                $stmt->bindValue(':title',  $rsubject, PDO::PARAM_STR);
                $stmt->bindValue(':comment', $rcomment, PDO::PARAM_STR);
                $stmt->bindValue(':no', $rnum, PDO::PARAM_INT);
                query_execute($stmt, __FILE__, __LINE__);
                break;

            case "del":

                $sql = "
                UPDATE
                    ". BBS_TABLE. "
                SET
                    ". BBS_CDEL. " = 1
                WHERE
                    ". BBS_CNUMBER. " = :no OR ". BBS_CRENUM. " = :no";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':no', $dnum, PDO::PARAM_INT);
                query_execute($stmt, __FILE__, __LINE__);
                break;

            case "rev":

                $sql = "
                UPDATE
                    ". BBS_TABLE. "
                SET
                    ". BBS_CDEL. " = 0
                WHERE
                    ". BBS_CNUMBER. " = :no";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':no', $dnum, PDO::PARAM_INT);
                query_execute($stmt, __FILE__, __LINE__);
                break;

            case "cdel":
            case "sysdel":

                $sql = "
                DELETE FROM
                    ". BBS_TABLE. "
                WHERE
                    ". BBS_CNUMBER. " = :no OR ". BBS_CRENUM. " = :no";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':no', $dnum, PDO::PARAM_INT);
                query_execute($stmt, __FILE__, __LINE__);
                break;

            default:

                $sql = "
                INSERT INTO
                ". BBS_TABLE. " (". BBS_CRENUM. ", ". BBS_CNAME. ", ". BBS_CMAIL. ", ". BBS_CHOME. ", ". BBS_CTITLE. ", ". BBS_CCOMMENT. ", ". BBS_CPASS. ", ". BBS_CDATE. ", ". BBS_CRESUPDATE. ", ". BBS_CIP. ", ". BBS_CHOST. ", ". BBS_CUA. ")
                VALUES
                (:renum, :name, :mail, :home, :title, :comment, :pw, '$time', '$time', :ip, :host, :ua)";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':renum', $rrenum, PDO::PARAM_INT);
                $stmt->bindValue(':name', $rname, PDO::PARAM_STR);
                $stmt->bindValue(':mail', $rmail, PDO::PARAM_STR);
                $stmt->bindValue(':home', $rhome, PDO::PARAM_STR);
                $stmt->bindValue(':title', $rsubject, PDO::PARAM_STR);
                $stmt->bindValue(':comment', $rcomment, PDO::PARAM_STR);
                $stmt->bindValue(':pw', $rpass, PDO::PARAM_STR);
                $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
                $stmt->bindValue(':host', $host, PDO::PARAM_STR);
                $stmt->bindValue(':ua', $ua, PDO::PARAM_STR);
                query_execute($stmt, __FILE__, __LINE__);

				if ($type == "res") {
                    $sql  = "
                    UPDATE
                    ". BBS_TABLE. "
                    SET
                    ". BBS_CRESUPDATE. " = '$time'
                    WHERE
                    ". BBS_CNUMBER. " = :no
                    LIMIT 1";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':no', $rrenum, PDO::PARAM_INT);
                    query_execute($stmt, __FILE__, __LINE__);
				}

                break;
        }

        $pdo = null;

        //登録のみクッキー発行
        if ($type == "add" || $type == "res" || ($type == "edit" && $rpass != PASSWORD)) {
            set_cook($rname, $rmail, $rhome, $rpass);

            //最大保持件数が0の場合はレコード数チェックを行わない
            if(MAXDATA != 0) {
                max_article_check();
            }
        }

        //TOPへ戻す
        switch ($type) {
            case "add":
            case "res":
            case "edit":
            case "del":
                $url = SCRIPT;
                break;

            case "rev":
            case "cdel":
                $url = ADMIN_SCRIPT;
                break;

            default:
                return;

        }

        header("Location: $url");

    } catch (PDOException $e) {
        error($e->getMessage());
    }

    $pdo = null;
}
#------------------------------------
#  クッキー発行  
#------------------------------------
function set_cook($ckname, $ckmail, $ckhome, $ckpass)
{
  
    $cookvalue = implode(",", array($ckname, $ckmail, $ckhome, $ckpass));
    setcookie ("PM_bbs", $cookvalue, time()+30*24*3600); 
    return;
}
#------------------------------------
#  記事数確認
#------------------------------------
function max_article_check()
{
    $pdo = null;
    try {

        $pdo = db_connect();

        $sql = "
        SELECT
            ". BBS_CNUMBER. "
        FROM
            ". BBS_TABLE. "
        WHERE
            ". BBS_CRENUM. " = 0
            AND
            ". BBS_CDEL. " <> 1
        ORDER BY
            ". BBS_CNUMBER. " ASC
        ";

        $stmt = $pdo->prepare($sql);
        query_execute($stmt, __FILE__, __LINE__);
        $rows = $stmt->rowCount();

        if ($rows > MAXDATA) {

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                reg("sysdel", $row[BBS_CNUMBER]);
                $rows = $rows - 1;

                if ($rows <= MAXDATA) {
                    break;
                }
            }
        }

    } catch (PDOException $e) {
        error($e->getMessage());
    }
    $pdo = null;

    return;
}
#------------------------------------
#  ログオン
#------------------------------------
function logon()
{
    global $post;

    $pw = $post['adpw'];

    if ($pw == PASSWORD) {

        $_SESSION['spw'] = $pw;

    } else {

        error("パスワードが違います。");
        session_unset();
        session_destroy();

        exit;

    }

    $url = ADMIN_SCRIPT;

    header("Location: $url");

}
#------------------------------------
#  ログアウト
#------------------------------------
function logout()
{
    session_unset();

    session_destroy();

    header("Location: ". ADMIN_SCRIPT. "");

}
#------------------------------------
#  入力値チェック
#------------------------------------
function reg_check()
{
    global $post;

    $er = "";
    $erflg = 0;

    if ($post['name'] == "") {
        $erflg = 1;
        $er .= "お名前が未入力です。<br />";
    }

    if ($post['comment'] == "") {
        $erflg = 1;
        $er .= "コメントが未入力です。<br />";
    }

    if ($post['subject'] == "") {
        $post['subject'] = "題名なし";
    }

    if ($post['home'] != "" && $post['home'] != "http://") {
        if (!preg_match("/^(https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+)/", $post['home'])) {
            $erflg = 1;
            $er = "ホームの形式が不正です";
        }
    }

    if ($post['mail'] != "") {
        if (!preg_match("/^[^@]+@[^.]+\..+/", $post['mail'])) {
            $erflg = 1;
            $er .= "Eメールの形式が不正です";
        }
    }

    if ($post['pass'] != "") {
        if (!preg_match("/[-_.!~*()a-zA-Z0-9;\/?:&@=+$,%#]+/", $post['pass'])) {
            $erflg = 1;
            $er .= "パスワードに使用できない文字が含まれています。<br />";
        }
    }

    //桁数チェック
    //2-->name
    //3-->mail
    //4-->home
    //5-->title
    //7-->pw

    $arr1 = array(50, 100, 100, 100, 15);
    $arr2 = array($post['name'], $post['mail'], $post['home'], $post['subject'], $post['pass']);
    $arr3 = array("お名前", "Eメール", "ホーム", "題名", "パスワード");
    $erflg = 0;

    for ($i = 0; $i < count($arr1); $i++) {
        if ($arr1[$i] < strlen($arr2[$i])) {
            $er .=  $arr3[$i]. "が長すぎます。<br />";
            $erflg = 1;
        }
    }

    if ($erflg == 1) {

        error($er);
        exit;

    }

    return;
}
#------------------------------------
#  URLオートリンク
#------------------------------------
function autolink($str)
{

    $str = preg_replace("/([^=^\"]|^)(https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+)/","\\1<a href=\"\\2\" target='_blank'>+リンク+</a>",$str);

    return $str;

}
#------------------------------------
#  入力許可チェック
#------------------------------------
function reg_limit_chk($type)
{

    global $deny, $ng_word, $post;


    //IP等々取得
    $ip  = get_ip();
    $host = get_host();

    $err = 0;
    if (SCRIPTURL != "") {
        $ref = getenv("HTTP_REFERER") ? getenv("HTTP_REFERER") : $_SERVER['HTTP_REFERER']; 
        $sc = preg_replace("/\./", "\.", SCRIPTURL);
        $sc = preg_replace("/\//", "\/", $sc);

        if (!preg_match("/$sc/i", $ref)) {
            $err = 1;
        }
    }

    foreach($deny as $line) {
        if ($line != "") {
            $line = preg_replace("/\./", "\.", $line);
            $line = preg_replace("/\*/", ".*", $line);

            if (preg_match("/$line/", $ip)) {
                $err = 1;
                break;
            } elseif (preg_match("/$line/", $host)) {
                $err = 1;
               break;
            }
        }
    }

    //スパム対策
    $chkcom = $post['comment'];
    if (SPAM) {
        $chkcom2 = convert($chkcom, 3);
        if (!preg_match("/(\\x82[\\x9f-\\xf2]|\\x81\\x5b|\\x83[\x40-\x96]){2,}/", $chkcom2)) {
            $err = 4;
        }
    }

    foreach($ng_word as $line) {
        if ($line != "") {

            if (strstr($chkcom, $line)) {
                $err = 4;
                break;
            }
        }
    }

    //URL数チェック
    $ct = preg_match_all("/([^=^\"]|^)(https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+)/", $chkcom, $matches);

    if (URLCOUNT < $ct) {
        $err = 3;
    }

    //文字数チェック
    foreach($post as $k => $v) {
        if ($k == "comment" && MAXCOMMENT < Klength($v)) {
            $err = 2;
        } elseif ($k == "home" && MAXURL < strlen($v)) {
            $err = 2;
        } elseif ($k == "name" && MAXNAME < Klength($v)) {
            $err = 2;
        } elseif ($k == "mail" && MAXMAIL < strlen($v)) {
            $err = 2;
        } elseif ($k == "subject" && MAXSUBJECT < Klength($v)) {
            $err = 2;
        } elseif ($k == "pass" && MAXPASS < strlen($v)) {
            $err = 2;
        } elseif ($k == "no" || $k == "reno") {
            if (!preg_match("/^([\d]+)?$/", $v)) {
                $err = 4;
        	} elseif ($k == "reno") {
                if ($type != "res") {
                    $err = 4;
                } else {

                    //親記事検索
                    $art = article_select(0, $v, PASSWORD);

                    if ($art != 0 && $art[BBS_CRENUM] != 0) {
                        $err = 4;
                    }
                }
            }
        }
    }

    if ($err) {
        switch ($err) {
            case 1:
                error("あなたのコメントは受付できません。");
                break;
            case 2:
                error("文字数が制限を超えています。");
                break;
            case 3:
                error("コメントに使用できるURL数が制限を超えています。");
                break;
            default:
                error("スパムコメントと見なしたために投稿は受付できません。");
                break;
        }
        exit;
    }

    return;
}
#------------------------------------
#  日本語混在の文字列の長さを取得
#------------------------------------
function Klength($str)
{

    $klen = 0;
    for ($ki = 0; $ki < strlen($str); $ki++){
        $c = substr($str, $ki, 1);

        //文字のアスキー値を取得
        $cb = ord($c);

        //EUC
        if (($cb >= 0x80) && ($cb <= 0xFF)) {
            $ki++;
        }

        $klen++;
    }
    return $klen;
}
#-----------------------------------------------
#  サニタイズ
#-----------------------------------------------
function sanitize($arr)
{

    foreach ($arr as $key => $val) {

        if (is_array($val)) {
            $arr[$key] = sanitize($val);
        } else {
            $arr[$key] = encode($val);
        }
    }

    return $arr;
}
#-----------------------------------------------
#  &をデコード
#-----------------------------------------------
function and_decode($str)
{

    $str = str_replace("&amp;", "&", $str);

    return $str;

}
#-----------------------------------------------
#  IP取得
#-----------------------------------------------
function get_ip()
{

    $ret = getenv("REMOTE_ADDR") ? getenv("REMOTE_ADDR") : $_SERVER['REMOTE_ADDR'];
    $ret = encode($ret);

    return $ret;
}
#-----------------------------------------------
#  ホスト名取得
#-----------------------------------------------
function get_host()
{

    $ret = getenv("REMOTE_HOST") ? getenv("REMOTE_HOST") : $_SERVER['REMOTE_HOST'];
    if ($ret == "") {
        $ip = get_ip();
        $ret = gethostbyaddr($ip);
    }
    $ret = encode($ret);

    return $ret;
}
#-----------------------------------------------
#  ユーザーエージェント取得
#-----------------------------------------------
function get_useragent()
{

    $ret = getenv("HTTP_USER_AGENT") ? getenv("HTTP_USER_AGENT") : $_SERVER['HTTP_USER_AGENT'];
    $ret = encode($ret);

    return $ret;
}
#-----------------------------------------------
#  プレースホルダ
#-----------------------------------------------
function quote_smart($value, $like = 0)
{
    // 数値あるいは数値形式の文字列以外をクオートする
    if (!is_numeric($value)) {
        if ($like) {
            $value = "'%". mysql_real_escape_string($value). "%'";
        } else {
            $value = "'". mysql_real_escape_string($value). "'";
        }
    }
    return $value;
}
#-----------------------------------------------
# エンティティー
#-----------------------------------------------
function entity($str)
{
    return htmlspecialchars($str);
}
?>
