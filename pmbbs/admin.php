<?php
/*****************************************
PM bbs admin file (2013/02/28)
copyright Rocomotion 2005
http://www.rocomotion.jp
 *****************************************/

@ini_set('mbstring.detect_order', 'auto');
@ini_set('mbstring.http_input', 'pass');
@ini_set('mbstring.http_output', 'pass');
@ini_set('mbstring.internal_encoding', 'neutral');
@ini_set('mbstring.substitute_character', 'none');

@mb_language('Japanese');
@mb_internal_encoding('utf-8');

require_once('./ini.php');
require_once('./func.php');

//セッション開始
session_start();

$post = sanitize($_POST);
$get = sanitize($_GET);
if ($post['mode'] == "adminreg") { adminreg(); }
elseif ($post['mode'] == "revival") { revival(); }
elseif ($post['mode'] == "cmpdel") { complete_delete(); }
elseif ($post['mode'] == "logon") { logon(); }
elseif ($get['mode'] == "logout") { logout(); }
admin();

#------------------------------------
#  管理者TOPメニュー
#------------------------------------
function admin()
{

    if (!isset($_SESSION['spw'])) {

        session_regenerate_id();

        //ヘッダ読み込み
        head();

        echo '
        <table summary="admintop" width="180" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a href="javascript:window.close();">閉じる</a>
                </td>
            </tr>
        </table>
        <div align="center">
            ログイン
        </div>
        <form action="'.ADMIN_SCRIPT.'" method="post">
            <input type="hidden" name="mode" value="logon" />
            <table summary="admintop" width="180" align="center" cellpadding="3" cellspacing="0">
                <tr>
                    <td>
                        パスワード
                    </td>
                    <td>
                        <input type="password" name="adpw" size="8" />
                        <input type="submit" value="送信" />
                    </td>
                </tr>
            </table>
        </form>
        ';

        foot();

    } else {

        adminmenu();

    }

    exit;
}
#------------------------------------
#  管理者メニュー
#------------------------------------
function adminmenu()
{
    global $get;

    session_regenerate_id();

    head();

    echo '
    <table width="90%" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <a href="javascript:window.close()">閉じる</a>
            </td>
            <td align="right">
                <a href="'. ADMIN_SCRIPT. '?mode=logout">ログアウト</a>
            </td>
        </tr>
    </table>
    <div align="center">
        管理者メニュー
        <br />
        復活・完全削除フォーム
        <br />
        <br />
        <span class="att">※ 親記事を完全削除すると子記事も完全削除されます。</span>
    </div>
    <br />
    <table width="90%" align="center" cellpadding="3" cellspacing="0">
        <tr>
            <td>
                <span class="del">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </span>
                &nbsp; は削除された記事です。&nbsp;&nbsp;Noの()内は親記事番号です。
            </td>
        </tr>
    </table>
    <table width="90%" align="center" cellpadding="3" cellspacing="0" class="admin_table">
        <tr>
            <td width="5%" class="admin_table_column">
                No
            </td>
            <td width="10%" class="admin_table_column">
                投稿者
            </td>
            <td width="7%" class="admin_table_column">
                M/H
            </td>
            <td width="13%" class="admin_table_column">
                題名
            </td>
            <td class="admin_table_column">
                内容
            </td>
            <td width="15%" class="admin_table_column">
                投稿日
            </td>
            <td width="10%" class="admin_table_column">
                IP/HOST
            </td>
            <td width="6%" class="admin_table_column">
                復活
            </td>
            <td width="6%" class="admin_table_column">
                完全削除
            </td>
        </tr>
        ';

    $pg = $get['page'] ? $get['page'] : 1;
    $stt = $get['st'];

    $start = $pg * ADMIN_MAXVIEW - ADMIN_MAXVIEW;
    $increment = ADMIN_MAXVIEW;

    try {
        $pdo = db_connect();

        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                ". BBS_CNUMBER. ",
                ". BBS_CNAME. ",
                ". BBS_CMAIL. ",
                ". BBS_CHOME. ",
                ". BBS_CTITLE. ",
                ". BBS_CCOMMENT. ",
                ". BBS_CDATE. ",
                ". BBS_CIP. ",
                ". BBS_CHOST. ",
                ". BBS_CDEL."
            FROM
                ". BBS_TABLE. "
            WHERE
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
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute();

        list($all_rows) = $stmt2->fetch(PDO::FETCH_NUM);

        if ($rows > 0) {

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

                $ip = $row[BBS_CIP];
                $host = $row[BBS_CHOST];
                $del = $row[BBS_CDEL];

                $name = and_decode($name);
                $subject = and_decode($subject);
                $comment = and_decode($comment);
                $comment = autolink($comment);

                $comment = str_replace("<br />","",$comment);
                if (Klength($comment) > 100) {
                    $comment = substr($comment, 0, 100);
                    $comment .= "...";
                }
                if (strlen($subject) > 16) {
                    $subject = substr($subject, 0, 16);
                    $subject .= "...";
                }

                if ($del) {
                    $class = ' class="del"';
                } else {
                    $class = '';
                }

                echo '
                <tr>
                    <td align="center"'. $class. '>
                        '. $num. '
                    </td>
                    <td'. $class. '>
                        '. $name. '
                    </td>
                    <td'. $class. '>
                        ';
                if ($mail == "") {
                    echo '
                            [M]
                            ';
                } else {
                    echo '
                            [<a href="mailto:'. $mail. '">M</a>]
                            ';
                }
                echo '/';

                //旧バージョン対応
                if ($home != "" && !preg_match("/https?:\/\//", $home)) {
                    $home = 'http://'. $home;
                }

                if ($home == "" || $home == "http://") {
                    echo '[H]';
                } else {
                    echo '[<a href="'. $home. '" target="_blank">H</a>]';
                }
                echo '
                    </td>
                    <td'. $class. '>
                        '. $subject. '
                    </td>
                    <td'. $class. '>
                        '. $comment. '
                    </td>
                    <td style="font-size:80%;"'. $class. '>
                        '. $date. '
                    </td>
                    <td style="font-size:80%;"'. $class. '>
                        '. $ip. '<br />'. $host. '
                    </td>
                    <td align="center"'. $class. '>
                        ';
                if ($del) {
                    echo '
                            <form action="'. ADMIN_SCRIPT. '" method="post">
                                <input type="hidden" name="mode" value="revival" />
                                <input type="hidden" name="rvnum" value="'. $num. '" />
                                <input type="submit" value="復活" />
                            </form>
                            ';
                } else {
                    echo '
                            &nbsp;
                            ';
                }
                echo '
                    </td>
                    <td align="center"'. $class. '>
                        <form action="'. ADMIN_SCRIPT. '" method="post">
                            <input type="hidden" name="mode" value="cmpdel" />
                            <input type="hidden" name="cdnum" value="'. $num. '" />
                            <input type="submit" value="完削" />
                        </form>
                    </td>
                </tr>
                ';

                //レス記事があれば表示
                $sql = "
                    SELECT
                        ". BBS_CNUMBER. ",
                        ". BBS_CRENUM. ",
                        ". BBS_CNAME. ",
                        ". BBS_CMAIL. ",
                        ". BBS_CHOME. ",
                        ". BBS_CTITLE. ",
                        ". BBS_CCOMMENT. ",
                        ". BBS_CDATE. ",
                        ". BBS_CDEL. "
                    FROM
                        ". BBS_TABLE. "
                    WHERE
                        ". BBS_CRENUM. " = :no
                    ORDER BY
                        ". BBS_CNUMBER. " ASC";
                $stmt2 = $pdo->prepare($sql);
                $stmt2->bindValue(':no', $num, PDO::PARAM_INT);
                $stmt2->execute();
                $res_rows = $stmt2->rowCount();

                if ($res_rows != 0) {
                    while($row_res = $stmt2->fetch(PDO::FETCH_ASSOC)) {

                        $num = $row_res[BBS_CNUMBER];
                        $renum = $row_res[BBS_CRENUM];
                        $name = $row_res[BBS_CNAME];
                        $mail = $row_res[BBS_CMAIL];
                        $home = $row_res[BBS_CHOME];
                        $subject = $row_res[BBS_CTITLE];
                        $comment = $row_res[BBS_CCOMMENT];
                        $date = $row_res[BBS_CDATE];

                        $ip = $row[BBS_CIP];
                        $host = $row[BBS_CHOST];
                        $del = $row_res[BBS_CDEL];

                        $name = and_decode($name);
                        $subject = and_decode($subject);
                        $comment = and_decode($comment);
                        $comment = autolink($comment);

                        if($del) {
                            $class = ' class="del"';
                        } else {
                            $class = '';
                        }

                        echo '
                        <tr>
                            <td align="center" '. $class. '>
                                '. $num. '('. $renum. ')
                            </td>
                            <td'. $class. '>
                                '. $name. '
                            </td>
                            <td'. $class. '>
                                ';
                        if ($mail == "") {
                            echo '
                                    [M]
                                    ';
                        } else {
                            echo '
                                    [<a href="mailto:'. $mail. '">M</a>]
                                    ';
                        }
                        echo '/';

                        //旧バージョン対応
                        if ($home != "" && !preg_match("/https?:\/\//", $home)) {
                            $home = 'http://'. $home;
                        }

                        if ($home != "" && $home != "http://") {
                            echo '[<a href="'. $home. '" target="_blank">H</a>]';
                        } else {
                            echo '[H]';
                        }
                        echo '
                            </td>
                            <td'. $class. '>
                                '. $subject. '
                            </td>
                            <td'. $class. '>
                                '. $comment. '
                            </td>
                            <td style="font-size:80%;"'. $class. '>
                                '. $date. '
                            </td>
                            <td style="font-size:80%;"'. $class. '>
                                '. $ip. '<br />'. $host. '
                            </td>
                            <td align="center"'. $class. '>
                                ';
                        if($del) {
                            echo '
                                    <form action="'. ADMIN_SCRIPT. '" method="post">
                                        <input type="hidden" name="mode" value="revival" />
                                        <input type="hidden" name="rvnum" value="'. $num. '" />
                                        <input type="submit" value="復活" />
                                    </form>
                                    ';
                        } else {
                            echo '
                                    &nbsp;
                                    ';
                        }
                        echo '
                            </td>
                            <td align="center"'. $class. '>
                                <form action="'. ADMIN_SCRIPT. '" method="post">
                                    <input type="hidden" name="mode" value="cmpdel" />
                                    <input type="hidden" name="cdnum" value="'. $num. '" />
                                    <input type="submit" value="完削" />
                                </form>
                            </td>
                        </tr>
                        ';
                    }
                }
            }

        } else {
            echo '
            <tr>
                <td colspan="9">
                    データがありません。
                </td>
            </tr>
            ';
        }
        echo '
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

        echo '
    <table width="90%" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <hr />
            </td>
        </tr>
    </table>
    ';

        //ページング
        if ($p_flag) {
            $x=1;

            if ($stt == "") {
                $stt = 1;
            } else {
                $all_rows = $all_rows - ($stt-1) * ADMIN_MAXVIEW;
            }
            echo '
        <table width="90%" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td align="right">
                    Page :
                    ';
            $pg = $pg ? $pg : 1;

            if ($stt - 20 > 0) {
                $morebackpg = $stt - 1;
                $st2 = $stt - 20;
                echo '<a href="'. ADMIN_SCRIPT. '?page='. $morebackpg. '&amp;st='. $st2. '">back &#171; </a> / ';
            } else {
                $st2 = 1;
            }

            $x = $stt;

            while ($all_rows > 0) {
                if ($pg == $x ) {
                    echo '<span style="font-weight:bold;">'.$x.'</span>';
                } else {
                    echo '<a href="'.ADMIN_SCRIPT.'?page='.$x.'&amp;st='.$stt.'">'.$x.'</a>';
                }
                $x++;
                $all_rows = $all_rows - ADMIN_MAXVIEW;
                if ($all_rows > 0) {
                    echo ' / ';
                }
                if($x > $stt+19) {
                    break;
                }
            }

            if ($all_rows > 0) {
                $morenextpg = $x;
                echo '<a href="'. ADMIN_SCRIPT. '?page='. $morenextpg. '&amp;st='. $x. '"> &#187; next</a>';
            }
        }
    } catch (PDOException $e) {
        error($e->getMessage());
    }
    echo '
                </td>
            </tr>
        </table>
        ';

    $pdo = null;

    foot();

    exit;

}
#------------------------------------
#  復活処理
#------------------------------------
function revival()
{
    global $post;

    $num = $post['rvnum'];

    if ($num == "" || strlen($num) > 32) {
        $er = 1;
    } elseif (!preg_match("/^([\d]+)?$/", $num)) {
        $er = 1;
    }

    if (!$er) {
        $ret = article_select(1, $num, $_SESSION['spw']);

        if ($ret != 0) {

            reg("rev", $num, "");
            exit;

        }
    }

    error("データが存在しないか、パスワードが違います。");
    exit;

}
#------------------------------------
#  完全削除処理
#------------------------------------
function complete_delete()
{
    global $post;

    $num = $post['cdnum'];

    if ($num == "" || strlen($num) > 32) {
        $er = 1;
    } elseif (!preg_match("/^([\d]+)?$/", $num)) {
        $er = 1;
    }

    if (!$er) {
        $ret = article_select(1, $num, $_SESSION['spw']);

        if ($ret != 0) {

            reg("cdel", $num);
            exit;
        }
    }
    error("データが存在しないか、パスワードが違います。");
    exit;
}
?>
