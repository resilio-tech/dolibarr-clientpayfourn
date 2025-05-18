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
 */

/**
 * \file    clientpayfourn/lib/clientpayfourn.lib.php
 * \ingroup clientpayfourn
 * \brief   Library files with common functions for ClientPayFourn
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function clientpayfournAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("clientpayfourn@clientpayfourn");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/clientpayfourn/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/clientpayfourn/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = is_countable($extrafields->attributes['myobject']['label']) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/clientpayfourn/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@clientpayfourn:/clientpayfourn/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@clientpayfourn:/clientpayfourn/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'clientpayfourn@clientpayfourn');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'clientpayfourn@clientpayfourn', 'remove');

	return $head;
}


/**	
 * Create a link between a Customer invoice and a sSupplier invoice when compensating them
 *
 *	facture_id 			int 	Foreign key to Facture (Customer Invoice) Object
 *	supplier_invoice_id 	int 	Foreign key to SupplierInvoice Object
 *
 * @return 0 if KO, int > 0 (id of inserted object) if inserted successfully
 */
function createLink($facture_id, $supplier_invoice_id)
{
	global $db, $now, $amount, $supplier_invoice, $date;
	
	$sql = "INSERT INTO " . MAIN_DB_PREFIX . "clientpayfourn_linkclientpayfourn (fk_facture_client, fk_facture_fourn, datec)";
	$sql .= " VALUES (" . (int)$facture_id . ", " . (int)$supplier_invoice_id . ", '".date_format(date_create($date), 'Y-m-d')."')";
	$resql = $db->query($sql);

	if ($resql) {
		setEventMessage("Link created", 'mesgs');
		$db->commit();
		// Get rowid to return it
		$sql = "SELECT fk_facture_client, fk_facture_fourn FROM " . MAIN_DB_PREFIX . "clientpayfourn_linkclientpayfourn";
		$sql .= " WHERE fk_facture_client = " . (int)$facture_id . " AND fk_facture_fourn = " . (int)$supplier_invoice_id;
		$resql = $db->query($sql);
		$id_inserted = $db->fetch_object($resql);
		
		return $id_inserted;
	
	} else {
		$db->rollback();
	
		return 0;
	}
}

function createBookKeeping($invoice, $counter_part, $account, $thirdparty, $mt, $ref, $fk_doc, $journal_code)
{
	global $db, $now, $langs, $user, $date, $conf, $action;
	$accountingjournalstatic = new AccountingJournal($db);
	$accountingjournalstatic->fetch($journal_code);

	$bookkeeping = new BookKeeping($db);
	$bookkeeping->doc_date = date_create($date)->getTimestamp();
	$bookkeeping->date_lim_reglement = date_create($date)->getTimestamp();
	$bookkeeping->doc_ref = $ref;
	$bookkeeping->date_creation = $now;
	$bookkeeping->doc_type = 'special_clientpayfourn';//'customer_invoice';
	$bookkeeping->fk_doc = $fk_doc;
	$bookkeeping->fk_docdet = 0;
	$bookkeeping->thirdparty_code = $mt > 0 ? $thirdparty->code_fournisseur : $thirdparty->code_client;

	$bookkeeping->subledger_account = $mt > 0 ? $thirdparty->code_compta_fournisseur : $thirdparty->code_compta;
	$bookkeeping->subledger_label = $thirdparty->name;

	$bookkeeping->numero_compte = $account->ref;
	$bookkeeping->label_compte = $account->label;

	$bookkeeping->label_operation = $langs->trans("DebtCompensation") . ' - ' . $counter_part->ref ;
	$bookkeeping->montant = $mt;
	$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
	$bookkeeping->debit = ($mt >= 0) ? $mt : 0;
	$bookkeeping->credit = ($mt < 0) ? -$mt : 0;
	$bookkeeping->code_journal = $accountingjournalstatic->code;
	$bookkeeping->journal_label = $langs->transnoentities($accountingjournalstatic->label);
	$bookkeeping->fk_user_author = $user->id;
	$bookkeeping->entity = $conf->entity;

	return $bookkeeping->create($user);
}

function createDiscount($invoice, $invoice_supp, $thirdparty, $amount) 
{
	global $db, $user, $langs;
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

	// Create the discount
	$discount = new DiscountAbsolute($db);
	$discount->description = $langs->trans("DebtCompensation").' - '.$invoice_supp->ref;
	$discount->fk_soc = $thirdparty->id;
	// When generalizing, reactivate on good usecases $discount->fk_facture_source = $invoice->id;
	$discount->fk_invoice_supplier_source = $invoice_supp->id;
	$discount->amount_ht = $discount->amount_ttc = $amount;
	$discount->amount_tva = 0;
	$discount->tva_tx = 0;
	$discount->vat_src_code = '';
	$id_result = $discount->create($user);

	return $id_result;
}
