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
		echo '<b>'.$result[1].'</b> - '.$result[2].'&nbsp;&nbsp;<a href="news.php?change_news=1&amp;newsID='.$result[4].'"><img src="site_images/change_icon.gif" border="0"></a>&nbsp;<a href="news.php?removing_news=1&newsID='.$result[4].'"><img src="site_images/delete_icon.gif" border="0"></a>';
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


		template_pagebox_start($text, 690);
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

function letter_check( )
{
    $letter = isset( $_POST['letter'] ) ? $_POST['letter'] : isset( $_GET['letter'] ) ? $_GET['letter'] : "A";

    switch ( $letter )
    {
    case 'num' :
    case 'broken' :
    case 'runnable' :
    case 'playable' :
        break;
    default :
        $letter = ucfirst( $letter[0] );
        break;
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

		<form name="versionchange" method="POST" action="comp_list.php?changeversion=1"><input type="hidden" name="letter" value="'.letter_check().'"><input type="hidden" name="gameID" value="'.$change.'">
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
		echo '<a href="comp_list.php?removeVERSION_ID='.$result[0].'&gameID='.$change.'&letter='.letter_check().'"><img src="site_images/delete_icon.gif" border="0"></a>';
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
function compat_list_query($sql)
{
    $query = mysql_query($sql);
    while( $result = mysql_fetch_row($query) )
    {
        $sel = isset($_GET['showID']) && $_GET['showID'] == $result[0] ? "selected" : "";
        echo "<option value='$result[0]' $sel> (".return_status($result[3]).") $result[1] ($result[2])</option>";
    }
    echo "</select>";
}
function compat_list_latest()
{
    global $user;
    $limit = 10;
    if( $user['priv']['compat_list_manage']==1 )
        $limit = 100;
    $sql = "SELECT g.ID, name, released, status"
        . " FROM list_game g"
        . " JOIN (status_games s)"
        . " ON (g.ID=s.gameID)"
        . " GROUP BY name"
        . " ORDER BY added DESC"
        . " LIMIT $limit";
    echo "<select name='showID' onChange='if(form.showID1 !== undefined)form.showID1.selectedIndex=0; submit(form.latest);'><option value='0'>-</option>";
    compat_list_query($sql);
}

function compat_list_latest_comments()
{
    global $user;
    if( $user['priv']['compat_list_manage'] != 1 )
        return;
    $sql = "SELECT * FROM"
        . " (SELECT g.ID, name, released, status, `datetime`"
        . "  FROM list_game g"
        . "  JOIN (list_comment c, status_games s)"
        . "  ON (c.gameID=g.ID AND s.gameID=g.ID)"
        . "  ORDER BY `datetime` DESC, status DESC) a"
        . " GROUP BY a.`datetime`"
        . " ORDER BY a.`datetime` DESC"
        . " LIMIT 100";
    echo "<select name='showID1' onChange='form.showID.selectedIndex=0; submit(form.latest);'><option value='0'>-</option>";
    compat_list_query($sql);
}

function compat_status_query( $min=0, $max=0, $order="version DESC, status, name, ID", $regexp="first_char RLIKE '.'" )
{
    $regexp = isset( $_GET['version'] ) ? "v.version = ".floatval( $_GET['version'] )." AND $regexp" :
        "s.versionID = (SELECT MAX(versionID) FROM status_games WHERE gameID = g.ID) AND $regexp";

    return <<<EOT
SELECT g.ID, name, released, v.version, status, first_char
FROM   list_game    g,
       versions     v,
       status_games s
WHERE  g.ID = s.gameID
AND    v.ID = s.versionID
AND    status >= $min
AND    status <= $max
AND    $regexp
ORDER BY
       $order
EOT;
}

function comp_list_header( )
{
    echo <<<EOT
<table cellspacing="0" cellpadding="0" class="tablecomp_min10">
 <tr>
  <th width="240">Game:</th>
  <th width="30">Year:</span></th>
  <th width="5">&nbsp;</th>
  <th width="40">Version:</th>
  <th width="5">&nbsp;</th>
  <th width="70">Status:</th>
  <th width="210">
   <span class="runnable">runnable</span> -
   <span class="playable">playable</span> -
   <span class="supported">supported</span>
  </th>
 </tr>

EOT;
}

function comp_mainlist_item( $result, $class="content", $keyword=null )
{
    $id      = $result[ 0 ];
    $name    = $result[ 1 ];
    $year    = $result[ 2 ] == 0 ? "" : $result[ 2 ];
    $version = $result[ 3 ];
    $percent = $result[ 4 ];
    $letter  = $result[ 5 ];
    $status  = return_status( $percent );
    $image   = <<<EOT
<img class="status" src="site_images/$status.png" width="$percent%" height="8" alt="$percent% ($status)"/>

EOT;
    $image   = $percent != 0 ? $image : "&nbsp;";

    if ( ! empty( $keyword ) )
        $name = str_ireplace( $keyword, "<span class=\"bold hilite\">$keyword</span>", $name );

    echo <<<EOT
<tr class="$class">
 <td><a href="comp_list.php?showID=$id&amp;letter=$letter">$name</a></td>
 <td>$year</td>
 <td>&nbsp;</td>
 <td>$version</td>
 <td>&nbsp;</td>
 <td><span class="$status">$status</span></td>
 <td>$image</td>
</tr>

EOT;
}

function comp_mainlist( $letter )
{
    switch ( $letter )
    {
    case 'num' :
        $ltrstr = "Numerical";
        $query = mysql_query( compat_status_query( 0, 100, "name, version DESC", "first_char NOT RLIKE '[A-Z]'" ) );
        break;
    case 'broken' :
        $ltrstr = "Broken Games";
        $query = mysql_query( compat_status_query( ) );
        break;
    case 'runnable' :
        $ltrstr = "Runnable Games";
        $query = mysql_query( compat_status_query( 1, 28 ) );
        break;
    case 'playable' :
        $ltrstr = "Playable Games";
        $query = mysql_query( compat_status_query( 29, 63 ) );
        break;
    default :
        $ltrstr = htmlspecialchars( $letter );
        $letter = mysql_real_escape_string( $letter );
        $query = mysql_query( compat_status_query( 0, 100, "name, version DESC", "first_char='$letter'" ) );
        break;
    }

    template_pagebox_start( "Game directory (browsing from <b>$ltrstr</b>)" );
    if ( mysql_num_rows( $query ) )
    {
        comp_list_header( );
        for ( $odd = 0 ; $result = mysql_fetch_row( $query ) ; $odd++ )
            comp_mainlist_item( $result, $odd % 2 == 0 ? "content_odd" : "content" );
        echo "</table>";
    }
    else
	echo "<p>No games with a first letter of &quot;$ltrstr&quot; were found in the database!</p>";
    template_pagebox_end( );
}

function search_results($keyword)
{
    $keyword = mysql_real_escape_string(stripslashes($keyword));
    $query = mysql_query( compat_status_query( 0, 100, "name, version DESC", "name LIKE '%$keyword%'" ) );
    $num = mysql_num_rows( $query );
    template_pagebox_start('Searching: <b>'.$keyword.'</b> (<b>'.$num.'</b> result'.($num>1?"s":"").' found)');
    if ( $num )
    {
        comp_list_header( );
        for ( $odd = 0 ; $result = mysql_fetch_row( $query ) ; $odd++ )
            comp_mainlist_item( $result, $odd % 2 == 0 ? "content_odd" : "content", $keyword );
        echo "</table>";
    }
    else
        echo "<p><i>0 hits found with the keyword $keyword</i></p>";
    template_pagebox_end( );
}

function comp_show_ID( $showID )
{
    global $user;
    $showID = intval($showID); // Restored by Qbix. Want to be extra safe with this one.
    $letter = letter_check( );
    $sql = <<<EOT
SELECT l.ID, l.name, publisher, released, nickname, ownerID, status, v.version
FROM   list_game l,
       userdb u,
       status_games s,
       versions v
WHERE  u.ID=ownerID
AND    s.gameID=l.ID
AND    s.versionID=v.ID
AND    l.ID=$showID
ORDER BY
       version DESC
EOT;

    $query = mysql_query( $sql );
    $result = mysql_fetch_row( $query );
    if ( !$result )
        return;
    $html = "";
    if ( isset( $_SESSION['userID'] ) )
    {
        $html = <<<EOT
<a href="comp_list.php?changeID=$showID&letter=$letter">
 <img src="site_images/change_icon.gif" border="0"/>
</a>

EOT;
        if ( isset( $user ) && $user['priv']['manage_comment'] == 1 || $_SESSION['userID'] == $result[5] )
        {
            $html = <<<EOT
$html
<a href="comp_list.php?removeID=$showID&letter=$letter">
 <img src="site_images/delete_icon.gif" border="0"/>
</a>

EOT;
        }
    }
    $html = "Game details $html";
    template_pagebox_start( $html );
    echo "<span>$result[1] - $result[2]";
    if ( $result[3] != 0 )
        echo " ($result[3])";
    echo "<br/><span class='bold'>Tested By:</span> $result[4]<br/><br/></span>";

    do {
        $status_text = return_status( $result[6] );
        $status_html = $result[6] != 0 ? "<img class='status' src='site_images/$status_text.png' width='$result[6]%' height='8' alt='$result[6]% ($status_text)'>" : "0% supported";
        echo <<<EOT
<table cellspacing="0" cellpadding="0" width="480">
 <tr>
  <td>
   <table cellspacing="0" cellpadding="0" width="262">
    <tr>
     <td width="237">
      <span class="bold runnable">runnable</span> &nbsp;-&nbsp; <span class="bold playable">playable</span> &nbsp;-&nbsp; <span class="bold supported">supported</span>
     </td>
    </tr>
    <tr>
     <td width="237">$status_html</td>
    </tr>
   </table>
  </td>
  <td width="20">
   &nbsp;
  </td>
  <td valign="middle" align="left" width="330">
   <span class="bold">DOSBox version:</span> $result[7] (<span class="$status_text">$status_text</span>)<br/>
  </td>
 </tr>
</table>
<hr line color="white" width="525" align="left"/>

EOT;
        $result = mysql_fetch_row( $query );
    } while ( $result );

    if ( isset( $_SESSION['userID'] ) && !isset( $_GET['post_new'] ) ||
         isset( $_GET['post_new'] ) && $_GET['post_new'] != 1 )
	echo <<<EOT
<p>
 <a class="bold" href="comp_list.php?post_newMSG=1&showID=$showID&letter=$letter#post_comment">Click here</a>
 to post a new comment.
</p>

EOT;
    template_pagebox_end( );
}

function comp_bar()
{
	$letter = letter_check();
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
	<option value="broken"'.($letter=="broken"?" selected":"").'>broken</option>
	<option value="runnable"'.($letter=="runnable"?" selected":"").'>runnable</option>
	<option value="playable"'.($letter=="playable"?" selected":"").'>playable</option>
	</select>

	</form>

	</td>

	<td width="6">&nbsp;</td>

	<td>
	<form name="latest" method="GET" action="comp_list.php">
	<input name="letter" type="hidden" value="'.$letter.'">';
	compat_list_latest();
	compat_list_latest_comments();
	echo '
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
	$sql = "SELECT COUNT(*) FROM list_game WHERE first_char";
	$sql.= $letter == 'num' ? " NOT RLIKE '[A-Z]'" : "='$letter'";
	$result = mysql_fetch_row(mysql_query($sql));
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
	return 'runnable';	// game starts in DOSBox but is not playable
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

		ORDER BY datetime DESC");
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
			$letter = letter_check();
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
		echo nl2br($result[4]);

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
	$letter = letter_check();
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
	$page = isset($_GET['page'])?abs((int)$_GET['page']):0;
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
	<span class="bold"><a href="?letter=broken">Games broken</a>:</span></td>

	<td valign="top">
	<span class="bold runnable"><a href="?letter=runnable">Games runnable</a>:</span></td>

	<td valign="top">
	<span class="bold playable"><a href="?letter=playable">Games playable</a>:</span></td>

	<td valign="top">
	<span class="bold supported">Games supported:</span></td>
	</tr>

	';

	$version_query = mysql_query("SELECT ID, version FROM versions ORDER BY version DESC");
	$odd = false;
	while ($version_result = mysql_fetch_row($version_query))
	{
		$odd = $odd == true ? false : true;
		$v_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]);
		$v_count = mysql_fetch_row($v_query);

		$broken_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status = 0");
		$broken_result = mysql_fetch_row($broken_query);

		$supported_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status >= 64");
		$supported_result = mysql_fetch_row($supported_query);

		$playable_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status >= 29 AND status_games.status < 64");
		$playable_result = mysql_fetch_row($playable_query);

		$runnable_query = mysql_query("SELECT COUNT(ID) FROM status_games WHERE status_games.versionID=".$version_result[0]." AND status_games.status <= 28 AND status_games.status > 0");
		$runnable_result = mysql_fetch_row($runnable_query);

		if( $v_count[0] )
		echo '<tr '.($odd == true ? 'class="content_odd"' : '').'>
		<td valign="top">
		DOSBox '.$version_result[1].' ('.$v_count[0].')</td>
		<td valign="top">
		<a class="nodecor" href="?letter=broken&version='.$version_result[1].'">'.$broken_result[0].'</a> ('.number_format($broken_result[0]/$v_count[0]*100,2).'%)</td>

		<td valign="top">
		<a class="nodecor" href="?letter=runnable&version='.$version_result[1].'">'.$runnable_result[0].'</a> ('.number_format($runnable_result[0]/$v_count[0]*100,2).'%)</td>

		<td valign="top">
		<a class="nodecor" href="?letter=playable&version='.$version_result[1].'">'.$playable_result[0].'</a> ('.number_format($playable_result[0]/$v_count[0]*100,2).'%)</td>

		<td valign="top">
		'.$supported_result[0].' ('.number_format($supported_result[0]/$v_count[0]*100,2).'%)</td>
		</tr>';


	}
	echo '</table>';
	template_pagebox_end();
}
?>
