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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("clientpayfourn@clientpayfourn"));

$action = GETPOST('action', 'aZ09');
$facture_id = GETPOST('id', 'int');
$facturefourn_id = GETPOST('facturefourn_id', 'int');
$accounting_fourn = GETPOST('accounting_fourn', 'int');
$accounting_client = GETPOST('accounting_client', 'int');
$date = GETPOST('date', 'int');
$date_day = GETPOST('dateday', 'int');
$date_month = GETPOST('datemonth', 'int');
$date_year = GETPOST('dateyear', 'int');
$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');

if ($action && $action == 'link') {
	if (!$facture_id || !$facturefourn_id) {
		if (!$facture_id) {
			setEventMessage("Facture client non renseignée", 'errors');
		}
		if (!$facturefourn_id) {
			setEventMessage("Facture fournisseur non renseignée", 'errors');
		}
	} else if ($facture_id && $facturefourn_id) {
		function createLink($facture_id, $facturefourn_id)
		{
			global $db, $accounting_fourn, $accounting_client, $date;
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "clientpayfourn_linkclientpayfourn (fk_facture_client, fk_facture_fourn, datec)";
			$sql .= " VALUES (" . (int)$facture_id . ", " . (int)$facturefourn_id . ", '".date_format(date_create($date), 'Y-m-d')."')";
			$resql = $db->query($sql);
			if ($resql) {
				setEventMessage("Link created", 'mesgs');

				$sql_fourn = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET fk_statut = 2 WHERE rowid = " . (int)$facturefourn_id;
				$resql_fourn = $db->query($sql_fourn);
				if ($resql_fourn) {
					setEventMessage("Facture fournisseur changé", 'mesgs');
				} else {
					setEventMessage("Erreur lors du changement facture fournisseur", 'errors');
				}

				$sql_facture = "UPDATE " . MAIN_DB_PREFIX . "facture SET fk_statut = 2 WHERE rowid = " . (int)$facture_id;
				$resql_facture = $db->query($sql_facture);
				if ($resql_facture) {
					setEventMessage("Facture client changé", 'mesgs');
				} else {
					setEventMessage("Erreur lors du changement facture client", 'errors');
				}

				$sql_accountingfourn = " UPDATE ".MAIN_DB_PREFIX."facture_fourn_det";
				$sql_accountingfourn .= " SET fk_code_ventilation = ".((int) $accounting_fourn);
				$sql_accountingfourn .= " WHERE rowid = ".((int) $facturefourn_id);

				$resql_accountingfourn = $db->query($sql_accountingfourn);
				if ($resql_accountingfourn) {
					setEventMessage("Compte comptable fournisseur changé", 'mesgs');
				} else {
					setEventMessage("Erreur lors du changement compte comptable fournisseur", 'errors');
				}

				$sql_accountingclient = " UPDATE ".MAIN_DB_PREFIX."facturedet";
				$sql_accountingclient .= " SET fk_code_ventilation = ".((int) $accounting_client);
				$sql_accountingclient .= " WHERE rowid = ".((int) $facture_id);

				$resql_accountingclient = $db->query($sql_accountingclient);
				if ($resql_accountingclient) {
					setEventMessage("Compte comptable client changé", 'mesgs');
				} else {
					setEventMessage("Erreur lors du changement compte comptable client", 'errors');
				}

				header("Location: /fourn/facture/card.php?facid=" . $facturefourn_id);
			} else {
				setEventMessage("Erreur lors de la création du lien", 'errors');
			}
		}

		$sql = "SELECT fk_facture_client, fk_facture_fourn FROM " . MAIN_DB_PREFIX . "clientpayfourn_linkclientpayfourn";
		$sql .= " WHERE fk_facture_client = " . (int)$facture_id . " AND fk_facture_fourn = " . (int)$facturefourn_id;
		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				setEventMessage("Lien déjà existant", 'warning');
			} else {
				createLink($facture_id, $facturefourn_id);
			}
		}
	}
}

$form = new Form($db);
$formfile = new FormFile($db);
$form_accounting = new FormAccounting($db);

llxHeader("", $langs->trans("ClientPayFournArea"), '', '', 0, 0, '', '', '', 'mod-clientpayfourn page-index');

print load_fiche_titre($langs->trans("ClientPayFournArea"), '', 'clientpayfourn.png@clientpayfourn');

print '<div class="fichecenter"><div class="fichethirdleft">';

print '<form name="form" action="'.$_SERVER["PHP_SELF"].'?id='.$facture_id.'" method="post">';
print '<table>';

print '<tr>';
print '<td>';
print '<b>'.$langs->trans("ClientPayFournFournisseur").'</b> '.$langs->trans("ClientPayFournFournisseurOptional");
print '</td>';
print '<td>';
print $form->select_company($socid, 'socid', '', 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print '<b>'.$langs->trans("ClientPayFournFacture").'</b>';
print '</td>';
print '<td>';

$sql = "SELECT f.rowid, f.ref, f.datef, f.fk_soc as socid, f.multicurrency_code as code, f.multicurrency_total_ttc as amount FROM ".MAIN_DB_PREFIX."facture_fourn as f";
$sql.= " WHERE f.entity IN (".getEntity('facture_fourn').")";
if ($socid)	$sql.= " AND f.fk_soc = ".((int) $socid);
$sql.= " ORDER BY f.datef DESC";
$resql = $db->query($sql);
$list = array();
$list_select = array();
if ($resql)
{
	while ($obj = $db->fetch_object($resql))
	{
		$list_select[$obj->rowid] = $obj->ref.' - '.dol_print_date($db->jdate($obj->datef), 'day') . ' - ' . price($obj->amount, 0, $conf->global->MAIN_MONNAIE) . ' ' . $obj->code;
	}
	$db->free($resql);
}

print $form->selectarray('facturefourn_id', $list_select, $facture_id, 1);

print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print '<b>'.$langs->trans("ClientPayFournFactureClientCompta").'</b>';
print '</td>';
print '<td>';
$client_code = isset($conf->global->CLIENTPAYFOURN_CLIENT_ACCOUNTING) ? $conf->global->CLIENTPAYFOURN_CLIENT_ACCOUNTING : 0;
print $form_accounting->select_account($client_code, 'accounting_client', 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print '<b>'.$langs->trans("ClientPayFournFactureFournCompta").'</b>';
print '</td>';
print '<td>';
$fourn_code = isset($conf->global->CLIENTPAYFOURN_FOURN_ACCOUNTING) ? $conf->global->CLIENTPAYFOURN_FOURN_ACCOUNTING : 0;
print $form_accounting->select_account($fourn_code, 'accounting_fourn', 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print '<b>'.$langs->trans("Date").'</b>';
print '</td>';
print '<td>';
print $form->selectDate($now, 'date');
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print '</td>';
print '<td>';
// Button send
print '<input type="submit" class="button" name="action" value="'.$langs->trans("ClientPayFournButtonLink").'">';
print '</td>';
print '</tr>';

print '</table>';

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="link">';
print '<input type="hidden" name="id" value="'.$facture_id.'">';

print '</form>';

print '<script type="text/javascript">';
print '$(document).ready(function() {';
print '	$("#socid").change(function() {';
print '		window.location.href = "/custom/clientpayfourn/clientpayfournindex.php?id=' . $facture_id . '&socid=" + $("#socid").val();';
print '	});';
print '});';
print '</script>';

print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

print '</div></div>';

// End of page
llxFooter();
$db->close();
