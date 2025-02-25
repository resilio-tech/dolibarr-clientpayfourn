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

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

global $db, $user, $conf, $langs;

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


$fourn_soc_id = 0;
$facturefourn = new FactureFournisseur($db);
var_dump(array("facturefourn_id", $facturefourn_id));
$facturefourn->fetch($facturefourn_id);
var_dump(array("facturefourn id", $facturefourn->id));
$thirdparty_seller = new Societe($db);
if (!empty($facturefourn_id)) {
	if (empty($facturefourn->id)) {
		setEventMessage("Société fournisseur non renseignée", 'errors');
	} else {
		$fourn_soc_id = $facturefourn->socid;
		$thirdparty_seller->fetch($fourn_soc_id);
	}
}

$factureClient = new Facture($db);
$factureClient->fetch($facture_id);
$client_soc_id = 0;
$mt = 0;
if ($factureClient && !empty($factureClient->id)) {
	$client_soc_id = $factureClient->socid;
	$mt = $factureClient->total_ttc;
}
$thirdparty_buyer = new Societe($db);
if (!$client_soc_id) {
	setEventMessage("Société client non renseignée", 'errors');
} else {
	$thirdparty_buyer->fetch($client_soc_id);
}

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
			global $db, $now, $mt, $facturefourn, $date;
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "clientpayfourn_linkclientpayfourn (fk_facture_client, fk_facture_fourn, datec)";
			$sql .= " VALUES (" . (int)$facture_id . ", " . (int)$facturefourn_id . ", '".date_format(date_create($date), 'Y-m-d')."')";
			$resql = $db->query($sql);
			if ($resql) {
				setEventMessage("Link created", 'mesgs');

				$paye = $mt == $facturefourn->total_ttc ? 1 : 0;
				$status = $paye ? 2 : 1;
				$sql_fourn = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET fk_statut = ".$status.", paye = ".$paye." WHERE rowid = " . (int)$facturefourn_id;
				$resql_fourn = $db->query($sql_fourn);
				if ($resql_fourn) {
					setEventMessage("Facture fournisseur changé", 'mesgs');
				} else {
					setEventMessage("Erreur lors du changement facture fournisseur", 'errors');
				}

				$sql_facture = "UPDATE " . MAIN_DB_PREFIX . "facture SET fk_statut = 2, paye = 1 WHERE rowid = " . (int)$facture_id;
				$resql_facture = $db->query($sql_facture);
				if ($resql_facture) {
					setEventMessage("Facture client changé", 'mesgs');
				} else {
					setEventMessage("Erreur lors du changement facture client", 'errors');
				}
// To delete
//				$sql_accountingfourn = " UPDATE ".MAIN_DB_PREFIX."facture_fourn_det";
//				$sql_accountingfourn .= " SET fk_code_ventilation = ".((int) $accounting_fourn);
//				$sql_accountingfourn .= " WHERE rowid = ".((int) $facturefourn_id);
//
//				$resql_accountingfourn = $db->query($sql_accountingfourn);
//				if ($resql_accountingfourn) {
//					setEventMessage("Compte comptable fournisseur changé", 'mesgs');
//				} else {
//					setEventMessage("Erreur lors du changement compte comptable fournisseur", 'errors');
//				}
//
//				$sql_accountingclient = " UPDATE ".MAIN_DB_PREFIX."facturedet";
//				$sql_accountingclient .= " SET fk_code_ventilation = ".((int) $accounting_client);
//				$sql_accountingclient .= " WHERE rowid = ".((int) $facture_id);
//
//				$resql_accountingclient = $db->query($sql_accountingclient);
//				if ($resql_accountingclient) {
//					setEventMessage("Compte comptable client changé", 'mesgs');
//				} else {
//					setEventMessage("Erreur lors du changement compte comptable client", 'errors');
//				}
//
//				header("Location: /fourn/facture/card.php?facid=" . $facturefourn_id);

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
				$action = '';
			} else {
				createLink($facture_id, $facturefourn_id);

				require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

				$paiementfourn = new PaiementFourn($db);
				$ref = $paiementfourn->getNextNumRef($fourn_soc_id);
				$sql_paimentfourn = "INSERT INTO " . MAIN_DB_PREFIX . "paiementfourn";
				$sql_paimentfourn .= " (ref, entity, tms, datec, datep, amount, multicurrency_amount, fk_user_author, fk_user_modif, fk_paiement, num_paiement, statut, fk_bank, note)";
				$sql_paimentfourn .= " VALUES ('" . $ref . "', " . $conf->entity . ", '" . $db->idate($now) . "', '" . $db->idate($now) . "', '" . $db->idate($now) . "', " . $mt . ", " . $factureClient->multicurrency_total_ttc . ", " . $user->id . ", " . $user->id . ", 0, '', 0, 0, 'Payé par Client ". $factureClient->ref ."')";

				$resql_paimentfourn = $db->query($sql_paimentfourn);
				if ($resql_paimentfourn) {
					setEventMessage("Paiement fournisseur créé", 'mesgs');
					$paimentfourn = $db->last_insert_id( MAIN_DB_PREFIX . 'paiementfourn' );

					$sql_payment = "INSERT INTO " . MAIN_DB_PREFIX . "paiementfourn_facturefourn";
					$sql_payment .= " (fk_paiementfourn, fk_facturefourn, amount, multicurrency_code, multicurrency_tx, multicurrency_amount)";
					$sql_payment .= " VALUES (".$paimentfourn.", ".$facturefourn_id.", ".$mt.", '".$factureClient->multicurrency_code."', $factureClient->multicurrency_tx, $factureClient->multicurrency_total_ttc)";

					$resql_payment = $db->query($sql_payment);
					if ($resql_payment) {
						setEventMessage("Paiement créé", 'mesgs');
					} else {
						$db->rollback();
						setEventMessage("Erreur lors de la création du paiement", 'errors');
						var_dump($sql_payment);
						var_dump($db->lasterror());
						$action = '';
					}
				} else {
					$db->rollback();
					setEventMessage("Erreur lors de la création du paiement fournisseur", 'errors');
					var_dump($sql_paimentfourn);
					var_dump($db->lasterror());
					$action = '';
				}
			}
		}
	}
}

require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

$account_sell = new AccountingAccount($db);
$account_sell->fetch(getDolGlobalString('CLIENTPAYFOURN_FOURN_ACCOUNTING'));
$account_buy = new AccountingAccount($db);
$account_buy->fetch(getDolGlobalString('CLIENTPAYFOURN_CLIENT_ACCOUNTING'));

if ($action && $action == 'compta') {
	if (!$facture_id || !$facturefourn_id) {
		if (!$facture_id) {
			setEventMessage("Facture client non renseignée", 'errors');
		}
		if (!$facturefourn_id) {
			setEventMessage("Facture fournisseur non renseignée", 'errors');
		}
		var_dump(array("fourn_soc_id", $fourn_soc_id));
		var_dump(array("client_soc_id", $client_soc_id));
	} else if ($facture_id && $facturefourn_id) {
		if (!$fourn_soc_id || !$client_soc_id) {
			var_dump(array("fourn_soc_id", $fourn_soc_id));
			var_dump(array("client_soc_id", $client_soc_id));
			$action = 'link';
		} else {
			function createBookKeeping($f_id, $account, $thirdparty, $accounting, $mt, $j)
			{
				global $db, $now, $journal, $journal_label, $langs, $user, $date, $conf, $action;
				$accountingjournalstatic = new AccountingJournal($db);
				$accountingjournalstatic->fetch($j);
				$journal = $accountingjournalstatic->code;
				$journal_label = $accountingjournalstatic->label;

				$bookkeeping = new BookKeeping($db);
				$bookkeeping->doc_date = date_create($date)->getTimestamp();
				$bookkeeping->date_lim_reglement = date_create($date)->getTimestamp();
				$bookkeeping->doc_ref = 'SPEC';
				$bookkeeping->date_creation = $now;
				$bookkeeping->doc_type = 'special_clientpayfourn';
				$bookkeeping->fk_doc = $f_id;
				$bookkeeping->fk_docdet = 0;
				$bookkeeping->thirdparty_code = $mt < 0 ? $thirdparty->code_fournisseur : $thirdparty->code_client;

				$bookkeeping->subledger_account = $mt < 0 ? $thirdparty->accountancy_code_sell : $thirdparty->accountancy_code_buy;
				$bookkeeping->subledger_label = $thirdparty->name;

				$bookkeeping->numero_compte = $accounting;
				$bookkeeping->label_compte = $account->label;

	//			$bookkeeping_seller->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RetainedWarranty");
				$bookkeeping->montant = $mt;
				$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
				$bookkeeping->debit = ($mt >= 0) ? $mt : 0;
				$bookkeeping->credit = ($mt < 0) ? -$mt : 0;
				$bookkeeping->code_journal = $journal;
				$bookkeeping->journal_label = $langs->transnoentities($journal_label);
				$bookkeeping->fk_user_author = $user->id;
				$bookkeeping->entity = $conf->entity;

				return $bookkeeping->create($user);
			}

			$compta_1 = createBookKeeping($facturefourn_id, $account_sell, $thirdparty_seller, $accounting_fourn, $mt, 1);
			$compta_2 = createBookKeeping($facture_id, $account_buy, $thirdparty_buyer, $accounting_client, -$mt, 2);
			if ($compta_1 != 0 || $compta_2 != 0) {
				$db->rollback();
				setEventMessage("Erreur lors de la création d'écritures comptable", 'errors');
				var_dump(array("compta_1", $compta_1));
				var_dump(array("compta_2", $compta_2));
				var_dump($db->lasterror());
				$action = 'link';
			} else {
				$db->commit();
				setEventMessage("Ecritures comptable créées", 'mesgs');
				header("Location: /fourn/facture/card.php?facid=" . $facturefourn_id);
			}
		}
	}
}

$form = new Form($db);
$formfile = new FormFile($db);
$form_accounting = new FormAccounting($db);

llxHeader("", $langs->trans("ClientPayFournArea"), '', '', 0, 0, '', '', '', 'mod-clientpayfourn page-index');

print load_fiche_titre($langs->trans("ClientPayFournArea"), '', 'clientpayfourn.png@clientpayfourn');

print '<div class="fichecenter">';

if ($action && $action == 'link') {
	print '<form name="form" action="' . $_SERVER["PHP_SELF"] . '?id=' . $facture_id . '" method="post">';

	print '<h2>' . $langs->trans("ClientPayFournCompta") . '</h2>';

	print '<table style="width: 100%;">';
	print '<tr>';
	print '<th>'. $langs->trans("Date"). '</th>';
	print '<th>'. $langs->trans("Ref"). '</th>';
	print '<th>'. $langs->trans("Libelle"). '</th>';
	print '<th>'. $langs->trans("Account"). '</th>';
	print '<th>'. $langs->trans("Débit"). '</th>';
	print '<th>'. $langs->trans("Crédit"). '</th>';
	print '</tr>';

	function printCompta($label, $soc, $mt)
	{
		global $date, $db;
		print '<tr>';
		print '<td>' . date_format(date_create($date), 'Y-m-d') . '</td>';
		print '<td>SPEC</td>';
		print '<td>' . $label . '</td>';
		print '<td>' . $soc . '</td>';
		print '<td>' . (($mt >= 0) ? $mt . "" : 0) . '</td>';
		print '<td>' . (($mt < 0) ? -$mt . "" : 0) . '</td>';
		print '</tr>';
	}
	printCompta($account_sell->label, $thirdparty_seller->name, $mt);
	printCompta($account_buy->label, $thirdparty_buyer->name, -$mt);

	print '</table>';

	print '<input type="submit" class="button" name="submit" value="' . $langs->trans("ClientPayFournValidate") . '">';

	print '<input type="hidden" name="facturefourn_id" value="' . $facturefourn_id . '">';
	print '<input type="hidden" name="facture_id" value="' . $facture_id . '">';
	print '<input type="hidden" name="accounting_fourn" value="' . $accounting_fourn . '">';
	print '<input type="hidden" name="accounting_client" value="' . $accounting_client . '">';

	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="compta">';
	print '<input type="hidden" name="id" value="' . $facture_id . '">';

	print '</form>';
} else {
	print '<form name="form" action="' . $_SERVER["PHP_SELF"] . '?id=' . $facture_id . '" method="post">';
	print '<table>';

	print '<tr>';
	print '<td>';
	print '<b>' . $langs->trans("ClientPayFournFournisseur") . '</b> ' . $langs->trans("ClientPayFournFournisseurOptional");
	print '</td>';
	print '<td>';
	print $form->select_company($socid, 'socid', '', 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print '<b>' . $langs->trans("ClientPayFournFacture") . '</b>';
	print '</td>';
	print '<td>';

	$sql = "SELECT f.rowid, f.ref, f.datef, f.fk_soc as socid, f.multicurrency_code as code, f.multicurrency_total_ttc as amount FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
	$sql .= " WHERE f.entity IN (" . getEntity('facture_fourn') . ")";
	if ($socid) $sql .= " AND f.fk_soc = " . ((int)$socid);
	$sql .= " ORDER BY f.datef DESC";
	$resql = $db->query($sql);
	$list = array();
	$list_select = array();
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$list_select[$obj->rowid] = $obj->ref . ' - ' . dol_print_date($db->jdate($obj->datef), 'day') . ' - ' . price($obj->amount, 0, $conf->global->MAIN_MONNAIE) . ' ' . $obj->code;
		}
		$db->free($resql);
	}

	print $form->selectarray('facturefourn_id', $list_select, $facture_id, 1);

	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print '<b>' . $langs->trans("ClientPayFournFactureClientCompta") . '</b>';
	print '</td>';
	print '<td>';
	$client_code = isset($conf->global->CLIENTPAYFOURN_CLIENT_ACCOUNTING) ? $conf->global->CLIENTPAYFOURN_CLIENT_ACCOUNTING : 0;
	print $form_accounting->select_account($client_code, 'accounting_client', 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print '<b>' . $langs->trans("ClientPayFournFactureFournCompta") . '</b>';
	print '</td>';
	print '<td>';
	$fourn_code = isset($conf->global->CLIENTPAYFOURN_FOURN_ACCOUNTING) ? $conf->global->CLIENTPAYFOURN_FOURN_ACCOUNTING : 0;
	print $form_accounting->select_account($fourn_code, 'accounting_fourn', 1);
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	print '<b>' . $langs->trans("Date") . '</b>';
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
	print '<input type="submit" class="button" name="action" value="' . $langs->trans("ClientPayFournButtonLink") . '">';
	print '</td>';
	print '</tr>';

	print '</table>';

	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="link">';
	print '<input type="hidden" name="id" value="' . $facture_id . '">';

	print '</form>';

	print '<script type="text/javascript">';
	print '$(document).ready(function() {';
	print '	$("#socid").change(function() {';
	print '		window.location.href = "/custom/clientpayfourn/clientpayfournindex.php?id=' . $facture_id . '&socid=" + $("#socid").val();';
	print '	});';
	print '});';
	print '</script>';
}


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

print '</div>';

// End of page
llxFooter();
$db->close();
