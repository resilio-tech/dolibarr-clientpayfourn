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

require_once DOL_DOCUMENT_ROOT.'/custom/clientpayfourn/lib/clientpayfourn.lib.php';

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

global $db, $user, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("clientpayfourn@clientpayfourn"));

$action = GETPOST('action', 'aZ09');
$facture_id = GETPOST('id', 'int');
$supplier_invoice_id = GETPOST('supplier_invoice_id', 'int');
$accounting_fourn = GETPOST('accounting_fourn', 'int');
$accounting_client = GETPOST('accounting_client', 'int');
$amount = GETPOST('amount', 'float');
$date = GETPOST('date', 'int');
$date_day = GETPOST('dateday', 'int');
$date_month = GETPOST('datemonth', 'int');
$date_year = GETPOST('dateyear', 'int');
$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');

$fourn_soc_id = 0;
$supplier_invoice = new FactureFournisseur($db);
$supplier_invoice->fetch($supplier_invoice_id);
$thirdparty_supplier = new Societe($db);
if (!empty($supplier_invoice_id)) {
	if (empty($supplier_invoice->id)) {
		setEventMessage($langs->trans("CPF_SupplierInvoiceUndefined"), 'errors');
	} else {
		$fourn_soc_id = $supplier_invoice->socid;
		$thirdparty_supplier->fetch($fourn_soc_id);
	}
}

$client_invoice = new Facture($db);
$client_invoice->fetch($facture_id);
$client_soc_id = 0;
$amountClient = 0;
if ($client_invoice && !empty($client_invoice->id)) {
	$client_soc_id = $client_invoice->socid;
	$amountClient = $client_invoice->total_ttc;
}
$thirdparty_customer = new Societe($db);
if (!$client_soc_id) {
	setEventMessage($langs->trans("CPF_CustomerUndefined"), 'errors');
} else {
	$thirdparty_customer->fetch($client_soc_id);
}

require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

$account_supplier = new AccountingAccount($db);
$account_supplier->fetch(empty($accounting_fourn) ? getDolGlobalString('CLIENTPAYFOURN_FOURN_ACCOUNTING') : $accounting_fourn);
$account_client = new AccountingAccount($db);
$account_client->fetch(empty($accounting_client) ? getDolGlobalString('CLIENTPAYFOURN_CLIENT_ACCOUNTING') : $accounting_client);
$JOURNAL_CODE = getDolGlobalString('CLIENTPAYFOURN_JOURNAL');
if (empty($JOURNAL_CODE)) {
	setEventMessage("CPF_Misconfigured", 'errors'); 
	header("Location: /custom/clientpayfourn/admin/setup.php");
}

if ($action && $action == 'save') {
	if (!$facture_id || !$supplier_invoice_id || !$fourn_soc_id || !$client_soc_id) {
		if (!$facture_id) {
			setEventMessage($langs->trans("CPF_CustomerInvoiceUndefined"), 'errors');
		}
		if (!$supplier_invoice_id) {
			setEventMessage($langs->trans("CPF_SupplierInvoiceUndefined"), 'errors');
		}
		if (!$fourn_soc_id) {
			setEventMessage($langs->trans("CPF_SupplierUndefined"), 'errors');
		}
		if (!$client_soc_id) {
			setEventMessage($langs->trans("CPF_CustomerUndefined"), 'errors');
		}
	} else if ($facture_id && $supplier_invoice_id) {
		/* MANAGE LINK */
		$sql = "SELECT fk_facture_client, fk_facture_fourn FROM " . MAIN_DB_PREFIX . "clientpayfourn_linkclientpayfourn";
		$sql .= " WHERE fk_facture_client = " . (int)$facture_id . " AND fk_facture_fourn = " . (int)$supplier_invoice_id;
		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				setEventMessage($langs->trans("CPF_ExistingLink"), 'errors');
				header("Location: /compta/facture/card.php?facid=" . $facture_id);
			} else {
				$link_id = createLink($facture_id, $supplier_invoice_id);
				if ($link_id == 0) {
					setEventMessage($langs->trans("CPF_LinkCreationError"), 'errors');
					header("Location: /fourn/facture/card.php?facid=" . $supplier_invoice_id);
				}
			}
		} else {
			setEventMessage($langs->trans("CPF_AntiDuplicateCheckFailed"), 'warning');
			// var_dump($db->lasterror());
		}

		/* Manage Payments */
		// Create discount from the supplier invoice
		$id_discount = createDiscount($client_invoice, $supplier_invoice, $thirdparty_customer, $amount);
		if ($id_discount < 0) {
			$db->rollback();
			setEventMessage($langs->trans("CPF_DiscountCreationFailed"), 'errors');
			/* var_dump(
				array(
					'sql' => $db->lasterror(), 
					'discount' => $discount,
				)
			);*/
			$action = 'validate';
		} else {
			$db->commit();
			setEventMessage($langs->trans("CPF_DiscountCreated"), 'mesgs');
		}

		// Mark the supplier invoice as paid
		$supplier_invoice->setPaid($user);

		// Use the credit to reduce remain to pay
		$discount = new DiscountAbsolute($db);
		$discount->fetch($id_discount);
		$result = $discount->link_to_invoice(0, $client_invoice->id);

		if ($result < 0) {
			setEventMessages($discount->error, $discount->errors, 'errors');
			$db->rollback();
		} else {
			$db->commit();
			setEventMessage($langs->trans("CPF_PaymentRecorded"), 'mesgs');
		}

		$newremaintopay = $client_invoice->getRemainToPay(0);
		if ($newremaintopay == 0) {
			$client_invoice->setPaid($user);
		}

		/* MANAGE Bookeeping */
		$ref = $client_invoice->ref . ' ' . $supplier_invoice->ref;
		$bk_1 = createBookKeeping($supplier_invoice, $client_invoice, $account_supplier, $thirdparty_supplier, (float) $amount, $ref, $link_id, $JOURNAL_CODE);
		$bk_2 = createBookKeeping($client_invoice, $supplier_invoice, $account_client, $thirdparty_customer, - (float) $amount, $ref, $link_id, $JOURNAL_CODE);
		if ($bk_1 != 0 || $bk_2 != 0) {
			setEventMessage($langs->trans("CPF_ErrorBookkeepingCreation"), 'errors');
			/*var_dump(array("Bookkeeping Supplier", $bk_1));
			var_dump(array("Bookkeeping Customer", $bk_2));
			var_dump($db->lasterror());*/
			$db->rollback();
			$action = 'validate';
		} else {
			setEventMessage("CPF_BookkeepingCreated", 'mesgs');
			$db->commit();
			header("Location: /fourn/facture/card.php?facid=" . $supplier_invoice_id);
		}
		
	}
}

$form = new Form($db);
$formfile = new FormFile($db);
$form_accounting = new FormAccounting($db);

llxHeader("", $langs->trans("ClientPayFournArea"), '', '', 0, 0, '', '', '', 'mod-clientpayfourn page-index');

print load_fiche_titre($langs->trans("ClientPayFournArea"), '', 'clientpayfourn.png@clientpayfourn');

print '<div class="fichecenter">';

if ($action && $action == 'validate') {
	print '<form name="form" action="' . $_SERVER["PHP_SELF"] . '?id=' . $facture_id . '" method="post">';

	print '<h2>' . $langs->trans("ClientPayFournCompta") . '</h2>';

	print '<table style="text-align: left ;width: 100%;">';
	print '<tr>';
	print '<th>'. $langs->trans("Docdate"). '</th>';
	print '<th>'. $langs->trans("Piece"). '</th>';
	print '<th>'. $langs->trans("Account"). '</th>';
	print '<th>'. $langs->trans("SubledgerAccount"). '</th>';
	print '<th>'. $langs->trans("Label"). '</th>';
	print '<th>'. $langs->trans("AccountingDebit"). '</th>';
	print '<th>'. $langs->trans("AccountingCredit"). '</th>';
	print '</tr>';

	function printCompta($account, $fac, $counter_part, $subledger, $mt)
	{
		global $date, $db, $langs;
		print '<tr>';
		print '<td>' . date_format(date_create($date), 'Y-m-d') . '</td>';
		print '<td> '. $fac->getNomUrl(1) . ' </td>';
		print '<td>' . $account->ref . '</td>';
		print '<td>' . $subledger . '</td>';
		print '<td>' . $langs->trans("DebtCompensation") . ' - ' . $counter_part->ref . '</td>';
		print '<td>' . (($mt >= 0) ? $mt . "" : 0) . '</td>';
		print '<td>' . (($mt < 0) ? -$mt . "" : 0) . '</td>';
		print '</tr>';
	}
	printCompta($account_supplier, $client_invoice, $supplier_invoice, $thirdparty_customer->code_compta, (float) $amount);
	printCompta($account_client, $supplier_invoice, $client_invoice, $thirdparty_supplier->code_compta_fournisseur, - (float)$amount);

	print '</table>';

	print '<input type="submit" class="button" name="submit" value="' . $langs->trans("ClientPayFournValidate") . '">';

	print '<input type="hidden" name="amount" value="' . $amount . '">';
	print '<input type="hidden" name="supplier_invoice_id" value="' . $supplier_invoice_id . '">';
	print '<input type="hidden" name="facture_id" value="' . $facture_id . '">';
	print '<input type="hidden" name="accounting_fourn" value="' . $accounting_fourn . '">';
	print '<input type="hidden" name="accounting_client" value="' . $accounting_client . '">';

	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="save">';
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
			$supplier_inv_data[$obj->rowid] = $obj->amount;
		}
		$db->free($resql);
	}

	print $form->selectarray('supplier_invoice_id', $list_select, $facture_id, 1);

	print "<script>
		// Pass the PHP JSON to a JavaScript variable
		var jsArray =" . json_encode($supplier_inv_data) . "
		</script>";

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

	$mnt = empty($amountClient) ? $amount : $amountClient;
	print '<tr>';
	print '<td>';
	print '<b>' . $langs->trans("Amount") . '</b>';
	print '</td>';
	print '<td>';
	print '<input type="number" value="'.$mnt.'" id="amount" name="amount" step="0.01"/>';
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
	print '<input type="submit" class="button" name="submit" value="' . $langs->trans("ClientPayFournButtonLink") . '">';
	print '</td>';
	print '</tr>';

	print '</table>';

	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="validate">';
	print '<input type="hidden" name="id" value="' . $facture_id . '">';

	print '</form>';

	print '<script type="text/javascript">';
	print '$(document).ready(function() {';
	print '	$("#socid").change(function() {';
	print '		window.location.href = "/custom/clientpayfourn/clientpayfournindex.php?id=' . $facture_id . '&socid=" + $("#socid").val();';
	print '	});';
	// Manage dynamic field amount update
	print '	console.log("Doneee");';
	print ' $("#supplier_invoice_id").change(function() {';
	print '		console.log("RUUUn");';
	print '		var selectedIndex = $(this).val();';
    print '		var amountValue = Number(jsArray[selectedIndex]);';
    print '		$("#amount").val(isNaN(amountValue) ? 0 : amountValue);';
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
