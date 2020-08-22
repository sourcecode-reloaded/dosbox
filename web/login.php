<?php
// this src is written under the terms of the GPL-licence, see gpl.txt for futher details
	include("include/standard.inc.php");
	sstart();
	
	if (isset($_GET['destroy']) && $_GET['destroy']==1)
	{
		SESSION_DESTROY();
		session_unset();
		Header("Location: login.php");
	}
		
	if (isset($_GET['login'],$_POST['nickname'],$_POST['password']) && $_GET['login']==1)
	{
		if(isset($_POST['h-captcha-response']) && !empty($_POST['h-captcha-response'])) {
        		// get verify response
       	 		$data = array(
				'secret' => "0x0000000000000000000000000000000000000000",
				'response' => $_POST['h-captcha-response']
			);
			$verify = curl_init();
			curl_setopt($verify, CURLOPT_URL,   "https://hcaptcha.com/siteverify");
			curl_setopt($verify, CURLOPT_POST, true);
			curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
			$verifyResponse = curl_exec($verify);
			$responseData = json_decode($verifyResponse);

			if($responseData->success) {
				login($_POST['nickname'], $_POST['password']);
			} else { 
				Header("Location: login.php?failed=1");
			}
		} else {
			Header("Location: login.php?failed=1");
		}
	}	

	template_header();
	echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table		
	
	template_pagebox_start("Login", 550);			
	
	echo '<br>';
	if (isset($_GET['failed']) && $_GET['failed']==1)
		echo '<span class="bold red">The username/password you tried to use is incorrect or the captcha failed, please try again!</span>';


	if (!isset($_SESSION['userID']))
{

echo' <script src="https://www.hCaptcha.com/1/api.js" async defer></script>';
echo '	<form action="login.php?login=1" method="POST">
<div class="h-captcha" data-sitekey="10000000-ffff-ffff-ffff-000000000001" data-theme="dark"></div>
			Nickname:<br>
			<input name="nickname" type="text"><br><br>
			Password:<br>
			<input name="password" type="password"><br><br>

			<input type="submit" name="submit" value="Login">
			</form>
			<a href="register_account.php?main_form=1" target="_top">Register new account</a>
			
			<br><br>
			
			';


}	else
	{ if( isset($user) ) {
		if ($user['priv']['post_news']==1)
			echo '<a href="news.php?post_news=1">Post news item</a><br>';
		if ($user['priv']['user_management']==1)
			echo '<a href="usermanagement.php?show=1">User management</a><br>';
		if ($user['priv']['status_manage']==1)
			echo '<a href="versionadmin.php?main=1">Version management</a><br>';
//		if ($user['priv']['screen_manage']==1)
//			echo '<a href="screenshots.php?main=1&page=0">Add/Remove screenshots from archive</a><br>';
		
		if ($user['priv']['status_manage']==1 || $user['priv']['post_news']==1 || $user['priv']['user_management']==1)
			echo '<br>';
		
		echo '<a href="login.php?destroy=1">Click here</a> to logout this user ('.$user['nickname'].')<br><br>';
		}
	}
	echo '</font>';

		template_pagebox_end();
		echo '</td></tr></table>';	// end of framespacing-table					
		template_end();
?>
