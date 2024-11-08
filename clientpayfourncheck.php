<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       clientpayfourn/clientpayfournindex.php
 *	\ingroup    clientpayfourn
 *	\brief      Home page of clientpayfourn top menu
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
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("clientpayfourn@clientpayfourn"));

$action = GETPOST('action', 'aZ09');
$clientid = GETPOST('clientid', 'int');
$fournid = GETPOST('fournid', 'int');

$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$sql = "";

if ($clientid > 0) {
	$sql .= "SELECT ff.* FROM ".MAIN_DB_PREFIX."facture_fourn AS ff";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."clientpayfourn_linkclientpayfourn AS cpf";
	$sql .= " ON ff.rowid = cpf.fk_facture_fourn";
	$sql .= " WHERE fk_facture_client = ".$clientid;
} else if ($fournid > 0) {
	$sql .= "SELECT f.* FROM ".MAIN_DB_PREFIX."facture AS f";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."clientpayfourn_linkclientpayfourn AS cpf";
	$sql .= " ON f.rowid = cpf.fk_facture_client";
	$sql .= " WHERE fk_facture_fourn = ".$fournid;
}

$results = $db->query($sql);
if ($results === false) {
	dol_print_error($db);
	exit;
}

print '<table class="centpercent notopnoleftnoright table-fiche-title">';
print '<tr class="titre">';
print '<td class="nobordernopadding valignmiddle col-title">';
print '<div class="titre inline-block">';
if ($clientid > 0) {
	print $langs->trans("ClientPayFournFactureFournLinked");
} else if ($fournid > 0) {
	print $langs->trans("ClientPayFournFactureClientLinked");
}
print '</div>';
print '</td>';
print '</tr>';
print '</table>';

print '<div class="div-table-responsive-no-min">';

print '<table class="centpercent noborder">';
print '<tr class="liste_titre">';
print '<th class="wrapcolumntitle liste_titre" title="Label">Libellé</th>';
print '<th class="wrapcolumntitle center liste_titre_sel" title="Date">Date</th>';
print '<th class="wrapcolumntitle liste_titre" title="Amount">Montant</th>';
print '</tr>';

$n = $db->num_rows($results);
if ($n == 0) {
	print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">Aucun</span></td></tr>';
} else {
	while ($n > 0) {
		$obj = $db->fetch_object($results);
		$n--;

		print '<tr class="oddeven">';
		print '<td>';
		if ($clientid > 0) {
			print '<a href="'.dol_buildpath('/fourn/facture/card.php', 1).'?facid='.$obj->rowid.'">'.$obj->ref.'</a>';
		} else if ($fournid > 0) {
			print '<a href="'.dol_buildpath('/compta/facture/card.php', 1).'?facid='.$obj->rowid.'">'.$obj->ref.'</a>';
		}
		print '</td>';
		print '<td class="nowrap">'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';
		print '<td class="nowrap">'.price($obj->total_ttc).'</td>';
	}
}

print '</table>';

print '</div>';
$db->close();
