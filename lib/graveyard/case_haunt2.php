<?php
$string="%";
$name = httppost('name');
for ($x=0;$x<strlen($name);$x++){
	$string .= substr($name,$x,1)."%";
}
$sql = "SELECT login,name,level FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($string)."' AND locked=0 ORDER BY level,login";
$result = db_query($sql);
if (db_num_rows($result)<=0){
	output::doOutput("`\$%s`) could find no one who matched the name you gave him.",$deathoverlord);
}elseif(db_num_rows($result)>100){
	output::doOutput("`\$%s`) thinks you should narrow down the number of people you wish to haunt.",$deathoverlord);
	$search = translator::translate_inline("Search");
	rawoutput("<form action='graveyard.php?op=haunt2' method='POST'>");
	output::addnav("","graveyard.php?op=haunt2");
	output::doOutput("Who would you like to haunt? ");
	rawoutput("<input name='name' id='name'>");
	rawoutput("<input type='submit' class='button' value='$search'>");
	rawoutput("</form>");
	rawoutput("<script language='JavaScript'>document.getElementById('name').focus()</script>",true);
}else{
	output::doOutput("`\$%s`) will allow you to try to haunt these people:`n",$deathoverlord);
	$name = translator::translate_inline("Name");
	$lev = translator::translate_inline("Level");
	rawoutput("<table cellpadding='3' cellspacing='0' border='0'>");
	rawoutput("<tr class='trhead'><td>$name</td><td>$lev</td></tr>");
	for ($i=0;$i<db_num_rows($result);$i++){
		$row = db_fetch_assoc($result);
		rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td><a href='graveyard.php?op=haunt3&name=".HTMLEntities($row['login'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."'>");
		output_notl("%s", $row['name']);
		rawoutput("</a></td><td>");
		output_notl("%s", $row['level']);
		rawoutput("</td></tr>",true);
		output::addnav("","graveyard.php?op=haunt3&name=".HTMLEntities($row['login'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1")));
	}
	rawoutput("</table>",true);
}
output::addnav(array("Question `\$%s`0 about the worth of your soul",$deathoverlord),"graveyard.php?op=question");
$max = $session['user']['level'] * 5 + 50;
$favortoheal = round(10 * ($max-$session['user']['soulpoints'])/$max);
output::addnav(array("Restore Your Soul (%s favor)",$favortoheal),"graveyard.php?op=restore");
output::addnav("Places");
output::addnav("S?Land of the Shades","shades.php");
output::addnav("G?The Graveyard","graveyard.php");
output::addnav("M?Return to the Mausoleum","graveyard.php?op=enter");
