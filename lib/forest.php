<?php
// addnews ready
// translator ready
// mail ready
require_once("lib/villagenav.php");

function forest($noshowmessage=false) {
	global $session,$playermount;
	translator::tlschema("forest");
//	mass_module_prepare(array("forest", "validforestloc"));
	output::addnav("Heal");
	output::addnav("H?Healer's Hut","healer.php");
	output::addnav("Fight");
	output::addnav("L?Look for Something to Kill","forest.php?op=search");
	if ($session['user']['level']>1)
		output::addnav("S?Go Slumming","forest.php?op=search&type=slum");
	output::addnav("T?Go Thrillseeking","forest.php?op=search&type=thrill");
	if (settings::getsetting("suicide", 0)) {
		if (settings::getsetting("suicidedk", 10) <= $session['user']['dragonkills']) {
			output::addnav("*?Search `\$Suicidally`0", "forest.php?op=search&type=suicide");
		}
	}
	if ($session['user']['level']>=15  && $session['user']['seendragon']==0){
		// Only put the green dragon link if we are a location which
		// should have a forest.   Don't even ask how we got into a forest()
		// call if we shouldn't have one.   There is at least one way via
		// a superuser link, but it shouldn't happen otherwise.. We just
		// want to make sure however.
		$isforest = 0;
		$vloc = modules::modulehook('validforestloc', array());
		foreach($vloc as $i=>$l) {
			if ($session['user']['location'] == $i) {
				$isforest = 1;
				break;
			}
		}
		if ($isforest || count($vloc)==0) {
			output::addnav("G?`@Seek Out the Green Dragon","forest.php?op=dragon");
		}
	}
	output::addnav("Other");
	villagenav();
	if ($noshowmessage!=true){
		output::doOutput("`c`7`bThe Forest`b`0`c");
		output::doOutput("The Forest, home to evil creatures and evildoers of all sorts.`n`n");
		output::doOutput("The thick foliage of the forest restricts your view to only a few yards in most places.");
		output::doOutput("The paths would be imperceptible except for your trained eye.");
		output::doOutput("You move as silently as a soft breeze across the thick moss covering the ground, wary to avoid stepping on a twig or any of the numerous pieces of bleached bone that populate the forest floor, lest you betray your presence to one of the vile beasts that wander the forest.`n");
		modules::modulehook("forest-desc");
	}
	modules::modulehook("forest", array());
	module_display_events("forest", "forest.php");
	translator::tlschema();
}
