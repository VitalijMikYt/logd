<?php
// translator ready
// addnews ready
// mail ready

//put these outside the function since they're used by scripts outside of
//this function.
$pvptime = settings::getsetting("pvptimeout",600);
$pvptimeout = date("Y-m-d H:i:s",strtotime("-$pvptime seconds"));

function pvplist($location=false,$link=false,$extra=false,$sql=false){
	global $session,$pvptime,$pvptimeout;

	if ($location===false)
		$location = $session['user']['location'];
	if ($link===false) {
		$link = basename($_SERVER['SCRIPT_NAME']);
	}
	if ($extra === false) {
		$extra = "?act=attack";
	}

	$days = settings::getsetting("pvpimmunity", 5);
	$exp = settings::getsetting("pvpminexp", 1500);
	$clanrankcolors=array("`!","`#","`^","`&", "`\$");

	if ($sql === false) {
		$lev1 = $session['user']['level']-1;
		$lev2 = $session['user']['level']+2;
		$last = date("Y-m-d H:i:s",
				strtotime("-".settings::getsetting("LOGINTIMEOUT", 900)." sec"));
		$id = $session['user']['acctid'];
		$loc = addslashes($location);

		$sql = "SELECT acctid, name, alive, location, sex, level, laston, " .
			"loggedin, login, pvpflag, clanshort, clanrank, dragonkills, " .
			db_prefix("accounts") . ".clanid FROM " .
			db_prefix("accounts") . " LEFT JOIN " .
			db_prefix("clans") . " ON " . db_prefix("clans") . ".clanid=" .
			db_prefix("accounts") . ".clanid WHERE (locked=0) " .
			"AND (slaydragon=0) AND " .
			"(age>$days OR dragonkills>0 OR pk>0 OR experience>$exp) " .
			"AND (level>=$lev1 AND level<=$lev2) AND (alive=1) " .
			"AND (laston<'$last' OR loggedin=0) AND (acctid<>$id) " .
			"ORDER BY location='$loc' DESC, location, level DESC, " .
			"experience DESC, dragonkills DESC";
	}

	$result = db_query($sql);

	$pvp = array();
	while($row = db_fetch_assoc($result)) {
		$pvp[] = $row;
	}

	$pvp = modules::modulehook("pvpmodifytargets", $pvp);

	translator::tlschema("pvp");
	$n = translator::translate_inline("Name");
	$l = translator::translate_inline("Level");
	$loc = translator::translate_inline("Location");
	$ops = translator::translate_inline("Ops");
	$bio = translator::translate_inline("Bio");
	$att = translator::translate_inline("Attack");

	rawoutput("<table border='0' cellpadding='3' cellspacing='0'>");
	rawoutput("<tr class='trhead'><td>$n</td><td>$l</td><td>$loc</td><td>$ops</td></tr>");
	$loc_counts = array();
	$num = count($pvp);
	$j = 0;
	for ($i=0;$i<$num;$i++){
		$row = $pvp[$i];

		if (isset($row['invalid']) && $row['invalid']) continue;
		if (!isset($loc_counts[$row['location']]))
			$loc_counts[$row['location']] = 0;
		$loc_counts[$row['location']]++;
		if ($row['location'] != $location) continue;
		$j++;
		$biolink="bio.php?char=".$row['acctid']."&ret=".urlencode($_SERVER['REQUEST_URI']);
		output::addnav("", $biolink);
		rawoutput("<tr class='".($j%2?"trlight":"trdark")."'>");
		rawoutput("<td>");
		if ($row['clanshort']>"" && $row['clanrank'] > CLAN_APPLICANT) {
			output_notl("%s&lt;`2%s%s&gt;`0 ",
					$clanrankcolors[$row['clanrank']], $row['clanshort'],
					$clanrankcolors[$row['clanrank']], true);
		}
		output_notl("`@%s`0", $row['name']);
		rawoutput("</td>");
		rawoutput("<td>");
		output_notl("%s", $row['level']);
		rawoutput("</td>");
		rawoutput("<td>");
		output_notl("%s", $row['location']);
		rawoutput("</td>");
		rawoutput("<td>[ <a href='$biolink'>$bio</a> | ");
		if($row['pvpflag']>$pvptimeout){
			output::doOutput("`i(Attacked too recently)`i");
		}elseif ($location!=$row['location']){
			output::doOutput("`i(Can't reach them from here)`i");
		}else{
			rawoutput("<a href='$link$extra&name=".$row['acctid']."'>$att</a>");
			output::addnav("","$link$extra&name=".$row['acctid']);
		}
		rawoutput(" ]</td>");
		rawoutput("</tr>");
	}

	if (!isset($loc_counts[$location]) || $loc_counts[$location]==0){
		$noone = translator::translate_inline("`iThere are no available targets.`i");
		output_notl("<tr><td align='center' colspan='4'>$noone</td></tr>", true);
	}
	rawoutput("</table>",true);

	if ($num != 0 && (!isset($loc_counts[$location]) ||
				$loc_counts[$location] != $num)) {
		output::doOutput("`n`n`&As you listen to different people around you talking, you glean the following additional information:`n");
		foreach ($loc_counts as $loc=>$count) {
			if ($loc == $location) continue;
			$args = modules::modulehook("pvpcount", array('count'=>$count,'loc'=>$loc));
			if (isset($args['handled']) && $args['handled']) continue;
			if ($count == 1) {
			output::doOutput("`&There is `^%s`& person sleeping in %s whom you might find interesting.`0`n", $count, $loc);
			} else {
			output::doOutput("`&There are `^%s`& people sleeping in %s whom you might find interesting.`0`n", $count, $loc);
			}
		}
	}
	translator::tlschema();
}
