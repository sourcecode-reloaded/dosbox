<?php
// this src is written under the terms of the GPL-licence, see gpl.txt for futher details
	include("include/standard.inc.php");
	sstart();




if ((isset($_GET['newsID'],$_SESSION['userID']) && check_if_owner($_GET['newsID'],$_SESSION['userID'])) || (isset($user) && $user['priv']['news_management']==1))
{
	if (isset($_GET['changing_news']) && ( $_GET['changing_news']==1))
	{
		$text 		= mysql_real_escape_string(stripslashes($_POST['text']));
		$updateID	= mysql_real_escape_string(stripslashes($_POST['updateID']));
		
		mysql_query("UPDATE news SET text='$text' WHERE ID = $updateID");
		Header("Location: news.php?show_news=1");
	}
	if (isset($_GET['removing_news']) && ($_GET['removing_news']==1))
	{
		$newsID=mysql_real_escape_string(stripslashes($_GET['newsID']));
		mysql_query("DELETE FROM news WHERE news.ID=$newsID");
		Header("Location: news.php?show_news=1");
	}
	if (isset($_GET['change_news']) && ($_GET['change_news']==1))
	{
		template_header();
		echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table		
		
		template_pagebox_start("Changing news-item");			
		
		

		$query = mysql_query("
				SELECT
					text
				FROM
					news
				WHERE
					news.ID = ".mysql_real_escape_string(stripslashes($_GET['newsID']))
				);

		$result = mysql_fetch_row($query);

		echo '
			<form action="news.php?changing_news=1&newsID='.intval($_GET['newsID']).'" method="POST"><input name="updateID" type="hidden" value="'.intval($_GET['newsID']).'">

			News text:<br>
			<textarea name="text" cols="60" rows="12">'.$result[0].'</textarea>
			
			<br><br>
			<input type="submit" name="submit" value="Send changes to database">
			</form><br>
		
				</font>';
				
		template_pagebox_end();	
		echo '</td></tr></table>';	// end of framespacing-table					
		template_end();
	}
}

if (isset($user) && $user['priv']['post_news']==1)
{
	if (isset($_GET['posting_news'])  && ($_GET['posting_news']==1))
	{
		$text = mysql_real_escape_string(stripslashes($_POST['text']));

		mysql_query("INSERT INTO news (text, ownerID, added) VALUES ('$text', ".$_SESSION['userID'].", NOW())");	
		Header("Location: news.php?success=1");
	}
	if (isset($_GET['post_news']) && ($_GET['post_news']==1))
	{
		template_header();
		echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table		
		
		template_pagebox_start("Posting news-item");			
		
		echo '<br>
		
			<form action="news.php?posting_news=1" method="POST">
			News text:<br>
			<textarea name="text" cols="60" rows="12"></textarea>
			
			<br><br>
			<input type="submit" name="submit" value="Send post to database">
			</form>';					
				
		template_pagebox_end();	
		echo '</td></tr></table>';	// end of framespacing-table					
		template_end();
	}
}


if (isset($_GET['show_news']) && $_GET['show_news']==1)
{
	template_header();
	echo '<br>
<table width="100%">
	<tr>
		<td width="14">&nbsp;</td>
		<td>';// start of framespacing-table		

	echo '
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<span class="c1"><strong>Note:</strong> this website uses cookies for the user account-system!</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!">
	<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
	<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
	">

	</form><br>
		';
	
	if (isset($user) && ($user['priv']['post_news']==1))
		echo '<a href="news.php?post_news=1">Create news-item</a><br><br>';

	main_news(isset($user)?$user['priv']['news_management']:0);
		
	echo '</td></tr></table>';	// end of framespacing-table					
	template_end();
}
if (isset($_GET['success']) && $_GET['success']==1)
{	
	template_header();
	echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table	
	template_pagebox_start("Posting news-item");			
		
		echo '<br>
				';
			echo '<b>News-item was posted successfully!</b><br><a href="news.php?post_news=1">Click here</a> to post one more news-item or <a href="news.php?show_news=1">click here</a> to show the main news-page again!<br><br>';
			
	template_pagebox_end();
	echo '</td></tr></table>';	// end of framespacing-table	
	template_end();
}
?>
