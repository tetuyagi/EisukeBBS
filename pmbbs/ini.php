<?php
//---------------------------------
//  PM bbs conf_file (2013/02/28)  
//     Copyright Rocomotion 2005   
//     http://www.rocomotion.jp    
//---------------------------------

// -- 設定開始 -- //

//DB設定
//データベースサーバ名
define('DB_SERVER_NAME', 'mysql1.php.xdomain.ne.jp');

//データベース名
define('DB_NAME', 'tyagiserver_bbs');

//データベースアクセスユーザ名
define('DB_USER', 'tyagiserver_root');

//データベースアクセスパスワード
define('DB_PW', 'password');

//スクリプト設定
//パスワード
define('PASSWORD', '0123');

//タイトル
define('TITLE', 'PHP &amp; MySQL BBS');

//タイトルに画像を使う場合はhttp://～指定
define('TGIF', '');

//タイトル画像の横幅(単位px)
define('TGIFW', 300);

//タイトル画像の縦幅(単位px)
define('TGIFH', 150);

//戻り先
define('HOMEPAGE', 'http://localhost');

//戻り先への戻り方
define('TARGET', '_blank');

//掲示板のログ表示部分の横幅
define('TABLE_WIDTH', 500);

//1ページ親記事表示数
define('MAXVIEW', 10);

//管理ページ内1ページログ表示件数
define('ADMIN_MAXVIEW', 20);

//DB最大保存件数(0で全て保存(未削除))
define('MAXDATA', 0);

//レスを許可する。(0:no',1:yes)
define('RESFLG', 1);

//ver 1.01 追加
//書込制限をかけるIPまたはホスト
//ワイルドカード[*]が使えます。
//'',の形でいくらでも増やせます。
$deny = array(
    '*.localhost.com',
    '',
    '',
    '',
    '',
    '');

//ver 1.02追加
//英字のみのコメントをスパムとする。(0:no,1:yes)
define('SPAM', 0);

//コメントにこの文字が入っていたらスパムとする。
//'',の形でいくらでも増やせます。
$ng_word = array(
    'カジノ',
    'アダルト',
    '',
    '',
    '');

//ver1.05追加
//コメントに許可するURL数
define('URLCOUNT', 3);

//文字数制限
define('MAXNAME', 30);       //名前
define('MAXCOMMENT', 3000);  //コメント
define('MAXSUBJECT', 50);    //題名
define('MAXMAIL', 100);      //メールアドレス
define('MAXURL', 100);       //URL
define('MAXPASS', 12);       //パスワード

//ver1.06追加
//セキュリティ対策。下記URL以外からの投稿を弾く。当スクリプトのURLをhttp://から
define('SCRIPTURL', 'http://tyagiserver.php.xdomain.jp/pmbbs/');

//  --設定終了-- //

//スクリプト
define('SCRIPT', './index.php');
define('ADMIN_SCRIPT', './admin.php');
define('CSS', './index.css');

//DBにて使う値
//テーブル名
define('COUNT_TABLE', 'rocomotion_counter');
define('COUNT_NAME', 'pm_bbs_counter');
define('BBS_TABLE', 'pm_bbs');

//カウンタDBテーブル・カラム名
define('COUNT_CNAME', 'counter_name');
define('COUNT_CCOUNT', 'count');
define('COUNT_CLASTIP', 'lastip');

//表示側DBテーブル・カラム名
define('BBS_CNUMBER', 'pm_bbs_num');
define('BBS_CRENUM', 'pm_bbs_renum');
define('BBS_CNAME', 'pm_bbs_name');
define('BBS_CMAIL', 'pm_bbs_mail');
define('BBS_CHOME', 'pm_bbs_home');
define('BBS_CTITLE', 'pm_bbs_title');
define('BBS_CCOMMENT', 'pm_bbs_comment');
define('BBS_CPASS', 'pm_bbs_pass');
define('BBS_CDATE', 'pm_bbs_date');
define('BBS_CRESUPDATE', 'pm_bbs_resupdate');
define('BBS_CIP', 'pm_bbs_ip');
define('BBS_CHOST', 'pm_bbs_host');
define('BBS_CUA', 'pm_bbs_ua');
define('BBS_CDEL', 'pm_bbs_del');
?>
