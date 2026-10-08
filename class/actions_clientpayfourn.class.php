<?php
/* Copyright (C) 2025 Resilio SA
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
 * \file    clientpayfourn/class/actions_clientpayfourn.class.php
 * \ingroup clientpayfourn
 * \brief   Hooks of module ClientPayFourn
 */

/**
 * Class ActionsClientpayfourn
 */
class ActionsClientpayfourn
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string[] Error messages
	 */
	public $errors = array();

	/**
	 * @var string[] Warning messages
	 */
	public $warnings = array();

	/**
	 * @var array Hook results
	 */
	public $results = array();

	/**
	 * @var string Html content returned by the hook
	 */
	public $resprints = '';

	/**
	 * @var array Invoice ids already resolved for a bookkeeping entry
	 */
	private $linkcache = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Add the compensation column title to the bookkeeping list
	 *
	 * @param	array			$parameters		Hook parameters
	 * @param	CommonObject	$object			Object of the calling page
	 * @param	string			$action			Action code of the calling page
	 * @param	HookManager		$hookmanager	Hook manager
	 * @return	int								0 to keep standard actions
	 */
	public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if ($parameters['currentcontext'] != 'bookkeepinglist') {
			return 0;
		}

		$langs->load('clientpayfourn@clientpayfourn');

		$this->resprints = '<th class="liste_titre">'.$langs->trans('DebtCompensation').'</th>';

		return 0;
	}

	/**
	 * Add the compensated invoices links to the bookkeeping list
	 *
	 * @param	array			$parameters		Hook parameters
	 * @param	CommonObject	$object			Object of the calling page
	 * @param	string			$action			Action code of the calling page
	 * @param	HookManager		$hookmanager	Hook manager
	 * @return	int								0 to keep standard actions
	 */
	public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if ($parameters['currentcontext'] != 'bookkeepinglist') {
			return 0;
		}

		$langs->load('clientpayfourn@clientpayfourn');

		$line = isset($parameters['obj']) ? $parameters['obj'] : null;
		$content = '';

		if (is_object($line) && $line->doc_type == 'special_clientpayfourn') {
			$link = $this->getLinkedInvoiceIds($line->fk_doc, $line->doc_ref);
			if (!empty($link)) {
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

				$customer_invoice = new Facture($this->db);
				$supplier_invoice = new FactureFournisseur($this->db);
				if ($customer_invoice->fetch($link['fk_facture_client']) > 0) {
					$content .= $customer_invoice->getNomUrl(1);
				}
				if ($supplier_invoice->fetch($link['fk_facture_fourn']) > 0) {
					$content .= ($content ? ' '.$langs->trans('CPF_CompensatedWith').' ' : '').$supplier_invoice->getNomUrl(1);
				}
			} else {
				$content = dol_escape_htmltag($line->doc_ref);
			}
		}

		$this->resprints = '<td class="nowraponall">'.$content.'</td>';

		if (empty($parameters['i']) && isset($parameters['totalarray']['nbfield'])) {
			$parameters['totalarray']['nbfield']++;
		}

		return 0;
	}

	/**
	 * Return the customer and supplier invoice ids compensated by a bookkeeping entry
	 *
	 * @param	int		$fk_doc		Id of the compensation link stored on the bookkeeping entry
	 * @param	string	$doc_ref	Piece reference of the bookkeeping entry
	 * @return	array				Empty if not found, else fk_facture_client and fk_facture_fourn
	 */
	private function getLinkedInvoiceIds($fk_doc, $doc_ref)
	{
		$cachekey = $fk_doc.'-'.$doc_ref;
		if (isset($this->linkcache[$cachekey])) {
			return $this->linkcache[$cachekey];
		}

		$sql = 'SELECT cpf.fk_facture_client, cpf.fk_facture_fourn';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'clientpayfourn_linkclientpayfourn as cpf';
		$sql .= ' WHERE cpf.rowid = '.((int) $fk_doc);

		$resql = $this->db->query($sql);
		$link = array();
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$link = array('fk_facture_client' => (int) $obj->fk_facture_client, 'fk_facture_fourn' => (int) $obj->fk_facture_fourn);
			}
			$this->db->free($resql);
		}

		// Entries created before the link id was stored only carry the two invoice references
		if (empty($link)) {
			$sql = 'SELECT cpf.fk_facture_client, cpf.fk_facture_fourn';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'clientpayfourn_linkclientpayfourn as cpf';
			$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'facture as f ON f.rowid = cpf.fk_facture_client';
			$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'facture_fourn as ff ON ff.rowid = cpf.fk_facture_fourn';
			$sql .= " WHERE CONCAT(f.ref, ' ', ff.ref) = '".$this->db->escape($doc_ref)."'";

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$link = array('fk_facture_client' => (int) $obj->fk_facture_client, 'fk_facture_fourn' => (int) $obj->fk_facture_fourn);
				}
				$this->db->free($resql);
			}
		}

		$this->linkcache[$cachekey] = $link;

		return $link;
	}
}
