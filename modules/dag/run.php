<?php
function dag_run_private(){
	require_once("modules/dag/misc_functions.php");
	global $session;
	if (http::httpget('manage')!="true"){
		page_header("Dag Durnick's Table");
		output::doOutput("<span style='color: #9900FF'>",true);
		output::doOutput("`c`bDag Durnick's Table`b`c");
	}else{
		dag_manage();
	}

	$op = http::httpget('op');

	output::addnav("Navigation");
	output::addnav("I?Return to the Inn","inn.php");
	if ($op != '')
		output::addnav("Talk to Dag Durnick", "runmodule.php?module=dag");

	if ($op=="list"){
		output::doOutput("Dag fishes a small leather bound book out from under his cloak, flips through it to a certain page and holds it up for you to see.");
		output::doOutput("\"`7Deese ain't the most recent figgers, I ain't just had time to get th' other numbers put in.`0\"`n`n");
		// ***ADDED***
		// By Andrew Senger
		// Added for new Bounty Code
		output::doOutput("`c`bThe Bounty List`b`c`n");
		$sql = "SELECT bountyid,amount,target,setter,setdate FROM " . db_prefix("bounty") . " WHERE status=0 AND setdate<='".date("Y-m-d H:i:s")."' ORDER BY bountyid ASC";
		$result = db_query($sql);
		rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
		$amount = translator::translate_inline("Amount");
		$level = translator::translate_inline("Level");
		$name = translator::translate_inline("Name");
		$loc = translator::translate_inline("Location");
		$sex = translator::translate_inline("Sex");
		$alive = translator::translate_inline("Alive");
		$last = translator::translate_inline("Last On");
		rawoutput("<tr class='trhead'><td><b>$amount</b></td><td><b>$level</b></td><td><b>$name</b></td><td><b>$loc</b></td><td><b>$sex</b></td><td><b>$alive</b></td><td><b>$last</b></td>");
		$listing = array();
		$totlist = 0;
		for($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			$amount = (int)$row['amount'];
			$sql = "SELECT name,alive,sex,level,laston,loggedin,lastip,location FROM " . db_prefix("accounts") . " WHERE acctid={$row['target']}";
			$result2 = db_query($sql);
			if (db_num_rows($result2) == 0) {
				/* this person has been deleted, clear bounties */
				$sql = "UPDATE " . db_prefix("bounty") . " SET status=1 WHERE target={$row['target']}";
				db_query($sql);
				continue;
			}
			$row2 = db_fetch_assoc($result2);
			$yesno = 0;
			for($j=0;$j<=$i;$j++){
				if(isset($listing[$j]) &&
						$listing[$j]['Name'] == $row2['name']) {
					$listing[$j]['Amount'] = $listing[$j]['Amount'] + $amount;
					$yesno = 1;
				}
			}

			if ($yesno==0) {
				$loggedin = (date("U")-strtotime($row2['laston'])<settings::getsetting("LOGINTIMEOUT",900) && $row2['loggedin']);
				$listing[] = array('Amount'=>$amount,'Level'=>$row2['level'],'Name'=>$row2['name'],'Location'=>$row2['location'],'Sex'=>$row2['sex'],'Alive'=>$row2['alive'],'LastOn'=>$row2['laston'], 'LoggedIn'=>$loggedin);
				$totlist = $totlist + 1;
			}
		}
		$sort = http::httpget("sort");
		if ($sort=="level")
			usort($listing, 'dag_sortbountieslevel');
		elseif ($sort != "")
			usort($listing, 'dag_sortbounties');
		else
			usort($listing, 'dag_sortbountieslevel');
		for($i=0;$i<$totlist;$i++) {
			rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>");
			output_notl("`^%s`0", $listing[$i]['Amount']);
			rawoutput("</td><td>");
			output_notl("`^%s`0", $listing[$i]['Level']);
			rawoutput("</td><td>");
			output_notl("`^%s`0", $listing[$i]['Name']);
			rawoutput("</td><td>");
			output::doOutput($listing[$i]['LoggedIn']?"`#Online`0":$listing[$i]['Location']);
			rawoutput("</td><td>");
			output::doOutput($listing[$i]['Sex']?"`!Female`0":"`!Male`0");
			rawoutput("</td><td>");
			output::doOutput($listing[$i]['Alive']?"`1Yes`0":"`4No`0");
			rawoutput("</td><td>");
			$laston = relativedate($listing[$i]['LastOn']);
			output_notl("%s", $laston);
			rawoutput("</td></tr>");
		}
		rawoutput("</table>");
		// ***END ADDING***
	}else if ($op=="addbounty"){
		if (get_module_pref("bounties") >= get_module_setting("maxbounties")) {
			output::doOutput("Dag gives you a piercing look.");
			output::doOutput("`7\"Ye be thinkin' I be an assassin or somewhat?  Ye already be placin' more than 'nuff bounties for t'day.  Now, be ye gone before I stick a bounty on yer head fer annoyin' me.\"`n`n");
		} else {
			$fee = get_module_setting("bountyfee");
			if ($fee < 0 || $fee > 100) {
				$fee = 10;
				set_module_setting("bountyfee",$fee);
			}
			$min = get_module_setting("bountymin");
			$max = get_module_setting("bountymax");
			output::doOutput("Dag Durnick glances up at you and adjusts the pipe in his mouth with his teeth.`n");
			output::doOutput("`7\"So, who ye be wantin' to place a hit on? Just so ye be knowing, they got to be legal to be killin', they got to be at least level %s, and they can't be having too much outstandin' bounty nor be getting hit too frequent like, so if they ain't be listed, they can't be contracted on!  We don't run no slaughterhouse here, we run a.....business.  Also, there be a %s%% listin' fee fer any hit ye be placin'.\"`n`n", get_module_setting("bountylevel"), get_module_setting("bountyfee"));
			rawoutput("<form action='runmodule.php?module=dag&op=finalize' method='POST'>");
			output::doOutput("`2Target: ");
			rawoutput("<input name='contractname'>");
			output_notl("`n");
			output::doOutput("`2Amount to Place: ");
			rawoutput("<input name='amount' id='amount' width='5'>");
			output_notl("`n`n");
			$final = translator::translate_inline("Finalize Contract");
			rawoutput("<input type='submit' class='button' value='$final'>");
			rawoutput("</form>");
			output::addnav("","runmodule.php?module=dag&op=finalize");
		}
	}elseif ($op=="finalize") {
		if (http::httpget('subfinal')==1){
			$sql = "SELECT acctid,name,login,level,locked,age,dragonkills,pk,experience FROM " . db_prefix("accounts") . " WHERE name='".addslashes(rawurldecode(stripslashes(httppost('contractname'))))."' AND locked=0";
		}else{
			$contractname = stripslashes(rawurldecode(httppost('contractname')));
			$name="%";
			for ($x=0;$x<strlen($contractname);$x++){
				$name.=substr($contractname,$x,1)."%";
			}
			$sql = "SELECT acctid,name,login,level,locked,age,dragonkills,pk,experience FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($name)."' AND locked=0";
		}
		$result = db_query($sql);
		if (db_num_rows($result) == 0) {
			output::doOutput("Dag Durnick sneers at you, `7\"There not be anyone I be knowin' of by that name.  Maybe ye should come back when ye got a real target in mind?\"");
		} elseif(db_num_rows($result) > 100) {
			output::doOutput("Dag Durnick scratches his head in puzzlement, `7\"Ye be describing near half th' town, ye fool?  Why don't ye be giving me a better name now?\"");
		} elseif(db_num_rows($result) > 1) {
			output::doOutput("Dag Durnick searches through his list for a moment, `7\"There be a couple of 'em that ye could be talkin' about.  Which one ye be meaning?\"`n");
			rawoutput("<form action='runmodule.php?module=dag&op=finalize&subfinal=1' method='POST'>");
			output::doOutput("`2Target: ");
			rawoutput("<select name='contractname'>");
			for ($i=0;$i<db_num_rows($result);$i++){
				$row = db_fetch_assoc($result);
				rawoutput("<option value=\"".rawurlencode($row['name'])."\">".full_sanitize($row['name'])."</option>");
			}
			rawoutput("</select>");
			output_notl("`n`n");
			$amount = httppost('amount');
			output::doOutput("`2Amount to Place: ");
			rawoutput("<input name='amount' id='amount' width='5' value='$amount'>");
			output_notl("`n`n");
			$final = translator::translate_inline("Finalize Contract");
			rawoutput("<input type='submit' class='button' value='$final'>");
			rawoutput("</form>");
			output::addnav("","runmodule.php?module=dag&op=finalize&subfinal=1");
		} else {
			// Now, we have just the one, so check it.
			$row  = db_fetch_assoc($result);
			if ($row['locked']) {
				output::doOutput("Dag Durnick sneers at you, `7\"There not be anyone I be knowin' of by that name.  Maybe ye should come back when ye got a real target in mind?\"");
			} elseif ($row['login'] == $session['user']['login']) {
				output::doOutput("Dag Durnick slaps his knee laughing uproariously, `7\"Ye be wanting to take out a contract on yerself?  I ain't be helping no suicider, now!\"");
			} elseif ($row['level'] < get_module_setting("bountylevel") ||
						($row['age'] < settings::getsetting("pvpimmunity",5) &&
						 $row['dragonkills'] == 0 && $row['pk'] == 0 &&
						 $row['experience'] < settings::getsetting("pvpminexp",1500))) {
				output::doOutput("Dag Durnick stares at you angrily, `7\"I told ye that I not be an assassin.  That ain't a target worthy of a bounty.  Now get outta me sight!\"");
			} else {
				// All good!
				$amt = abs((int)httppost('amount'));
				$min = get_module_setting("bountymin") * $row['level'];
				$max = get_module_setting("bountymax") * $row['level'];
				$fee = get_module_setting("bountyfee");
				$cost = round($amt*((100+$fee)/100), 0);
				$curbounty = 0;
				$sql = "SELECT sum(amount) AS total FROM " . db_prefix("bounty") . " WHERE status=0 AND target={$row['acctid']}";
				$result = db_query($sql);
				if (db_num_rows($result) > 0) {
					$nrow = db_fetch_assoc($result);
					$curbounty = $nrow['total'];
				}
				if ($amt < $min) {
					output::doOutput("Dag Durnick scowls, `7\"Ye think I be workin' for that pittance?  Be thinkin' again an come back when ye willing to spend some real coin.  That mark be needin' at least %s gold to be worth me time.\"", $min);
				} elseif ($session['user']['gold'] < $cost) {
					output::doOutput("Dag Durnick scowls, `7\"Ye don't be havin enough gold to be settin' that contract.  Wastin' my time like this, I aught to be puttin' a contract on YE instead!");
				} elseif ($amt + $curbounty > $max) {
					if ($curbounty) {
						output::doOutput("Dag looks down at the pile of coin and just leaves them there.");
						output::doOutput("`7\"I'll just be passin' on that contract.  That's way more'n `^%s`7 be worth and ye know it.  I ain't no durned assassin. A bounty o' %s already be on their head, what with the bounties I ain't figgered in to th' book already.  I might be willin' t'up it to %s, after me %s%% listin' fee of course\"`n`n",$row['name'], $curbounty, $max, $fee);
					} else {
						output::doOutput("Dag looks down at the pile of coin and just leaves them there.");
						output::doOutput("`7\"I'll just be passin' on that contract.  That's way more'n `^%s`7 be worth and ye know it.  I ain't no durned assassin.  I might be willin' t'let y' set one of %s, after me %s%% listin' fee of course\"`n`n", $row['name'], $max, $fee);
					}
				} else {
					output::doOutput("You slide the coins towards Dag Durnick, who deftly palms them from the table.");
					output::doOutput("`7\"I'll just be takin' me %s%% listin' fee offa the top.  The word be put out that ye be wantin' `^%s`7 taken care of. Be patient, and keep yer eyes on the news.\"`n`n", $fee, $row['name']);
					set_module_pref("bounties",get_module_pref("bounties")+1);
					$session['user']['gold']-=$cost;
					// ***ADDED***
					// By Andrew Senger
					// Adding for new Bounty Code
					$setdate = time();
					// random set date up to 4 hours in the future.
					$setdate += e_rand(0,14400);
					$sql = "INSERT INTO ". db_prefix("bounty") . " (amount, target, setter, setdate) VALUES ($amt, ".$row['acctid'].", ".(int)$session['user']['acctid'].", '".date("Y-m-d H:i:s",$setdate)."')";
					db_query($sql);
					// ***END ADD***
					debuglog("spent $cost to place a $amt bounty on {$row['name']}");
				}
			}
		}
	}else{
		output::doOutput("You stroll over to Dag Durnick, who doesn't even bother to look up at you.");
		output::doOutput("He takes a long pull on his pipe.`n");
		output::doOutput("`7\"Ye probably be wantin' to know if there's a price on yer head, ain't ye.\"`n`n");
		// ***ADDED***
		// By Andrew Senger
		// Adding for new Bounty Code
		$sql = "SELECT sum(amount) as total FROM " . db_prefix("bounty") . " WHERE status=0 AND setdate<='".date("Y-m-d H:i:s")."' AND target=".$session['user']['acctid'];
		$result = db_query($sql);
		$curbounty = 0;
		if (db_num_rows($result) != 0) {
			$row = db_fetch_assoc($result);
			$curbounty = $row['total'];
		}
		if ($curbounty == 0) {
			output::doOutput("\"`3Ye don't have no bounty on ya.  I suggest ye be keepin' it that way.\"");
		} else {
		output::doOutput("\"`3Well, it be lookin like ye have `^%s gold`3 on yer head currently. Ye might wanna be watchin yourself.\"", $curbounty);
		}
		// ***END ADD***
		output::addnav("Bounties");
		output::addnav("Check the Wanted List","runmodule.php?module=dag&op=list");
		output::addnav("Set a Bounty","runmodule.php?module=dag&op=addbounty");
	}
	modules::modulehook('dagnav');
	if ($op == "list") {
		output::addnav("Sort List");
		output::addnav("View by Bounty",
				"runmodule.php?module=dag&op=list&sort=bounty");
		output::addnav("View by Level", "runmodule.php?module=dag&op=list&sort=level");
	}
	rawoutput("</span>");
	page_footer();
}
