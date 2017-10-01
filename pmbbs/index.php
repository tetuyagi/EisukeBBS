<?php
/*****************************************
      PM bbs main file (2013/02/28)
         copyright Rocomotion 2005
         http://www.rocomotion.jp
*****************************************/

//文字化け対策
@ini_set('mbstring.detect_order', 'auto'); 
@ini_set('mbstring.http_input', 'pass'); 
@ini_set('mbstring.http_output', 'pass');
@ini_set('mbstring.internal_encoding', 'neutral'); 
@ini_set('mbstring.substitute_character', 'none'); 

@mb_language('Japanese');
@mb_internal_encoding('utf-8');

require_once('./ini.php');
require_once('./func.php');

$post = sanitize($_POST);
$get = sanitize($_GET);
if ($post['mode'] == "reg") { reg("add"); }
elseif ($post['mode'] == "edit") { edit(0); } 
elseif ($post['mode'] == "editreg") { reg("edit"); } 
elseif ($post['mode'] == "del") { edit(1); }
elseif ($post['mode'] == "res") { res(); }
elseif ($post['mode'] == "resreg") { reg("res"); } 
elseif ($get['mode'] == "howto") { howto(); } 
view();

#------------------------------------
#  メイン表示  
#------------------------------------
function view()
{
    global $get;

    $ip = get_ip();
    if (empty($ip)) {
        $ip = "0.0.0.0";
    }

    $pg = $get['page'] ? $get['page'] : 1;
    $stt = $get['st'];


    //カウントアップ
    $count = counter_update($ip);
    $count = sprintf("%05d", $count);

    //ヘッダを表示
    head();

    //カウンタ表示
    echo '
    <table summary="counter" width="'. TABLE_WIDTH. '" align="center">
        <tr>
            <td class="count">
                '. $count. '
            </td>
        </tr>
    </table>
    ';

    //タイトル表示
    title();

    echo '
    <table summary="topmenu" width="'. TABLE_WIDTH. '" align="center">
        <tr>
            <td align="right">
                <a href="'. HOMEPAGE. '" target="'. TARGET. '">home</a>
                 | 
                <a href="'. SCRIPT. '?mode=howto">howto</a>
                 | 
                <a href="'. ADMIN_SCRIPT. '" target="_blank">admin</a>
                 | 
            </td>
        </tr>
    </table>
    <br />
    ';

    //書き込みフォーム表示
    form();

    $start = $pg * MAXVIEW - MAXVIEW;
    $increment = MAXVIEW;

    try {
        $pdo = db_connect();

        $sql  = "
        SELECT
            SQL_CALC_FOUND_ROWS
            ". BBS_CNUMBER. ",
            ". BBS_CNAME. ",
            ". BBS_CMAIL. ",
            ". BBS_CHOME. ",
            ". BBS_CTITLE. ",
            ". BBS_CCOMMENT. ",
            ". BBS_CDATE. "
        FROM
            ". BBS_TABLE. "
        WHERE
            ". BBS_CDEL. " <> 1
            AND
            ". BBS_CRENUM. " = 0
        ORDER BY
            ". BBS_CRESUPDATE. " DESC
        LIMIT
            :start, :end";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':end', $increment, PDO::PARAM_INT);
        query_execute($stmt, __FILE__, __LINE__);
        $rows = $stmt->rowCount();

        $sql = "SELECT FOUND_ROWS()";
        $res = $pdo->query($sql);

        list($all_rows) = $res->fetch(PDO::FETCH_NUM);

        if ($rows > 0) {
            //表示
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $num = $row[BBS_CNUMBER];
                $name = $row[BBS_CNAME];
                $mail = $row[BBS_CMAIL];
                $home = $row[BBS_CHOME];
                $subject = $row[BBS_CTITLE];
                $comment = $row[BBS_CCOMMENT];
                $date = $row[BBS_CDATE];

                $name = and_decode($name);
                $subject = and_decode($subject);
                $comment = and_decode($comment);

                $comment = autolink($comment);

                echo '
            <table width="'. TABLE_WIDTH. '" align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td colspan="2">
                        <table width="'. TABLE_WIDTH. '" align="center" cellpadding="3" cellspacing="0">
                            <tr>
                                <td class="thread_title">
                                    '. $subject. '
                                </td>
                                ';
                if (RESFLG) {
                    echo '
                                    <td class="thread_main_res">
                                        <form action="'.SCRIPT.'" method="post">
                                            <input type="hidden" name="mode" value="res" />
                                            <input type="hidden" name="reno" value="'. $num. '" />
                                            <input type="submit" value="返信" />
                                        </form>
                                    </td>
                                    ';
                } else {
                    echo '
                                    <td class="thread_main_res">
                                        &nbsp;
                                    </td>
                                    ';
                }
                echo '
                            </tr>
                            <tr>
                                <td colspan="2" class="thread_comment">
                                   '.$comment.'
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="thread_bottom">
                                    <span class="thread_name">'. $name. '</span>';
                if ($mail == "") {
                    echo ' [M] ';
                } else {
                    $m = entity('mailto:'. $mail);
                    echo ' [<a href="'. $m. '">M</a>] ';
                }

                //旧バージョン対応
                if ($home != "" && !preg_match("/https?:\/\//", $home)) {
                    $home = 'http://'. $home;
                }
                if ($home == "" || $home == "http://") {
                    echo '[H]';
                } else {
                    echo '[<a href="'. $home. '" target="_blank">H</a>]';
                }
                echo '<br />
                                    <span class="thread_date">'. $date. '</span> [<span class="thread_no">'. $num. '</span>]
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                ';

                if (RESFLG) {
                    //レス記事があれば表示
                    $sql = "
                        SELECT
                            ". BBS_CNUMBER. ",
                            ". BBS_CNAME. ",
                            ". BBS_CMAIL. ",
                            ". BBS_CHOME. ",
                            ". BBS_CTITLE. ",
                            ". BBS_CCOMMENT. ",
                            ". BBS_CDATE. "
                        FROM
                            ". BBS_TABLE. "
                        WHERE
                            ". BBS_CDEL. " <> 1
                        AND
                            ". BBS_CRENUM. " = :no
                        ORDER BY
                            ". BBS_CNUMBER. " ASC";
                    $stmt2 = $pdo->prepare($sql);
                    $stmt2->bindValue(':no', $num, PDO::PARAM_INT);
                    $stmt2->execute();
                    $res_rows = $stmt2->rowCount();

                    $res_width = TABLE_WIDTH - 50;

                    if ($res_rows > 0) {
                        while($row_res = $stmt2->fetch(PDO::FETCH_ASSOC)) {

                            //変数へ代入
                            $num = $row_res[BBS_CNUMBER];
                            $name = $row_res[BBS_CNAME];
                            $mail = $row_res[BBS_CMAIL];
                            $home = $row_res[BBS_CHOME];
                            $subject = $row_res[BBS_CTITLE];
                            $comment = $row_res[BBS_CCOMMENT];
                            $date = $row_res[BBS_CDATE];

                            $name = and_decode($name);
                            $subject = and_decode($subject);
                            $comment = and_decode($comment);

                            $comment = autolink($comment);

                            echo '
                            <tr>
                                <td width="50">
                                    &nbsp;
                                </td>
                                <td>
                                    <table width="'. $res_width. '" align="right" cellpadding="3" cellspacing="0" class="thread_res">
                                        <tr>
                                            <td class="thread_res_title">
                                                '. $subject. '
                                            </td>
                                            <td class="thread_res_res">
                                                &nbsp;
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="thread_res_comment">
                                                '. $comment. '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="thread_res_bottom">
                                                <span class="thread_name">'. $name. '</span>';
                            if ($mail == "") {
                                echo ' [M] ';
                            } else {
                                $m = entity('mailto:'. $mail);
                                echo ' [<a href="'. $m. '">M</a>] ';
                            }

                            //旧バージョン対応
                            if ($home != "" && !preg_match("/https?:\/\//", $home)) {
                                $home = 'http://'. $home;
                            }
                            if ($home == "" || $home == "http://") {
                                echo '[H]';
                            } else {
                                echo '[<a href="' . $home . '" target="_blank">H</a>]';
                            }
                            echo '<br />
                                                <span class="thread_res_date">'. $date. '</span> [<span class="thread_res_no">'. $num. '</span>]
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            ';
                        }
                    }
                }
                echo '
            </table>
            ';
            }
        } else {
            echo '
        <div align="center">
            データがありません。
        </div>
        ';
        }

        echo '
    <table width="'. TABLE_WIDTH. '" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <hr />
            </td>
        </tr>
    </table>
    ';

        //next,backボタン表示の設定
        $next = $pg + 1;
        $back = $pg - 1;

        if ($back >= 0) {
            $p_flag=1;
        }
        if ($next < $all_rows) {
            $p_flag=1;
        }

        //ページング
        if ($p_flag) {
            $x=1;

            if ($stt == "") {
                $stt = 1;
            } else {
                $all_rows = $all_rows - ($stt-1) * MAXVIEW;
            }
            echo '
        <table width="'. TABLE_WIDTH. '" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td align="right">
                    Page :
                    ';
            $pg = $pg ? $pg : 1;

            if ($stt - 10 > 0) {
                $morebackpg = $stt - 1;
                $st2 = $stt - 10;
                echo '<a href="'. SCRIPT. '?page='. $morebackpg. '&amp;st='. $st2. '">back &#171; </a> / ';
            } else {
                $st2 = 1;
            }

            $x = $stt;

            while ($all_rows > 0) {
                if ($pg == $x ) {
                    echo '<span style="font-weight:bold;">'.$x.'</span>';
                } else {
                    echo '<a href="'. SCRIPT. '?page='. $x. '&amp;st='. $stt. '">'. $x. '</a>';
                }
                $x++;
                $all_rows = $all_rows - MAXVIEW;
                if ($all_rows > 0) {
                    echo ' / ';
                }
                if ($x > $stt+9) {
                    break;
                }
            }

            if ($all_rows > 0) {
                $morenextpg = $x;
                echo '<a href="'. SCRIPT. '?page='. $morenextpg. '&amp;st='. $x. '"> &#187; next</a>';
            }
            echo '
                </td>
            </tr>
        </table>
        <br />
        ';
        }

        //修正フォーム
        echo '
        <table width="'. TABLE_WIDTH. '" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td align="right">
                    記事の修正・削除はこちらから。
                    <br />
                    <form action="'. SCRIPT. '" method="post">
                        No.<input type="text" name="eno" size="5" />
                        Pass.<input type="password" name="epw" size="8" />
                        <select name="mode">
                            <option value="edit">修正</option>
                            <option value="del">削除</option>
                        </select>
                        <input type="submit" value="送信" />
                    </form>
                </td>
            </tr>
        </table>
        ';
    } catch (PDOException $e) {
        error($e->getMessage());
    }

    $pdo = null;

    foot();

    exit;
}
#------------------------------------
#  フォーム部分
#------------------------------------
function form($type = '', $enum = '', $ename = '', $email = '', $ehome = '', $etitle = '', $ecomment = '', $epass = '')
{

    //修正の場合はvalue値を変更

    if ($type == "edit" || $type == "res") {
        head();

        echo '
        <table width="'.TABLE_WIDTH.'" align="center" cellpadding="3" cellspacing="0">
            <tr>
                <td>
                    <a href="javascript:history.back();">&#171; back</a>
                </td>
            </tr>
        </table>
        ';

        if ($type == "edit") {

            echo '
            <div align="center">
                記事修正フォーム
            </div>
            ';

            $ck_name = $ename;
            $ck_mail = $email;
            $ck_home = $ehome ? $ehome : "http://";

        } else {

            echo '
            <div align="center">
                返信フォーム
            </div>
            ';

            $etitle = "Re:". $etitle;

        }
    }
    if ($type != "edit") {

        //クッキー取得
        list($ck_name, $ck_mail, $ck_home, $ck_pw) = explode(",", $_COOKIE['PM_bbs']);

        //旧バージョン対応
        if (!preg_match("/https?:\/\//", $ck_home)) {
            $ck_home = 'http://'. $ck_home;
        }
    }

    echo '
    <form name="myForm" action="'. SCRIPT. '" method="post">
        ';

        if ($type == "edit") {
            echo '
            <input type="hidden" name="mode" value="editreg" />
            <input type="hidden" name="num" value="'. $enum. '" />
            <input type="hidden" name="pass" value="'. $epass. '" />
            ';
        } elseif ($type == "res") {
            echo '
            <input type="hidden" name="mode" value="resreg" />
            <input type="hidden" name="reno" value="'. $enum. '" />
            ';
        } else{
            echo '
            <input type="hidden" name="mode" value="reg" />
            ';
        }
        echo '
        <table width="'. TABLE_WIDTH. '" align="center" cellpadding="3" cellspacing="0">
            <tr>
                <td width="100">
                    お名前
                </td>
                <td>
                    <input type="text" size="40" maxlength="'. MAXNAME. '" name="name" value="'. $ck_name. '" />
                </td>
            </tr>
            <tr>
                <td>
                    Eメール
                </td>
                <td>
                    <input type="text" size="40" maxlength="'. MAXMAIL. '" name="mail" value="'. $ck_mail. '" />
                </td>
            </tr>
            <tr>
                <td>
                    ホーム
                </td>
                <td>
                    <input type="text" size="40" maxlength="'. MAXURL. '" name="home" value="'. $ck_home. '" />
                </td>
            </tr>
            <tr>
                <td>
                    題名
                </td>
                <td>
                    <input type="text" size="40" maxlength="'. MAXSUBJECT. '" name="subject" value="'. $etitle. '" />
                </td>
            </tr>
            <tr>
                <td>
                    コメント
                </td>
                <td>
                    <textarea name="comment" rows="7" cols="50">'. $ecomment. '</textarea>
                </td>
            </tr>
            ';
            if ($type != "edit") {
                echo '
                <tr>
                    <td>
                        パスワード
                    </td>
                    <td>
                        <input type="password" size="8" maxlength="'. MAXPASS. '" name="pass" value="'. $ck_pw. '" />
                    </td>
                </tr>
                ';
            }
            echo '
            <tr>
                <td colspan="2" align="right">
                    <input type="button" value="書込" onclick="javascript:check_form();" />
                </td>
            </tr>
        </table>
    </form>
    ';
?>

<script type="text/javascript">
<!--

    function check_form() {
        var err = "";
        var errflg = 0;
        if (document.myForm.name.value == "") {
            err += "・お名前を入力してください。\n";
            errflg = 1;
        }
        if (document.myForm.subject.value == "") {
            err += "・題名を入力してください。\n";
            errflg = 1;
        }
        if (document.myForm.comment.value == "") {
            err += "・コメントを入力してください。\n";
            errflg = 1;
        }

        if (errflg) {
            alert(err);
            return false;
        }

        if (document.myForm.pass.value == "") {
            if (!confirm("パスワードが設定されていません。\nパスワードが無いと修正・削除が出来ませんがよろしいですか?")) {
                return false;
            }
        }

        document.myForm.submit();
    }

    function getByte(str) {
        count = 0;
        for (i = 0; i < str.length; i++) {
            n = escape(str.charAt(i));
            if (n.length < 4) { 
                count++;
            } else {
                count+=2;
            }
        }
	    return count;
    }

//-->
</script>
<?php

    //レスの場合は関連記事を表示
    if ($type == "res") {

        try {

            $pdo = db_connect();

            $sql = "
                SELECT
                    ". BBS_CNUMBER. ",
                    ". BBS_CRENUM. ",
                    ". BBS_CNAME. ",
                    ". BBS_CMAIL. ",
                    ". BBS_CHOME. ",
                    ". BBS_CTITLE. ",
                    ". BBS_CCOMMENT. ",
                    ". BBS_CDATE. "
                FROM
                    ". BBS_TABLE. "
                WHERE
                    ". BBS_CDEL. " <> 1
                    AND
                    (". BBS_CRENUM. " = :no OR ". BBS_CNUMBER. " = :no)
                ORDER BY
                    ". BBS_CNUMBER. " ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':no', $enum, PDO::PARAM_INT);
            query_execute($stmt, __FILE__, __LINE__);
            $rows = $stmt->rowCount();

            if ($rows > 0) {
                echo '
                <br />
                <table width="'. TABLE_WIDTH. '" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="30%">
                            <hr />
                        </td>
                        <td width="40%" align="center">
                            関連記事
                        </td>
                        <td width="30%">
                            <hr />
                        </td>
                    </tr>
                </table>
                <br />
                <table width="'. TABLE_WIDTH. '" align="center" cellpadding="3" cellspacing="0">
                    ';

                    $res_width = TABLE_WIDTH - 50;

                    //表示
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        //変数へ代入
                        $num = $row[BBS_CNUMBER];
                        $renum = $row[BBS_CRENUM];
                        $name = $row[BBS_CNAME];
                        $mail = $row[BBS_CMAIL];
                        $home = $row[BBS_CHOME];
                        $subject = $row[BBS_CTITLE];
                        $comment = $row[BBS_CCOMMENT];
                        $date = $row[BBS_CDATE];

                        $name = and_decode($name);
                        $subject = and_decode($subject);
                        $comment = and_decode($comment);

                        $comment = autolink($comment);

                        if ($renum == 0) {
                            echo '
                            <tr>
                                <td colspan="2">
                                    <table width="100%" align="center" cellpadding="3" cellspacing="0" class="thread">
                                        <tr>
                                            <td class="thread_title">
                                                '. $subject. '
                                            </td>
                                            <td class="thread_main_res">
                                                &nbsp;
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="thread_comment">
                                                '. $comment. '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="thread_bottom">
                                                <span class="thread_name">'. $name. '</span>
                                                ';
                                                if ($mail == "") {
                                                    echo ' [M] ';
                                                } else {
                                                    $m = entity('mailto:'. $mail);
                                                    echo ' [<a href="'. $m. '">M</a>] ';
                                                }

                                                //旧バージョン対応
                                                if ($home != "" && !preg_match("/https?:\/\//", $home)) {
                                                    $home = 'http://'. $home;
                                                }
                                                if ($home == "" || $home == "http://") {
                                                    echo '[H]';
                                                } else {
                                                    echo '[<a href="' . $home . '" target="_blank">H</a>]';
                                                }
                                                echo '<br />
                                                <span class="thread_date">'. $date. '</span> [<span class="thread_no">'. $num. '</span>]
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            ';
                        } else {

                            echo '
                            <tr>
                                <td width="50">
                                    &nbsp;
                                </td>
                                <td>
                                    <table width="'. $res_width. '" align="right" cellpadding="3" cellspacing="0" class="thread_res">
                                        <tr>
                                            <td class="thread_res_title">
                                                '. $subject. '
                                            </td>
                                            <td class="thread_res_res">
                                                &nbsp;
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="thread_res_comment">
                                                '. $comment. '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="thread_res_bottom">
                                                <span class="thread_name">'. $name. '</span>
                                                ';
                                                if ($mail == "") {
                                                    echo ' [M] ';
                                                } else {
                                                    $m = entity('mailto:'. $mail);
                                                    echo ' [<a href="'. $m. '">M</a>] ';
                                                }

                                                //旧バージョン対応
                                                if ($home != "" && !preg_match("/https?:\/\//", $home)) {
                                                    $home = 'http://'. $home;
                                                }
                                                if ($home == "" || $home == "http://") {
                                                    echo '[H]';
                                                } else {
                                                    echo '[<a href="' . $home . '" target="_blank">H</a>]';
                                                }
                                                echo '<br />
                                                <span class="thread_res_date">'. $date. '</span> [<span class="thread_res_no">'. $num. '</span>]
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            ';
                        }
                    }
                    echo '
                </table>
                <br />
                ';
            }
        } catch (PDOException $e) {
            error($e->getMessage());
        }

        $pdo = null;

        foot();
        exit;
    } elseif ($type == "edit") {
         foot();
    }
} 
#------------------------------------
#  記事修正・削除
#------------------------------------
function edit($type)
{
    //$type = 0 edit
    //$type = 1 del

    global $post;

    $num = $post['eno'];
    $pw = $post['epw'];
    $er = 0;

    if ($num == "" || strlen($num) > 32) {
        $er = 1;
    } elseif ($pw == "" || strlen($pw) > MAXPASS) {
        $er = 1;
    } elseif (!preg_match("/^([\d]+)?$/", $num)) {
        $er = 1;
    } elseif (!preg_match("/[-_.!~*()a-zA-Z0-9;\/?:&@=+$,%#]+/", $pw)) {
        $er = 1;
    }

    if (!$er) {
        $ret = article_select(0, $num, $pw);

        if ($ret != 0) {

            if ($type) {
                reg("del", $num);
            } else {
                $name = decode($ret[BBS_CNAME]);
                $mail = decode($ret[BBS_CMAIL]);
                $home = decode($ret[BBS_CHOME]);
                $title = decode($ret[BBS_CTITLE]);
                $com = br_delete($ret[BBS_CCOMMENT]);
 
                form("edit", $num, $name, $mail, $home, $title, $com, $pw);
                exit;
            }
        }
    }

    error("データが存在しないか、パスワードが違います。");

    exit;

}
#------------------------------------
#  レス
#------------------------------------
function res()
{
    global $post;

    $num = $post['reno'];
    $er = 0;

    if ($num == "" || strlen($num) > 32) {
        $er = 1;
    } elseif (!preg_match("/^([\d]+)?$/", $num)) {
        $er = 1;
    }

    if (!$er) {
        $ret = article_select(0, $num, PASSWORD);

        if ($ret != 0) {

            $title = decode($ret[BBS_CTITLE]);
            form("res", $num, "", "", "", $title);
            exit;
        }
    }

    error("データが存在しないか、パスワードが違います。");

    exit;
}
#------------------------------------
#  使い方表示
#------------------------------------
function howto()
{

    head();

    echo '<br />';

    title();

    echo '
    <table width="'. TABLE_WIDTH . '" align="center" cellpadding="3" cellspacing="0" class="howto">
        <tr>
            <td align="center" class="howto_title">
                掲示板の使用方法
            </td>
        </tr>
        <tr>
            <td>
                <ol>
                    <li>
                        パスワードを指定した場合、フォームより修正・削除が可能です。
                    </li>
                    <li>
                        タグは使用できません。
                    </li>
                    <li>
                        コメントに使用できる文字数は<span class="bold">'. MAXCOMMENT. '文字</span>までです。
                    </li>
                    <li>
                        コメントに投稿できるURL数は<span class="bold">'. URLCOUNT. 'つ</span>までです。
                    </li>
                    ';
                    if (SPAM) {
                        echo '
                        <li>
                            英数字漢字のみのコメントは投稿することができません。
                        </li>
                        ';
                    }
                    if (MAXDATA > 0) {
                        echo '
                        <li>
                            最大記事保存件数は'. MAXDATA. '件です。
                        </li>
                        ';
                    }
                    echo '
                </ol>
            </td>
        </tr>
        <tr>
            <td align="center">
                <a href="'. SCRIPT. '">掲示板へ戻る</a>
            </td>
        </tr>
    </table>
    <br />
    ';

    foot();

    exit;
}
?>
