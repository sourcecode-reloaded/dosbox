<?php
// this src is written under the terms of the GPL-licence, see gpl.txt for futher details
	include("include/standard.inc.php");
	sstart();
	template_header();
	echo '<br><table width="100%"><tr><td width="14">&nbsp;</td><td>';// start of framespacing-table

template_pagebox_start("DOSBox related links", 900);
			echo '<ul>
			<li>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://www.sourceforge.net/projects/dosbox" target="_blank">Sourceforge</a> - DOSBox project page,  submit bugs and patches here. 
			</font></li>
			<li>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://vogons.zetafleet.com" target="_blank">VOGONS</a> - Official DOSBox Forum, look here for solutions for your problems.
			</font></li>
			<li>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://pain.scene.org/service_dosbox.php" target="_blank">pain.scene.org</a> - Unofficial DOSBox Demo Compatibility List.
			</font></li>							    
			<li>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://cvscompile.aep-emu.de/dosbox.htm" target="_blank">cvscompile.aep-emu.de</a> - CVS builds kindly provided by the people of AEP-Emulation.
			</font></li>
			</ul>';
template_pagebox_end();
										    
template_pagebox_start("General Emulation links", 900);
			echo '<ul>
			<li>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://www.zophar.net/" target="_blank">Zophar\'s Domain</a> - Great emulation news site with a huge list of emulators for many different systems and my site is hosted there.
			</font>
			</li>
			<li><font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://www.retrogames.com/" target="_blank">Retrogames</a> - Retrogames, your one stop emulation site. That should explain it enough.
			</font></li>
			<li><font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://www.emu-france.com/" target="_blank">Emu-France</a> - a good french source for your daily emulation news.
			</font></li>
			</ul>';
template_pagebox_end();

template_pagebox_start("PC/DOS/SB Emulation links", 900);			
			echo '<ul>
			<li><font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://ntvdm.cjb.net/" target="_blank">VDMSound</a> - Great tool by Vlad R. that gives you Soundblaster emulation under Windows NT/2K/XP.
			</font></li>
		        <li><font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://bochs.sourceforge.net/" target="_blank">Bochs IA-32 Emulator</a> - Bochs emulates a full x86 based pc. Unlike DOSBox that tries to mainly emulate dos programs.
			</font></li>
                        <li><font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://www.eliel.com/dodge.html" target="_blank">DodGE</a> - DOS Emulator for Win32 by Joel McIntyre, comes with a great GUI.
			</font></li>
                        <li><font face="Verdana, Arial, Helvetica, sans-serif" size="2">
			<a href="http://www.dosemu.org/" target="_blank">DOSEMU</a> - DOS Emulator for Linux, great tool to run just about any dos application under linux.
			</font></li>
			</ul>';
template_pagebox_end();
	
	echo '</td></tr></table>';	// end of framespacing-table
	
	template_end();
?>
