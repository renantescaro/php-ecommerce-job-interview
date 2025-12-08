<?php
namespace Tests\Controller;

use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Controller\BaseController
 */
class BaseControllerTest extends TestCase {

    private $baseControllerStub;

    protected function setUp(): void {
        $this->baseControllerStub = new class extends \App\Controller\BaseController {
            public function callGetRequestData(): array {
                return $this->getRequestData();
            }

            public function dummyMethod(): void {}
        };
    }
    
    protected function tearDown(): void {
        // Limpeza global
        if (defined('PHPUNIT_RUNNING')) {
            unset($GLOBALS['mock_http_input']);
            if (defined('PHPUNIT_RUNNING')) {
                // @codingStandardsIgnoreStart
                eval('if (defined("PHPUNIT_RUNNING")) { unset($GLOBALS["_ENV"]["PHPUNIT_RUNNING"]); }');
                // @codingStandardsIgnoreEnd
            }
        }
    }

    public function testGetRequestDataReturnsMockDataInTestEnvironment(): void {
        $expectedData = ['key' => 'test_value', 'id' => 123];
        $expectedJson = json_encode($expectedData);
        
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $GLOBALS['mock_http_input'] = $expectedJson;

        $data = $this->baseControllerStub->callGetRequestData();

        $this->assertSame($expectedData, $data);
    }

    public function testGetRequestDataReturnsEmptyArrayIfMockDataIsInvalid(): void {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $GLOBALS['mock_http_input'] = 'not valid json string';

        $data = $this->baseControllerStub->callGetRequestData();

        $this->assertSame([], $data);
    }
    
    public function testGetRequestDataReturnsEmptyArrayIfInputIsNull(): void {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $GLOBALS['mock_http_input'] = null;

        $data = $this->baseControllerStub->callGetRequestData();

        $this->assertSame([], $data);
    }
}
