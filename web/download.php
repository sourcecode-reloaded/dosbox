<?php
// this src is written under the terms of the GPL-licence, see gpl.txt for futher details
	include("include/standard.inc.php");
	sstart();
//Currently assuming that users with download_management abilities can be trusted. Should be changed in the future

if (isset($user) && ($user['priv']['download_management']==1))
{

	if (isset($_GET['removeID'],$_GET['catID']))

	{
		$removeID = mysql_real_escape_string(intval(stripslashes($_GET['removeID'])));
		$catID = mysql_real_escape_string(intval(stripslashes($_GET['catID'])));

		mysql_query("DELETE FROM download WHERE download.ID=$removeID");

		Header("Location: download.php?changeID=".$catID);
	}
	if (isset($_GET['adding']) &&$_GET['adding'] ==1)
	{
		$name = mysql_real_escape_string(stripslashes($_POST['name']));
		$version = mysql_real_escape_string(stripslashes($_POST['version']));
		$desc = mysql_real_escape_string(stripslashes($_POST['description']));
		$url = mysql_real_escape_string(stripslashes($_POST['url']));
		$catID = mysql_real_escape_string(intval(stripslashes($_GET['catID'])));
		
		if ($name != '' AND $version != '' AND $url != '' AND $catID != '')
		{
			mysql_query("
			INSERT INTO download (name, url, description, version, catID, added)
			VALUES ('$name', '$url', '$desc', '$version', $catID, NOW())
			");
			
		    Header("Location: download.php?changeID=".isset($_GET['catID'])?intval($_GET['catID']):0);
		}
		else
		      Header("Location: download.php?problem=1&changeID=".isset($_GET['catID'])?intval($_GET['catID']):0);
	}
	if (isset($_GET['changing']) && $_GET['changing']==1)
	{
		$updateID = mysql_real_escape_string(intval(stripslashes($_POST['updateID'])));
		$name = mysql_real_escape_string(stripslashes($_POST['name']));
		$version = mysql_real_escape_string(stripslashes($_POST['version']));
		$desc = mysql_real_escape_string(stripslashes($_POST['description']));
		$url = mysql_real_escape_string(stripslashes($_POST['url']));
		$catID = mysql_real_escape_string(intval(stripslashes($_GET['catID'])));
		
		if ($name != '' AND $version != '' AND $url != '' AND $catID != '')
		{
			mysql_query("UPDATE download SET name='$name', version='$version', description='$desc', url='$url' WHERE ID=$updateID");
			
		    Header("Location: download.php?changeID=".isset($_GET['catID'])?intval($_GET['catID']):0);
		}
		else
	      Header("Location: download.php?problem=1&changeID=".isset($_GET['catID'])?intval($_GET['catID']):0);
	}
	if (isset($_GET['changeID']))
	{
	    $changeID=intval($_GET['changeID']);
		template_header();
		echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table
                template_pagebox_start("Add/change download-category", 890);
	
			echo '<br><font face="Verdana, Arial, Helvetica, sans-serif" size="2"><a href="download.php?main=1">Click here</a> to get back to the download page!<br><br>';
			download_change($changeID);
				
			echo '<br><br>
			
			<b>Add new item</b><font size="2"><br>
			

			<table cellspacing="0" cellpadding="0">
			<form action="download.php?adding=1&catID='.$changeID.'" method="POST" name="adding_statusID"><tr><td>
				<input type="text" name="name" maxlength="40" size="25">
			</td>

			<td>
				&nbsp;
			</td>

			<td>
				<input type="text" name="version" maxlength="20" size="5">
			</td>
			<td>
				&nbsp;
			</td>
			<td>
				<input type="text" name="description" maxlength="50" size="25">
			</td>
			<td>
				&nbsp;
			</td>
			<td>
				<input type="text" name="url" maxlength="150" size="40">
			</td>
			<td>
				&nbsp;&nbsp;<input type="submit" name="submit" value="Create item">
			</td>		
		</tr></form>
		</table><br>';
	
		template_pagebox_end();
		
		echo '</td></tr></table>';	// end of framespacing-table					
		template_end();
	}
}
/*
if (isset($_GET['changelog']) && ($_GET['changelog']==1))
{
		template_header();
	echo '
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr align="left" valign="top"> 
			<td width="24">
				&nbsp;
			</td>
		
			<td> 
				<font face="Verdana, Arial, Helvetica, sans-serif" size="7"><b>Changelog</b><font size="2"><br><br>';
	
				show_changelog($_GET['showID']);
				
				echo '</font>
	
			</td>
			<td width="50">
				&nbsp;
			</td>
		</tr>
	</table><br><br>';
	
		template_end();	
}
*/
if (isset($_GET['main']) && ($_GET['main']==1))
{

		template_header();
		echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table
	echo '
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="hidden" name="cmd" value="_s-xclick">
	<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!">
	<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">

	<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
	">
 &nbsp;<span class="support">Support DOSBox.</span>
		</form>';
		show_downloads(isset($user)?$user['priv']['download_management']:0);
				
		echo '</td></tr></table>';	// end of framespacing-table					
		template_end();
}
?>
