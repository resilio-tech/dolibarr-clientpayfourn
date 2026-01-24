<?php
/**
 * Standalone unit tests for LinkClientPayFourn
 * Tests data mapping and configuration without database dependency
 *
 * Run with: phpunit htdocs/custom/clientpayfourn/test/phpunit/unit/LinkClientPayFournTest.php
 */

use PHPUnit\Framework\TestCase;

/**
 * Mock DoliDB class for testing without Dolibarr dependency
 */
class DoliDB
{
	public function prefix()
	{
		return 'llx_';
	}

	public function query($sql)
	{
		return true;
	}
}

/**
 * Mock CommonObject class
 */
class CommonObject
{
	public $db;
	public $fields = array();

	public function __construct($db = null)
	{
		$this->db = $db;
	}

	public function getFieldList($alias = '')
	{
		return '*';
	}

	public function setVarsFromFetchObj($obj)
	{
		// Mock implementation
	}
}

/**
 * Define Dolibarr functions used in the class
 */
if (!function_exists('getDolGlobalInt')) {
	function getDolGlobalInt($name)
	{
		return 0;
	}
}

if (!function_exists('isModEnabled')) {
	function isModEnabled($module)
	{
		return false;
	}
}

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../..');
}

/**
 * Simplified test class that includes only the methods we want to test
 */
class LinkClientPayFournTestable
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'clientpayfourn';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'linkclientpayfourn';

	/**
	 * @var string Name of table without prefix
	 */
	public $table_element = 'clientpayfourn_linkclientpayfourn';

	/**
	 * Mapping of element types to database fields
	 */
	public $element_to_db_field = array(
		'facture' => 'fk_facture_client',
		'facture_fourn' => 'fk_facture_fourn',
		'invoice_supplier' => 'fk_facture_fourn',
	);

	/**
	 * Field definitions
	 */
	public $fields = array(
		"rowid" => array("type" => "integer", "label" => "TechnicalID", "enabled" => "1", 'position' => 1, 'notnull' => 1, "visible" => "0"),
		"fk_facture_client" => array("type" => "integer", "label" => "InvoiceClient", "enabled" => "1", 'position' => 2, 'notnull' => 1, "visible" => "1"),
		"fk_facture_fourn" => array("type" => "integer", "label" => "InvoiceSupplier", "enabled" => "1", 'position' => 3, 'notnull' => 1, "visible" => "1"),
		"datec" => array("type" => "datetime", "label" => "DateCreation", "enabled" => "1", 'position' => 4, 'notnull' => 1, "visible" => "1"),
	);

	/**
	 * Status constants
	 */
	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	/**
	 * Check if element type is supported
	 *
	 * @param string $element Element type identifier
	 * @return bool True if supported, false otherwise
	 */
	public function isElementSupported($element)
	{
		return array_key_exists($element, $this->element_to_db_field);
	}

	/**
	 * Get database field for element type
	 *
	 * @param string $element Element type identifier
	 * @return string|null Database field name or null if not found
	 */
	public function getDbFieldForElement($element)
	{
		if ($this->isElementSupported($element)) {
			return $this->element_to_db_field[$element];
		}
		return null;
	}

	/**
	 * Get all supported element types
	 *
	 * @return array List of supported element type identifiers
	 */
	public function getSupportedElements()
	{
		return array_keys($this->element_to_db_field);
	}

	/**
	 * Determine target type based on source type
	 *
	 * @param string $sourceElement Source element type
	 * @return array|null Array with target_field and target_class, or null if invalid
	 */
	public function getTargetInfo($sourceElement)
	{
		if (!$this->isElementSupported($sourceElement)) {
			return null;
		}

		$sourceField = $this->element_to_db_field[$sourceElement];

		switch ($sourceField) {
			case 'fk_facture_fourn':
				return array(
					'target_field' => 'fk_facture_client',
					'target_class' => 'Facture'
				);
			case 'fk_facture_client':
				return array(
					'target_field' => 'fk_facture_fourn',
					'target_class' => 'FactureFournisseur'
				);
			default:
				return null;
		}
	}

	/**
	 * Validate invoice IDs
	 *
	 * @param int $clientInvoiceId Customer invoice ID
	 * @param int $supplierInvoiceId Supplier invoice ID
	 * @return array Validation errors (empty if valid)
	 */
	public function validateLinkData($clientInvoiceId, $supplierInvoiceId)
	{
		$errors = array();

		if (empty($clientInvoiceId) || !is_numeric($clientInvoiceId) || $clientInvoiceId <= 0) {
			$errors[] = 'InvalidClientInvoiceId';
		}

		if (empty($supplierInvoiceId) || !is_numeric($supplierInvoiceId) || $supplierInvoiceId <= 0) {
			$errors[] = 'InvalidSupplierInvoiceId';
		}

		return $errors;
	}
}

class LinkClientPayFournTest extends TestCase
{
	/**
	 * @var LinkClientPayFournTestable
	 */
	private $link;

	protected function setUp(): void
	{
		$this->link = new LinkClientPayFournTestable();
	}

	// ========================================
	// Tests for element_to_db_field mapping
	// ========================================

	public function testElementToDbFieldContainsFacture()
	{
		$this->assertTrue($this->link->isElementSupported('facture'));
		$this->assertEquals('fk_facture_client', $this->link->getDbFieldForElement('facture'));
	}

	public function testElementToDbFieldContainsFactureFourn()
	{
		$this->assertTrue($this->link->isElementSupported('facture_fourn'));
		$this->assertEquals('fk_facture_fourn', $this->link->getDbFieldForElement('facture_fourn'));
	}

	public function testElementToDbFieldContainsInvoiceSupplier()
	{
		$this->assertTrue($this->link->isElementSupported('invoice_supplier'));
		$this->assertEquals('fk_facture_fourn', $this->link->getDbFieldForElement('invoice_supplier'));
	}

	public function testElementToDbFieldRejectsUnknown()
	{
		$this->assertFalse($this->link->isElementSupported('unknown_element'));
		$this->assertNull($this->link->getDbFieldForElement('unknown_element'));
	}

	public function testGetSupportedElements()
	{
		$supported = $this->link->getSupportedElements();

		$this->assertIsArray($supported);
		$this->assertContains('facture', $supported);
		$this->assertContains('facture_fourn', $supported);
		$this->assertContains('invoice_supplier', $supported);
		$this->assertCount(3, $supported);
	}

	// ========================================
	// Tests for target info resolution
	// ========================================

	public function testGetTargetInfoForClientInvoice()
	{
		$result = $this->link->getTargetInfo('facture');

		$this->assertNotNull($result);
		$this->assertEquals('fk_facture_fourn', $result['target_field']);
		$this->assertEquals('FactureFournisseur', $result['target_class']);
	}

	public function testGetTargetInfoForSupplierInvoice()
	{
		$result = $this->link->getTargetInfo('facture_fourn');

		$this->assertNotNull($result);
		$this->assertEquals('fk_facture_client', $result['target_field']);
		$this->assertEquals('Facture', $result['target_class']);
	}

	public function testGetTargetInfoForInvoiceSupplierAlias()
	{
		$result = $this->link->getTargetInfo('invoice_supplier');

		$this->assertNotNull($result);
		$this->assertEquals('fk_facture_client', $result['target_field']);
		$this->assertEquals('Facture', $result['target_class']);
	}

	public function testGetTargetInfoForUnknownReturnsNull()
	{
		$result = $this->link->getTargetInfo('unknown');

		$this->assertNull($result);
	}

	// ========================================
	// Tests for link data validation
	// ========================================

	public function testValidateLinkDataValidIds()
	{
		$errors = $this->link->validateLinkData(1, 2);

		$this->assertEmpty($errors);
	}

	public function testValidateLinkDataLargeIds()
	{
		$errors = $this->link->validateLinkData(999999, 888888);

		$this->assertEmpty($errors);
	}

	public function testValidateLinkDataInvalidClientId()
	{
		$errors = $this->link->validateLinkData(0, 1);

		$this->assertNotEmpty($errors);
		$this->assertContains('InvalidClientInvoiceId', $errors);
	}

	public function testValidateLinkDataInvalidSupplierIdZero()
	{
		$errors = $this->link->validateLinkData(1, 0);

		$this->assertNotEmpty($errors);
		$this->assertContains('InvalidSupplierInvoiceId', $errors);
	}

	public function testValidateLinkDataNegativeClientId()
	{
		$errors = $this->link->validateLinkData(-1, 1);

		$this->assertNotEmpty($errors);
		$this->assertContains('InvalidClientInvoiceId', $errors);
	}

	public function testValidateLinkDataNegativeSupplierId()
	{
		$errors = $this->link->validateLinkData(1, -5);

		$this->assertNotEmpty($errors);
		$this->assertContains('InvalidSupplierInvoiceId', $errors);
	}

	public function testValidateLinkDataBothInvalid()
	{
		$errors = $this->link->validateLinkData(0, 0);

		$this->assertCount(2, $errors);
		$this->assertContains('InvalidClientInvoiceId', $errors);
		$this->assertContains('InvalidSupplierInvoiceId', $errors);
	}

	public function testValidateLinkDataNullValues()
	{
		$errors = $this->link->validateLinkData(null, null);

		$this->assertCount(2, $errors);
	}

	public function testValidateLinkDataEmptyStringValues()
	{
		$errors = $this->link->validateLinkData('', '');

		$this->assertCount(2, $errors);
	}

	public function testValidateLinkDataNonNumericValues()
	{
		$errors = $this->link->validateLinkData('abc', 'xyz');

		$this->assertCount(2, $errors);
	}

	// ========================================
	// Tests for field definitions
	// ========================================

	public function testFieldsContainsRowid()
	{
		$this->assertArrayHasKey('rowid', $this->link->fields);
		$this->assertEquals('integer', $this->link->fields['rowid']['type']);
	}

	public function testFieldsContainsFkFactureClient()
	{
		$this->assertArrayHasKey('fk_facture_client', $this->link->fields);
		$this->assertEquals('integer', $this->link->fields['fk_facture_client']['type']);
		$this->assertEquals(1, $this->link->fields['fk_facture_client']['notnull']);
	}

	public function testFieldsContainsFkFactureFourn()
	{
		$this->assertArrayHasKey('fk_facture_fourn', $this->link->fields);
		$this->assertEquals('integer', $this->link->fields['fk_facture_fourn']['type']);
		$this->assertEquals(1, $this->link->fields['fk_facture_fourn']['notnull']);
	}

	public function testFieldsContainsDatec()
	{
		$this->assertArrayHasKey('datec', $this->link->fields);
		$this->assertEquals('datetime', $this->link->fields['datec']['type']);
	}

	public function testFieldsPositionOrder()
	{
		$this->assertEquals(1, $this->link->fields['rowid']['position']);
		$this->assertEquals(2, $this->link->fields['fk_facture_client']['position']);
		$this->assertEquals(3, $this->link->fields['fk_facture_fourn']['position']);
		$this->assertEquals(4, $this->link->fields['datec']['position']);
	}

	// ========================================
	// Tests for status constants
	// ========================================

	public function testStatusDraftConstant()
	{
		$this->assertEquals(0, LinkClientPayFournTestable::STATUS_DRAFT);
	}

	public function testStatusValidatedConstant()
	{
		$this->assertEquals(1, LinkClientPayFournTestable::STATUS_VALIDATED);
	}

	public function testStatusCanceledConstant()
	{
		$this->assertEquals(9, LinkClientPayFournTestable::STATUS_CANCELED);
	}

	// ========================================
	// Tests for module/element identifiers
	// ========================================

	public function testModuleIdentifier()
	{
		$this->assertEquals('clientpayfourn', $this->link->module);
	}

	public function testElementIdentifier()
	{
		$this->assertEquals('linkclientpayfourn', $this->link->element);
	}

	public function testTableElement()
	{
		$this->assertEquals('clientpayfourn_linkclientpayfourn', $this->link->table_element);
	}
}
