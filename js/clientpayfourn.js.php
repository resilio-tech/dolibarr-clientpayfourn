<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    clientpayfourn/js/clientpayfourn.js.php
 * \ingroup clientpayfourn
 * \brief   JavaScript file for module ClientPayFourn.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

global $langs, $db, $conf;

$langs->load("clientpayfourn@clientpayfourn");

$paydirectfourn = $langs->trans("ClientPayFournButton");
?>

/* Javascript library of module ClientPayFourn */

$(document).ready(function () {
	// if we aren't on the Tier page, don't do anything
	if (window.location.href.indexOf("/compta/facture/card.php") === -1) {
		return;
	}
	const id = window.location.href.split("id=")[1].split("&")[0];
	// if there are a button with "Valider" inside
	const disabled = $(".badge.badge-status1.badge-status").length <= 0;

	if (disabled) return;

	const last = $(".tabsAction").first();

	last.prepend(
		"<a href='/custom/clientpayfourn/clientpayfournindex.php?id=" + id + "' class='butAction'><?= $paydirectfourn ?></a>"
	);
});

$(document).ready(function () {
	if (window.location.href.indexOf("/compta/facture/card.php") === -1) {
		return;
	}
	const id = window.location.href.split("id=")[1].split("&")[0];

	$.get("/custom/clientpayfourn/clientpayfourncheck.php?clientid=" + id, function (data) {
		$(".fichehalfright").last().append(data);
	});
});

$(document).ready(function () {
	if (window.location.href.indexOf("/fourn/facture/card.php") === -1) {
		return;
	}
	const id = window.location.href.split("id=")[1].split("&")[0];

	$.get("/custom/clientpayfourn/clientpayfourncheck.php?fournid=" + id, function (data) {
		$(".fichehalfright").last().append(data);
	});
});
