<?php
// this src is written under the terms of the GPL-licence, see gpl.txt for futher details

function error_msg(){
$result = "";
for ($i = 0;$i < func_num_args();$i++) {
      $result .= func_get_arg($i) . " ";
    }
die($result);
}

// Starting session

//******************************************************************************************************
function sstart()
{
	global $settings;

	session_set_cookie_params(($settings['lifetime']),$settings['cookiedir']);
	session_name("begaming_website_session");

	session_start();


	// If user-session found --> load user-vars
	//*********************************************************************************************
	if (isset($_SESSION["userID"]))
	loaduser($_SESSION["userID"]);

}
// MySQL connection
//******************************************************************************************************
function db_connect()
{
	global $db, $settings;
	$db = mysql_connect($settings['sql_url'], $settings['sql_username'], $settings['sql_password']);
	if (!$db) error_msg("Cannot connect to database server",$db);
	$result=mysql_select_db($settings['sql_db']);
	if (!$result) error_msg("Cannot connect to database mongo",$db);
}



// Logging in user
//**************************************************************************************************


function login($usr, $pwd)
{
	$username=mysql_real_escape_string(stripslashes($usr));
	$password=mysql_real_escape_string(scramble($pwd));

	$query = mysql_query("
	SELECT
	ID, nickname, password
	FROM
	userdb
	WHERE
	nickname = '$username' AND password = '$password' AND active = 1
	");

	$result = mysql_fetch_row($query);

	if (mysql_num_rows($query) != 0)
	{
		$_SESSION["userID"]=(int)$result[0];
		header("Location: login.php");
	}
	else
	header("Location: login.php?failed=1");

}
// Setting user-variables
//******************************************************************************************************
function loaduser($userID)
{
	unset($GLOBALS['user']);
	global $user;


	$query = mysql_query("
	SELECT
	ID,grpID,name,nickname,email
	FROM
	userdb
	WHERE
	ID = $userID
	");

	$result = mysql_fetch_row($query);
	if (mysql_num_rows($query)==1)
	{
		$user['ID']			=$result[0];
		$user['groupID']		=$result[1];
		$user['name']			=$result[2];
		$user['nickname']		=$result[3];

		$user['mail']			=$result[4];


		$query = mysql_query("SELECT * FROM usergrp WHERE ID=$result[1]");
		if (mysql_num_rows($query)==1)
		{
			$user['priv']=mysql_fetch_assoc($query);

		}
		else
		unset($GLOBALS['user']);
	}


}
// Debugging function
//******************************************************************************************************
function dbg_o()
{
	global $user;
	ob_start();
	echo "\nUSER: ";
	print_r($user);
	echo "\nGET: ";
	print_r($_GET);
	echo "\nPOST: ";
	print_r($_POST);
	echo "\nCOOKIE: ";
	print_r($_COOKIE);
	echo "\nSESSION: ";
	print_r($_SESSION);
	echo "\n";

	$temp=nl2br(htmlspecialchars(ob_get_contents()));
	ob_end_clean();
	echo $temp;
}
// Checking if $username already exists in database
//******************************************************************************************************
function check_dublicate_username($username)
{
	$name = mysql_real_escape_string($username);
	$query = mysql_query("SELECT COUNT(ID) FROM userdb WHERE userdb.nickname = '$name'");
	$result = mysql_fetch_row($query);

	if ($result[0] == 1)
	return 1;
	else
	return 0;
}
// Get username from $userID
//******************************************************************************************************
function get_name_from_id($userID)
{
	$query = mysql_query("SELECT nickname FROM userdb WHERE userdb.ID = $userID");
	$result = mysql_fetch_row($query);
	return $result[0];

}
function check_if_owner($newsID,$userID=NULL)
{
	$newsID = mysql_real_escape_string(stripslashes($newsID));
	if ($newsID != NULL)
	{
		$userID = mysql_real_escape_string(stripslashes($userID));
		$query = mysql_query("SELECT COUNT(*) FROM news WHERE news.ID = $newsID AND news.ownerID = $userID");
		$result = mysql_fetch_row($query);
		return $result[0];
	}
	else
	return 0;
}
function get_version_num($version = 0)
{

	$query = mysql_query("SELECT version FROM download WHERE download.catID=1 ORDER BY version DESC");

	if ($version == 0)
	{
		echo '
		<select name="version">';
		while ($result = mysql_fetch_row($query))
		echo '<option value="'.$result[0].'">DOSBox '.$result[0].'</option>';
		echo '</select>';
	}
	else
	{
		echo '<select name="version">';

		while ($result = mysql_fetch_row($query))
		{
			echo '<option value="'.$result[0].'"';

			if($version == $result[0])
			echo ' selected';

			echo '>DOSBox '.$result[0].'</option>';
		}

		echo '</select>';
	}

}
function check_game_owner($gameID, $userID)
{
	$gameID = mysql_real_escape_string(stripslashes($gameID));
	$userID = mysql_real_escape_string(stripslashes($_SESSION['userID']));

	$query = mysql_query("SELECT COUNT(*) FROM list_game WHERE list_game.ID=$gameID AND list_game.ownerID=$userID");

	if(!$query) return 0;

	$result = mysql_fetch_row($query);

	return $result[0];

}
function main_news($priv)
{

	$query = mysql_query("

	SELECT
	news.text, DATE_FORMAT(news.added, '%W, %M %D, %Y'), userdb.nickname, news.ownerID, news.ID
	FROM
	news, userdb
	WHERE
	userdb.ID=news.ownerID
	ORDER BY
	news.added DESC
	LIMIT 5  
	");

	while ($result = mysql_fetch_row($query))
	{
		$text = ereg_replace ("\n", "<br>", $result[0]);
		$text = parse_http($text);

		echo '<table class="table630" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787"><tr><td>
		<table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';

		if ($priv==1 || (isset($_SESSION['userID']) && $result[3]==$_SESSION['userID']))
		echo '<p><b>'.$result[1].'</b> - '.$result[2].'&nbsp;&nbsp;<a href="news.php?change_news=1&amp;newsID='.$result[4].'"><img src="site_images/change_icon.gif" border="0"></a>&nbsp;<a href="news.php?removing_news=1&newsID='.$result[4].'"><img src="site_images/delete_icon.gif" border="0"></a></p>';
		else
		echo '<b>'.$result[1].'</b> - '.$result[2].' ';

		echo '</td></tr></table></td>
		</tr></table></td></tr></table><table class="table630" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#113466"><tr>
		<td><table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';





		echo $text;

		echo '</td></tr></table></td></tr></table></td></tr></table><br>';
	}


}
// Check if $email is a valid email adress
//******************************************************************************************************
function verify_mail($email)
{
	if(eregi("^[_a-z0-9-]+(.[_a-z0-9-]+)*@[_a-z0-9-]+(.[_a-z0-9-]+)*(.([a-z]{2,3}))+$",$email))
	return 1;
	else
	return 0;
}
// Check if $urladdr is a valid www url
//******************************************************************************************************
function verifyurl( $urladdr )
{
	$regexp = "^(https?://)?(([0-9a-z_!~*'().&=+$%-]+:)?[0-9a-z_!~*'().&=+$%-]+@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z_!~*'()-]+\.)*([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.[a-z]{2,3})(:[0-9]{1,4})?((/?)|(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$";

	if (eregi( $regexp, $urladdr ))
	{
		if (!eregi( "^https?://", $urladdr ))
		$urladdr = "http://" . $urladdr;

		if (!eregi( "^https?://.+/", $urladdr ))
		$urladdr .= "/";

		if ((eregi( "/[0-9a-z~_-]+$", $urladdr)) && (!eregi( "[\?;&=+\$,#]", $urladdr))) $urladdr .= "/";
		return ($urladdr);
	}
	else
	return false;

}
// Check if the $email allready exists in database
//******************************************************************************************************
function check_mail_db($email)
{
	$mail 	= mysql_real_escape_string(stripslashes($email));
	$query 	= mysql_query("SELECT COUNT(*) FROM userdb WHERE userdb.email = '$mail'");
	$result = mysql_fetch_row($query);

	return $result[0];
}
// Convert mail/http/https/ftp urls to <a href> tags.
//******************************************************************************************************
function parse_http($str, $target = '_blank')
{
//	$str = preg_replace ("/(https?:\/\/|ftp:\/\/|mailto:)([^>\s\"\']+)/i", "<a href='\\0' target='$target'>\\2</a>", $str);
// add target=blank if url starts with http:// or friends
	$str = preg_replace ("/<a href=(\"?https?:\/\/|\"?ftp:\/\/|\"?mailto:)([^>]+)>/i", "<a href=\\1\\2 target='$target'>", $str);
	return $str;
}
// Displays all the usergroups in a <select> field!
//******************************************************************************************************
function get_usergroups($selected=NULL)
{
	$query=mysql_query("SELECT usergrp.ID,usergrp.groupname FROM usergrp ORDER BY usergrp.ID");
	echo '<select name="usergroup">';
	while ($result=mysql_fetch_row($query))
	{
		echo '<option value="'.$result[0].'"';

		if ($selected == $result[0])
		echo ' selected';

		echo '>'.$result[1].'</option>';
	}
	echo '</select>';
}
function status_change($changeID)
{
	$catID = mysql_real_escape_string(stripslashes($changeID));

	$query = mysql_query("

	SELECT
	status_items.name, status_items.percent, status_items.note, status_items.ID
	FROM
	status_items
	WHERE
	status_items.catID=$catID

	");
	echo '<table cellspacing="0" cellpadding="0">
	<tr valign="top" align="left">
	<td width="180">
	</td>

	<td width="5">
	&nbsp;
	</td>

	<td width="60">Status:
	</td>
	<td width="5">
	&nbsp;
	</td>
	<td width="100">Note:
	</td>
	<td>
	&nbsp;
	</td>
	<td>
	</td>
	</tr>';

	while ($result = mysql_fetch_row($query))
	{


		echo '<form action="status.php?changing=1&catID='.$changeID.'" method="POST" name="changing_status"><input type="hidden" name="updateID" value="'.$result[3].'"><tr><td>
		<input type="text" value="'.$result[0].'" name="name" maxlength="40" size="25">
		</td>

		<td>
		&nbsp;
		</td>

		<td>
		<input type="text" value="'.$result[1].'" name="status" maxlength="3" size="5">
		</td>
		<td>
		&nbsp;
		</td>

		<td>
		<input type="text" value="'.$result[2].'" name="note" maxlength="150" size="70">
		</td>
		<td>
		&nbsp;&nbsp;<input type="submit" name="submit" value="Update">
		</td>
		<td>
		&nbsp;&nbsp;<a href="status.php?removeID='.$result[3].'&catID='.$changeID.'"><img src="site_images/delete_icon.gif" border="0"></a>
		</td>
		</tr></form>
		';
	}

	echo '</table>';
}

function show_status_db($priv)
{
	$query = mysql_query("SELECT ID, name FROM status_cat");

	while($result=mysql_fetch_row($query))
	{
		if ($priv==1)
		$text = $result[1].'&nbsp;<a href="status.php?changeID='.intval($result[0]).'" target="_top"><img src="site_images/change_icon.gif" border="0" alt="Change these item(s)"></a>';
		else
		$text = $result[1];


		template_pagebox_start($text, 890);
		echo '
		<table cellspacing="0" cellpadding="0" width="100%">';

		$item_query = mysql_query("SELECT name,percent,note FROM status_items WHERE catID=".intval($result[0]));
		while($item_result=mysql_fetch_row($item_query))
		{
			echo '
			<tr valign="top" align="left">
			<td width="125">
			'.$item_result[0].'
						</td>

			<td width="43">
			'.$item_result[1].'%
						</td>

			<td width="72" valign="middle">';
			if ($item_result[1] !=0 )
			echo '<img src="site_images/progress.gif" border="1" width="'.$item_result[1].'%" alt="'.$item_result[1].'% implemented" height="8">';
			else
			echo '&nbsp';
			echo '</td>

			<td width="10">
			&nbsp;
			</td>

			<td valign="middle">
			'.$item_result[2].'
						</td>
			</tr>';
		}
		echo '</table>';
		template_pagebox_end();
	}


}
function download_change($changeID)
{
	$catID = mysql_real_escape_string(stripslashes($changeID));

	$query = mysql_query("

	SELECT
	name, url, description, version, changelog, ID
	FROM
	download
	WHERE
	download.catID=$catID

	");
	echo '<table cellspacing="0" cellpadding="0">
	<tr valign="top" align="left">
	<td width="180">
	Name:
	</td>

	<td width="5">
	&nbsp;
	</td>

	<td width="60">Version:
	</td>
	<td width="5">
	&nbsp;
	</td>
	<td width="60">Description:
	</td>
	<td width="5">
	&nbsp;
	</td>
	<td width="100">URL:
	</td>
	<td>
	&nbsp;
	</td>
	<td>
	</td>
	</tr>';

	while ($result = mysql_fetch_row($query))
	{


		echo '<form action="download.php?changing=1&catID='.intval($changeID).'" method="POST" name="changing_status"><input type="hidden" name="updateID" value="'.$result[5].'"><tr><td>
		<input type="text" value="'.$result[0].'" name="name" maxlength="40" size="25">
		</td>

		<td>
		&nbsp;
		</td>

		<td>
		<input type="text" value="'.$result[3].'" name="version" maxlength="20" size="5">
		</td>
		<td>
		&nbsp;
		</td>
		<td>
		<input type="text" value="'.$result[2].'" name="description" maxlength="50" size="25">
		</td>
		<td>
		&nbsp;
		</td>
		<td>
		<input type="text" value="'.$result[1].'" name="url" maxlength="150" size="40">
		</td>
		<td>
		&nbsp;&nbsp;<input type="submit" name="submit" value="Update">
		</td>
		<td>
		&nbsp;&nbsp;<a href="download.php?removeID='.$result[5].'&catID='.$changeID.'"><img src="site_images/delete_icon.gif" border="0"></a>
		</td>
		</tr></form>
		';
	}

	echo '</table>';
}
function show_downloads($priv)
{
	$cat_query = mysql_query("SELECT ID, name FROM download_cat");


	while($cat_result=mysql_fetch_row($cat_query))
	{

		if ($priv==1)
		$cat=$cat_result[1].'&nbsp;<a href="download.php?changeID='.intval($cat_result[0]).'" target="_top"><img src="site_images/change_icon.gif" border="0" alt="Change these item(s)"></a>';
		else
		$cat=$cat_result[1];

		;

		template_pagebox_start($cat, 690);



		$query = mysql_query("SELECT ID, name, url, description, version, DATE_FORMAT(added, '%W, %M %D, %Y'), changelog FROM download WHERE catID=".intval($cat_result[0])." ORDER BY version DESC");
		if (mysql_num_rows($query) != 0)
		{
			echo '<table cellspacing="0" cellpadding="0" width="100%">';
			while($result=mysql_fetch_row($query))
			{
				echo '
				<tr valign="top" align="left">
				<td width="210">
				<a href="'.$result[2].'" target="_blank">'.$result[1].'</a>
				</td>

				<td width="110">
				'.$result[4].'
				</td>
				<td>
				'.$result[3].'
				</td>' ;
//				<td width="226">
//				'.$result[5].'
//				</td>
	echo			'</tr>';
			}
			echo '</table>';
		}
		else
		echo '<i>No items available for download in this category</i>';
		template_pagebox_end();

	}



}
function get_latest_version()
{
	$query = mysql_query("SELECT version FROM versions ORDER BY version DESC LIMIT 1");
	$result = mysql_fetch_row($query);

	return $result[0];
}
function get_versions()
{
	$query = mysql_query("SELECT ID, version FROM versions ORDER BY version DESC");
	echo '<select name="version">';
	while ($result = mysql_fetch_row($query))
	{
		echo '<option value="'.$result[0].'">DOSBox '.$result[1].'</option>';
	}
	echo '</select>';
}

function letter_check($letter){
	if(!isset($letter)) $letter = "A";
	
	if($letter != 'num') {
		if(strlen($letter)>1) $letter=substr($letter,0,1);
		$letter = ucfirst($letter);
	}
	return $letter;
} 
function change_version_compatibility_form($gameID)
{
	global $user;
	$gameID = intval($gameID);
	$change = isset($_GET['changeID'])?intval($_GET['changeID']):0;

	$query = mysql_query("SELECT status_games.ID, status_games.status, versions.version, versions.ID FROM versions,status_games WHERE status_games.versionID=versions.ID AND status_games.gameID=$gameID ORDER BY version DESC");
	$num = mysql_num_rows($query);

	while ($result = mysql_fetch_row($query))
	{
		echo '

		<form name="versionchange" method="POST" action="comp_list.php?changeversion=1"><input type="hidden" name="letter" value="'.letter_check($_GET['letter']).'"><input type="hidden" name="gameID" value="'.$change.'">
		<input type="hidden" name="statusID" value="'.$result[0].'">


		<input type="submit" value="Update this item (DOSBox v.'.$result[2].')">';



		echo '<select name="percent">';

		for ($i = 0; $i <= 100; $i++)
		{
			echo '<option value="'.$i.'"'; if ($i == $result[1]) echo ' selected'; echo '>'.$i.'% (game ';
			echo return_status($i);
			echo ')</option>';
		}

		echo '</select>&nbsp;&nbsp;';
		if ($num != 1 AND $user['priv']['compat_list_manage']==1)
		echo '<a href="comp_list.php?removeVERSION_ID='.$result[0].'&gameID='.$change.'&letter='.letter_check($_GET['letter']).'"><img src="site_images/delete_icon.gif" border="0"></a>';
		echo '</form>';
	}

}
function add_version_compatibility_form($gameID)
{
	$gameID = isset($gameID)?intval($gameID):0;
	$query = mysql_query("SELECT versions.ID, versions.version FROM versions ORDER BY versions.version DESC");

	echo '<select name="versionID">
	<option>-</option>';

	while($result=mysql_fetch_row($query))
	{
		$q=mysql_query("

		SELECT
		COUNT(*)
		FROM
		status_games
		WHERE
		status_games.versionID=$result[0] AND status_games.gameID=$gameID
		");

		$a=mysql_fetch_row($q);


		if ($a[0] == 0)
		{
			echo '<option value="'.$result[0].'">version '.$result[1].'</option>';
		}
	}
	echo '</select>';
}

function choose_percentage()
{
	echo '<select name="percent">';

	for ($i = 0; $i <= 100; $i++)
	{
		echo '<option value="'.$i.'">'.$i.'% (game ';
		echo return_status($i);
		echo ')</option>';
	}

	echo '</select>';
}
function compat_list_latest()
{
	global $user;
	$limit = 10;
	if($user['priv']['compat_list_manage']==1){
		$limit = 30;
	}
	$query = mysql_query("SELECT ID, name, version, released FROM list_game ORDER BY added DESC LIMIT $limit");
	while ($result = mysql_fetch_row($query))
	{
		$q = mysql_query("SELECT status_games.status FROM status_games WHERE status_games.gameID=$result[0] ORDER BY status_games.status DESC LIMIT 1");
		$a = mysql_fetch_row($q);

		echo '<option value="'.$result[0].'"';

		if (isset($_GET['showID']) && ($result[0]==$_GET['showID']))
		echo ' selected';

		echo '> (';

		echo return_status($a[0]);

		echo ') '.$result[1].''; if ($result[2] != 0) echo ' ('.$result[3].')'; echo '</option>';
	}
}

function comp_mainlist($letter)
{
$letter = letter_check($letter);

	echo '<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787"><tr><td>
	<table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">Game directory (browsing from <b>'; if($letter=='num') echo 'numerical'; else echo $letter; echo '</b>)</td></tr></table></td>
	</tr></table></td></tr></table><table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#113466"><tr>
	<td><table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';


	$letter = mysql_real_escape_string(stripslashes($letter));

	if (isset($letter) && ($letter == 'num'))
	$query = mysql_query("
	SELECT
	list_game.ID, list_game.name, list_game.released, list_game.version
	FROM
	list_game
	WHERE
	(list_game.first_char='1' OR list_game.first_char='2' OR list_game.first_char='3' OR list_game.first_char='4' OR list_game.first_char='5' OR list_game.first_char='6' OR list_game.first_char='7' OR list_game.first_char='8' OR list_game.first_char='9' OR list_game.first_char='0')
	ORDER BY
	list_game.name");
	else
	$query = mysql_query("
	SELECT
	list_game.ID, list_game.name, list_game.released, list_game.version
	FROM
	list_game
	WHERE
	list_game.first_char='$letter'
	ORDER BY
	list_game.name");
	if (mysql_num_rows($query) == 0)
	echo '<table cellspacing="0" cellpadding="0" width="100%"><tr><td>No games with the first letter "'.$letter.'" was found in the database!</td></tr></table>';
	else
	{


		echo '<table cellspacing="0" cellpadding="0" class="tablecomp_min10">
		<tr>
		<td width="385">
		<b>Game:</b>
		</td>

		<td width="6">
		&nbsp;
		</td>

		<td width="70">
		<b>Version:</b>
		</td>

		<td width="6">
		&nbsp;
		</td>

		<td width="70">
		<b>Status:</b>
		</td>


		<td width="6">
		&nbsp;
		</td>


		<td width="275">
		<b>runable&nbsp;&nbsp;-&nbsp;&nbsp;playable&nbsp;&nbsp;-&nbsp;&nbsp;supported</b>
		</td>
		</tr>';

		while($result=mysql_fetch_row($query))
		{

			$status_query = mysql_query("SELECT status_games.status, versions.version FROM versions, status_games WHERE status_games.gameID=".$result[0]." AND status_games.versionID=versions.ID ORDER BY status_games.status DESC,status_games.versionID DESC LIMIT 1");
			$status = mysql_fetch_row($status_query);
			$percent_text = return_status($status[0]);


			echo '<tr>
			<td>
<a href="comp_list.php?showID='.$result[0].'&amp;letter='.$letter.'">'.$result[1].'</a>'; if ($result[2] != 0) echo ' ('.$result[2].')'; 
echo '
			</td>

			<td>
			&nbsp;
			</td>

			<td>
			'.$status[1].'
			</td>

			<td>
			&nbsp;
			</td>

			<td>
			'.$percent_text.'
			</td>

			<td>
			&nbsp;
			</td>


			<td>';
			if ($status[0] != 0) echo '<img src="site_images/progress.gif" border="1" width="'.$status[0].'%" height="8" alt="'.$status[0].'% ('.$percent_text.')">'; else echo '&nbsp;';
			echo '</td>
			</tr>';
		}

		echo '</table>';

	}
	echo '</td></tr></table></td></tr></table></td></tr></table><br>';
}
function stri_replace($searchFor, $replaceWith, $string, $offset = 0)
{
	$lsearchFor = strtolower($searchFor);
	if(strlen($lsearchFor) == 0)
		return($string);
	$lstring = strtolower($string);
	$newPos = strpos($lstring, $lsearchFor, $offset);
	if (strlen($newPos) == 0)
	return($string);
	else
	{
		$left = substr($string, 0, $newPos);
		$right = substr($string, $newPos + strlen($searchFor));
		$newStr = $left . $replaceWith . $right;
		return stri_replace($searchFor, $replaceWith, $newStr, $newPos + strlen($replaceWith));
	}
}
function search_results($keyword)
{


	$keyword = mysql_real_escape_string(stripslashes($keyword));

	$query = mysql_query("
	SELECT
	list_game.ID, list_game.name, list_game.released, list_game.version, list_game.first_char
	FROM
	list_game
	WHERE
	list_game.name LIKE '%$keyword%'
	ORDER BY
	list_game.name");

	$num = mysql_num_rows($query);
	if ($num == 0)
	{
		echo '<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787"><tr><td>
		<table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">Searching game-database (keyword "<b>'.$keyword.'</b>")</td></tr></table></td>
		</tr></table></td></tr></table><table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#113466"><tr>
		<td><table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';

		echo '<table cellspacing="0" cellpadding="0" width="100%"><tr><td><i>0 hits found with the keyword "'.$keyword.'"</i></td></tr></table>';

	}
	else
	{

		echo '<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787"><tr><td>
		<table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">Searching: <b>'.$keyword.'</b> (<b>'.$num.'</b>'; if ($num ==1) echo ' result found'; else echo ' results found'; echo ')</td></tr></table></td>
		</tr></table></td></tr></table><table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#113466"><tr>
		<td><table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';


		echo '<table cellspacing="0" cellpadding="0" class="comp_min10">
		<tr>
		<td width="335">
		<b>Game:</b>
		</td>

		<td width="6">
		&nbsp;
		</td>

		<td width="70">
		<b>Version:</b>
		</td>

		<td width="6">
		&nbsp;
		</td>

		<td width="70">
		<b>Status:</b>
		</td>


		<td width="6">
		&nbsp;
		</td>


		<td width="225">
		<b>runable&nbsp;&nbsp;-&nbsp;&nbsp;playable&nbsp;&nbsp;-&nbsp;&nbsp;supported
		</td>
		</tr>';

		while($result=mysql_fetch_row($query))
		{

			$status_query = mysql_query("SELECT status_games.status, versions.version FROM versions, status_games WHERE status_games.gameID=".$result[0]." AND status_games.versionID=versions.ID ORDER BY status_games.status DESC LIMIT 1");
			$status = mysql_fetch_row($status_query);
			$percent_text = return_status($status[0]);

			$name = stri_replace($keyword, "<b><font color=\"#90DEFF\">".$keyword."</b></font color>", $result[1]);
			echo '<tr>
			<td>
			<a href="comp_list.php?showID='.$result[0].'&letter='.$result[4].'&search='.$keyword.'">'.$name.'</a>'; if ($result[2] != 0) echo ' ('.$result[2].')'; echo '
			</td>

			<td>
			&nbsp;
			</td>

			<td>
			'.$status[1].'
			</td>

			<td>
			&nbsp;
			</td>

			<td>
			'.$percent_text.'
			</td>

			<td>
			&nbsp;
			</td>


			<td>';
			if ($status[0] != 0) echo '<img src="site_images/progress.gif" border="1" width="'.$status[0].'%" height="8" alt="'.$status[0].'% ('.$percent_text.')">'; else echo '&nbsp;';
			echo '</td>
			</tr>';
		}

		echo '</table>';

	}
	echo '</td></tr></table></td></tr></table></td></tr></table><br>';
}
function comp_show_ID($showID)
{
	global $user;
	//HACK THING ?
	$showID = intval($showID);
	$showID = mysql_real_escape_string(stripslashes($showID));

	$query = mysql_query("

	SELECT
	list_game.ID, list_game.name, list_game.publisher, list_game.released,
	userdb.nickname, userdb.ID, ownerID
	FROM
	list_game, userdb
	WHERE
	list_game.ID=$showID AND list_game.ownerID=userdb.ID
	");

	$result=mysql_fetch_row($query);

	echo '<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787"><tr><td>
	<table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">Game details';

	if (isset($_SESSION['userID']))
	echo '&nbsp;&nbsp;<a href="comp_list.php?changeID='.$showID.'&letter='.letter_check($_GET['letter']).'"><img src="site_images/change_icon.gif" border="0"></a>&nbsp;';
	if ((isset($_SESSION['userID']) && $result[6] == $_SESSION['userID']) || (isset($user) && $user['priv']['manage_comment']==1))
	echo '<a href="comp_list.php?removeID='.$showID.'&letter='.letter_check($_GET['letter']).'"><img src="site_images/delete_icon.gif" border="0"></a>';

	echo '</td></tr></table></td>
	</tr></table></td></tr></table><table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#113466"><tr>
	<td><table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';


	echo ' '.$result[1].' - '.$result[2]; if ($result[3] != 0) echo ' ('.$result[3].')';
	echo '<br><b>Tested By:</b> '.$result[4].'<br><br>';

	$status_query=mysql_query("
	SELECT
	status_games.versionID, status_games.status, versions.version

	FROM
	status_games, versions
	WHERE
	status_games.gameID=$result[0] AND status_games.versionID=versions.ID
	ORDER BY
	versions.version DESC
	");

	while ($status_query && ($status = mysql_fetch_row($status_query)))
	{
		$status_text = return_status($status[1]);

		echo '<table cellspacing="0" cellpadding="0" width="480">
		<tr>
		<td>
		<table cellspacing="0" cellpadding="0" width="262">
		<tr>
		<td width="237">
		<b>runable&nbsp;&nbsp;-&nbsp;&nbsp;playable&nbsp;&nbsp;-&nbsp;&nbsp;supported
		</td>
		</tr>';



		echo '<tr>
		<td width="237">';
		if ($status[1] != 0) echo '<img src="site_images/progress.gif" border="1" width="'.$status[1].'%" height="8" alt="'.$status[1].'% ('.$status_text.')">'; else echo '0% supported';
		echo '</td>
		</tr>';

		echo '</table>
		</td>
		<td width="20">
		&nbsp;
		</td>
		<td valign="middle" align="left" width="330">
		<b>DOSBox version:</b> '.$status[2].'</b> ('.$status_text.')<br>
		</td>

		</tr>
		</table>
		<hr line color="#FFFFFF" width="525" align="left">
		';



	}

	if (isset($_SESSION['userID']) AND (!isset($_GET['post_new'])|| ($_GET['post_new']!=1)))
	echo '<a href="comp_list.php?post_newMSG=1&showID='.$showID.'&letter='.letter_check($_GET['letter']).'#post_comment">Click here</a> to post new comment<br>';
	echo '</td></tr></table></td></tr></table></td></tr></table>';
}
function comp_bar()
{
	$letter = letter_check(isset($_GET['letter'])?$_GET['letter']:'a');
	echo '

	<table cellspacing="0" cellpadding="0">
	<tr>
	<td>
	<b>First char:
		</b>
	</td>

	<td width="6">&nbsp;</td>

	<td>
	<b>Latest added:
		</b>
	</td>

	<td width="6">&nbsp;</td>

	<td>
	<b>Game-search:
		</b>
	</td>


	</tr>

	<tr>
	<td>
	<form name="sort" method="GET" action="comp_list.php">
	<select name="letter" onChange="submit(form.sort);">';
	for ( $l = ord( "A" ); $l <= ord( "Z" ); $l++ ) {
		$ll = chr( $l );
		$selected = $letter == $ll ? " selected" : "";
		echo "<option value='$ll'$selected>$ll (".count_firstchar($ll).")</option>";
	}
	echo '<option value="num"'; if ($letter =='num') echo ' selected'; echo '>0-9 ('; echo count_firstchar('num'); echo ')</option>
	</select>

	</form>

	</td>

	<td width="6">&nbsp;</td>

	<td>
	<form name="latest" method="GET" action="comp_list.php">
	<input name="letter" type="hidden" value="'.$letter.'">
	<select name="showID" onChange="submit(form.latest);">
	<option value="0">-</option>';
	compat_list_latest();

	echo '
	</select>
	</form>

	</td>

	<td width="6">&nbsp;</td>

	<td>
	<form name="game-search" method="GET" action="comp_list.php">
	<input name="letter" type="hidden" value="'.$letter.'"><input type="text" name="search" size="14" value="game-name">&nbsp;<input type="submit" name="submit" value="Search">
	</form>
	</td>

	</tr>
	</table>';
	if ((!isset($_GET['post_new']) || $_GET['post_new'] != 1) AND ( !isset($_GET['posting']) || $_GET['posting'] !=1) AND isset($_SESSION['userID']))
	echo '<a href="comp_list.php?post_new=1&letter='.$letter.'">Add new game to database</a><br><br>';
}
function count_firstchar($letter)
{
	$letter = mysql_real_escape_string(letter_check(stripslashes($letter)));

	if ($letter == 'num')
	$query = mysql_query("SELECT COUNT(*) FROM list_game WHERE first_char='1' OR first_char='2' OR first_char='3' OR first_char='4' OR first_char='5' OR first_char='6' OR first_char='7' OR first_char='8' OR first_char='9' OR first_char='0' OR first_char='#' OR first_char='!' OR first_char='$'");
	else
	$query = mysql_query("SELECT COUNT(*) FROM list_game WHERE first_char='$letter'");

	$result = mysql_fetch_row($query);
	return $result[0];
}
function return_status($percent)
{
	if ($percent == 0 )
	return 'broken';	// game doesn't work in DOSBox
	elseif ($percent >= 64 )
	return 'supported';	// game is supported, may have some glitches and small issues
	elseif ($percent >= 29)
	return 'playable';	// game is playable but with some serius problems/errors/glitches
	elseif ($percent <= 28 )
	return 'runable';	// game starts in DOSBox but is not playable
}

function get_msg_threads($gameID, $msgID=null)
{
	global $user;
	$gameID = mysql_real_escape_string(intval(stripslashes($gameID)));

	if (!isset($msgID))
	{
		$query = mysql_query("
		SELECT
		list_comment.ID, list_comment.gameID, list_comment.ownerID,
		list_comment.subject, list_comment.text, list_comment.parent_id,
		DATE_FORMAT(list_comment.datetime, '%Y-%m-%d %H:%i'),
		userdb.ID, userdb.nickname
		FROM
		list_comment, userdb
		WHERE
		list_comment.gameID=$gameID
		AND list_comment.ownerID=userdb.ID

		ORDER BY datetime ASC");
	}

	while ($result = mysql_fetch_row($query))
	{

		echo '<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000">
		<tr>
		<td valign="top" align="left">
		<table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787">
		<tr>
		<td>
		<table cellspacing="0" cellpadding="0" width="100%">
		<tr>
		<td valign="top" align="left">
		'.$result[3].' ('.$result[6].')';

		if (isset($user) && $user['priv']['compat_list_manage']==1){
			$letter = letter_check($_GET['letter']);
			$show = isset($_GET['showID'])?intval($_GET['showID']):0;
		echo '&nbsp;<a href="comp_list.php?removeMSG_ID='.$result[0].'&letter='.$letter.'&gameID='.$show.'"><img src="site_images/msgboard_remove.gif" alt="Remove this comment" border="0"></a>';
}




		echo '</td>
		<td valign="top" align="right" width="135">
		'.$result[8].'
		</td>
		</tr>
		</table>

		</td>
		</tr>
		</table>
		</td>
		</tr>
		</table>
		<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000">
		<tr>
		<td valign="top" align="left">
		<table cellspacing="4" cellpadding="0" width="100%" bgcolor="#0D2E5D">
		<tr>
		<td>
		<table cellspacing="0" cellpadding="0" width="100%">
		<tr>
		<td valign="top" align="left">
		
		';
		echo wordwrap($result[4], 105, "\n", 1);;

		echo '</td>
		</tr>
		</table>

		</td>
		</tr>
		</table>
		</td>
		</tr>
		</table>';
	}
	if (mysql_num_rows($query) != 0)
	echo '<br><br>';
}
function write_comment()
{
	$letter = letter_check($_GET['letter']);
    $show = isset($_GET['showID'])?intval($_GET['showID']):0;

	echo '<table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#355787"><tr><td>
	<table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">Write new comment</td></tr></table></td>
	</tr></table></td></tr></table><table class="tablecomp" cellspacing="0" cellpadding="1" bgcolor="#000000"><tr><td valign="top" align="left"><table cellspacing="4" cellpadding="0" width="100%" bgcolor="#113466"><tr>
	<td><table cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" align="left">';

	echo '
	<form action="comp_list.php?post_comment=1" method="POST" name="comment">
	<input type="hidden" name="gameID" value="'.$show.'"><input type="hidden" name="letter" value="'.$letter.'">';

	if (isset($_GET['problem']) && $_GET['problem']==1)
	echo '<b>Error - this form must be filled in accurate!</b><br>';

	echo '
	<br>
	Subject: (max 60 chars)<br>
	<input type="text" name="subject" size="60" maxlength="60"><br><br>

	Comment: (max 1024 chars)<br>
	<textarea name="text" cols="50" rows="10"></textarea><br><br>
	<input type="submit" value="Post comment">
	</form>';

	echo '</td></tr></table></td></tr></table></td></tr></table><br>';
}
function show_screenshots($limit)
{
	$page = mysql_real_escape_string(stripslashes(isset($_GET['page'])?(int)$_GET['page']:0));

	$query = mysql_query("
	SELECT
	ID, text
	FROM
	screenshots
	ORDER BY
	screenshots.datetime DESC

	LIMIT ".($page*3).",3

	");

	$count_query = mysql_query("SELECT COUNT(ID) FROM screenshots");
	$count = mysql_fetch_row($count_query);

	$maxpages=floor(($count[0]-1)/3);

	while ($result = mysql_fetch_row($query))
	{
		echo '<a href="screenshots/big/'.$result[0].'.png" target="_blank"><img src="screenshots/thumb/'.$result[0].'.png" border="0" alt="'.$result[1].'"></a><br><br>';
	}

	echo '<table cellspacing="0" cellpadding="0" width="200">
	<tr>
	<td align="left" width="17">';

	if (!$page==0)
	echo '<a href="information.php?page='.($page-1).'"><img src="site_images/arrow_left.gif" border="0" alt="Browse screenshots-archive"></a>';
	else
	echo '<img src="site_images/arrow_left_nofuther.gif" border="0">';

	echo '</td><td align="center">browse screen-archive</td><td align="right" width="17">';

	if ($page<($maxpages))
	echo '<a href="information.php?page='.($page+1).'"><img src="site_images/arrow_right.gif" border="0" alt="Browse screenshots-archive"></a>';
	else
	echo '<img src="site_images/arrow_right_nofuther.gif" border="0">';

	echo '</td></tr></table>';
}
function get_support_stats()
{
	$query = mysql_query("SELECT COUNT(ID) FROM list_game");
	$result = mysql_fetch_row($query);
	$text = 'Compatibility statistics (<b>'.$result[0].'</b> games in database)';

	template_pagebox_start($text);

	echo '
	<table cellspacing="0" cellpadding="0" width="100%">
	<tr>
	<td valign="top">
	<b>Version:</b></td>

	<td valign="top">
	<b>Games broken:</b></td>

	<td valign="top">
	<b>Games runable:</b></td>

	<td valign="top">
	<b>Games playable:</b></td>

	<td valign="top">
	<b>Games supported:</b></td>
	</tr>

	';

	$version_query = mysql_query("SELECT ID, version FROM versions ORDER BY version DESC");
	while ($version_result = mysql_fetch_row($version_query))
	{
		$v_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]);
		$v_count = mysql_fetch_row($v_query);

		$broken_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status = 0");
		$broken_result = mysql_fetch_row($broken_query);

		$supported_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status >= 64");
		$supported_result = mysql_fetch_row($supported_query);

		$playable_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status >= 29 AND status_games.status < 64");
		$playable_result = mysql_fetch_row($playable_query);

		$runable_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status <= 28 AND status_games.status > 0");
		$runable_result = mysql_fetch_row($runable_query);

		echo '<tr>
		<td valign="top">
		DOSBox '.$version_result[1].' ('.$v_count[0].')</td>

		<td valign="top">
		'.$broken_result[0].'</td>

		<td valign="top">
		'.$runable_result[0].'</td>

		<td valign="top">
		'.$playable_result[0].'</td>

		<td valign="top">
		'.$supported_result[0].'</td>
		</tr>';


	}
	echo '</table>';
	template_pagebox_end();
}
?>
