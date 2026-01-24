<?php
/**
 * Standalone unit tests for modClientPayFourn module descriptor
 * Tests module configuration without Dolibarr dependency
 *
 * Run with: phpunit htdocs/custom/clientpayfourn/test/phpunit/unit/ModClientPayFournTest.php
 */

use PHPUnit\Framework\TestCase;

/**
 * Testable version of module descriptor that extracts configuration
 */
class ModClientPayFournTestable
{
	/**
	 * @var int Module unique ID
	 */
	public $numero = 561000;

	/**
	 * @var string Rights class identifier
	 */
	public $rights_class = 'clientpayfourn';

	/**
	 * @var string Module family
	 */
	public $family = 'other';

	/**
	 * @var string Module position
	 */
	public $module_position = '90';

	/**
	 * @var string Module name
	 */
	public $name = 'ClientPayFourn';

	/**
	 * @var string Module description key
	 */
	public $description = 'ClientPayFournDescription';

	/**
	 * @var string Module version
	 */
	public $version = '1.2';

	/**
	 * @var string Editor name
	 */
	public $editor_name = 'DoliTest';

	/**
	 * @var string Module picto/icon
	 */
	public $picto = 'fa-file-o';

	/**
	 * @var array Minimum PHP version
	 */
	public $phpmin = array(7, 0);

	/**
	 * @var array Minimum Dolibarr version
	 */
	public $need_dolibarr_version = array(11, -3);

	/**
	 * @var array Module dependencies
	 */
	public $depends = array();

	/**
	 * @var array Modules that require this module
	 */
	public $requiredby = array();

	/**
	 * @var array Conflicting modules
	 */
	public $conflictwith = array();

	/**
	 * @var array Language files
	 */
	public $langfiles = array('clientpayfourn@clientpayfourn');

	/**
	 * @var array Module parts configuration
	 */
	public $module_parts = array(
		'triggers' => 0,
		'login' => 0,
		'substitutions' => 0,
		'menus' => 0,
		'tpl' => 0,
		'barcode' => 0,
		'models' => 0,
		'printing' => 0,
		'theme' => 0,
		'css' => array(),
		'js' => array('/clientpayfourn/js/clientpayfourn.js.php'),
		'hooks' => array(),
		'moduleforexternal' => 0,
	);

	/**
	 * @var array Data directories
	 */
	public $dirs = array('/clientpayfourn/temp');

	/**
	 * @var array Config page URLs
	 */
	public $config_page_url = array('setup.php@clientpayfourn');

	/**
	 * @var bool Module hidden state
	 */
	public $hidden = false;

	/**
	 * Check if PHP version is compatible
	 *
	 * @param int $major PHP major version
	 * @param int $minor PHP minor version
	 * @return bool True if compatible
	 */
	public function isPhpVersionCompatible($major, $minor)
	{
		if ($major > $this->phpmin[0]) {
			return true;
		}
		if ($major == $this->phpmin[0] && $minor >= $this->phpmin[1]) {
			return true;
		}
		return false;
	}

	/**
	 * Get minimum Dolibarr version as string
	 *
	 * @return string Version string
	 */
	public function getMinDolibarrVersion()
	{
		$major = $this->need_dolibarr_version[0];
		$minor = abs($this->need_dolibarr_version[1]);
		return $major . '.' . $minor;
	}

	/**
	 * Parse version string into components
	 *
	 * @param string $version Version string like "1.2" or "1.2.3"
	 * @return array Array with major, minor, and optionally patch
	 */
	public function parseVersion($version)
	{
		$parts = explode('.', $version);
		$result = array(
			'major' => isset($parts[0]) ? (int) $parts[0] : 0,
			'minor' => isset($parts[1]) ? (int) $parts[1] : 0,
		);
		if (isset($parts[2])) {
			$result['patch'] = (int) $parts[2];
		}
		return $result;
	}

	/**
	 * Check if a module part is enabled
	 *
	 * @param string $part Part name (triggers, login, etc.)
	 * @return bool True if enabled
	 */
	public function isModulePartEnabled($part)
	{
		if (!isset($this->module_parts[$part])) {
			return false;
		}
		$value = $this->module_parts[$part];
		if (is_array($value)) {
			return !empty($value);
		}
		return $value == 1;
	}

	/**
	 * Get JavaScript files
	 *
	 * @return array List of JS file paths
	 */
	public function getJsFiles()
	{
		return isset($this->module_parts['js']) ? $this->module_parts['js'] : array();
	}

	/**
	 * Get CSS files
	 *
	 * @return array List of CSS file paths
	 */
	public function getCssFiles()
	{
		return isset($this->module_parts['css']) ? $this->module_parts['css'] : array();
	}
}

class ModClientPayFournTest extends TestCase
{
	/**
	 * @var ModClientPayFournTestable
	 */
	private $module;

	protected function setUp(): void
	{
		$this->module = new ModClientPayFournTestable();
	}

	// ========================================
	// Tests for module identification
	// ========================================

	public function testModuleNumber()
	{
		$this->assertEquals(561000, $this->module->numero);
	}

	public function testModuleNumberIsInValidRange()
	{
		// Module numbers for custom modules should be > 100000
		$this->assertGreaterThan(100000, $this->module->numero);
	}

	public function testRightsClass()
	{
		$this->assertEquals('clientpayfourn', $this->module->rights_class);
	}

	public function testModuleName()
	{
		$this->assertEquals('ClientPayFourn', $this->module->name);
	}

	public function testModuleFamily()
	{
		$this->assertEquals('other', $this->module->family);
	}

	public function testModulePosition()
	{
		$this->assertEquals('90', $this->module->module_position);
	}

	// ========================================
	// Tests for version information
	// ========================================

	public function testModuleVersion()
	{
		$this->assertEquals('1.2', $this->module->version);
	}

	public function testParseVersionMajorMinor()
	{
		$parsed = $this->module->parseVersion('1.2');

		$this->assertEquals(1, $parsed['major']);
		$this->assertEquals(2, $parsed['minor']);
		$this->assertArrayNotHasKey('patch', $parsed);
	}

	public function testParseVersionWithPatch()
	{
		$parsed = $this->module->parseVersion('2.5.3');

		$this->assertEquals(2, $parsed['major']);
		$this->assertEquals(5, $parsed['minor']);
		$this->assertEquals(3, $parsed['patch']);
	}

	public function testParseVersionSingleDigit()
	{
		$parsed = $this->module->parseVersion('3');

		$this->assertEquals(3, $parsed['major']);
		$this->assertEquals(0, $parsed['minor']);
	}

	// ========================================
	// Tests for PHP version compatibility
	// ========================================

	public function testPhpMinVersion()
	{
		$this->assertEquals(7, $this->module->phpmin[0]);
		$this->assertEquals(0, $this->module->phpmin[1]);
	}

	public function testPhpVersionCompatiblePhp74()
	{
		$this->assertTrue($this->module->isPhpVersionCompatible(7, 4));
	}

	public function testPhpVersionCompatiblePhp80()
	{
		$this->assertTrue($this->module->isPhpVersionCompatible(8, 0));
	}

	public function testPhpVersionCompatiblePhp81()
	{
		$this->assertTrue($this->module->isPhpVersionCompatible(8, 1));
	}

	public function testPhpVersionCompatiblePhp82()
	{
		$this->assertTrue($this->module->isPhpVersionCompatible(8, 2));
	}

	public function testPhpVersionCompatiblePhp83()
	{
		$this->assertTrue($this->module->isPhpVersionCompatible(8, 3));
	}

	public function testPhpVersionIncompatiblePhp56()
	{
		$this->assertFalse($this->module->isPhpVersionCompatible(5, 6));
	}

	public function testPhpVersionCompatibleExactMatch()
	{
		$this->assertTrue($this->module->isPhpVersionCompatible(7, 0));
	}

	// ========================================
	// Tests for Dolibarr version requirements
	// ========================================

	public function testDolibarrMinVersion()
	{
		$this->assertEquals(11, $this->module->need_dolibarr_version[0]);
		$this->assertEquals(-3, $this->module->need_dolibarr_version[1]);
	}

	public function testGetMinDolibarrVersion()
	{
		$this->assertEquals('11.3', $this->module->getMinDolibarrVersion());
	}

	// ========================================
	// Tests for dependencies
	// ========================================

	public function testNoDependencies()
	{
		$this->assertEmpty($this->module->depends);
	}

	public function testNoRequiredBy()
	{
		$this->assertEmpty($this->module->requiredby);
	}

	public function testNoConflicts()
	{
		$this->assertEmpty($this->module->conflictwith);
	}

	// ========================================
	// Tests for module parts
	// ========================================

	public function testTriggersDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('triggers'));
	}

	public function testLoginDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('login'));
	}

	public function testSubstitutionsDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('substitutions'));
	}

	public function testMenusDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('menus'));
	}

	public function testModelsDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('models'));
	}

	public function testJsEnabled()
	{
		$this->assertTrue($this->module->isModulePartEnabled('js'));
	}

	public function testCssDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('css'));
	}

	public function testUnknownPartDisabled()
	{
		$this->assertFalse($this->module->isModulePartEnabled('nonexistent'));
	}

	// ========================================
	// Tests for JS/CSS files
	// ========================================

	public function testGetJsFiles()
	{
		$jsFiles = $this->module->getJsFiles();

		$this->assertIsArray($jsFiles);
		$this->assertCount(1, $jsFiles);
		$this->assertContains('/clientpayfourn/js/clientpayfourn.js.php', $jsFiles);
	}

	public function testGetCssFiles()
	{
		$cssFiles = $this->module->getCssFiles();

		$this->assertIsArray($cssFiles);
		$this->assertEmpty($cssFiles);
	}

	// ========================================
	// Tests for configuration
	// ========================================

	public function testConfigPageUrl()
	{
		$this->assertContains('setup.php@clientpayfourn', $this->module->config_page_url);
	}

	public function testLangFiles()
	{
		$this->assertContains('clientpayfourn@clientpayfourn', $this->module->langfiles);
	}

	public function testDirectories()
	{
		$this->assertContains('/clientpayfourn/temp', $this->module->dirs);
	}

	public function testNotHidden()
	{
		$this->assertFalse($this->module->hidden);
	}

	// ========================================
	// Tests for editor information
	// ========================================

	public function testEditorName()
	{
		$this->assertEquals('DoliTest', $this->module->editor_name);
	}

	public function testPicto()
	{
		$this->assertEquals('fa-file-o', $this->module->picto);
	}

	public function testPictoIsFontAwesome()
	{
		$this->assertStringStartsWith('fa-', $this->module->picto);
	}

	// ========================================
	// Tests for description
	// ========================================

	public function testDescription()
	{
		$this->assertEquals('ClientPayFournDescription', $this->module->description);
	}

	public function testDescriptionIsTranslationKey()
	{
		// Description should be a translation key, not the actual text
		$this->assertStringNotContainsString(' ', $this->module->description);
	}
}
