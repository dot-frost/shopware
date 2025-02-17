<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Tests\Functional\Controllers\Backend;

use Enlight_Components_Test_Controller_TestCase;
use Shopware\Tests\Functional\Traits\ContainerTrait;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use Shopware_Plugins_Backend_Auth_Bootstrap as AuthPlugin;

class BaseTest extends Enlight_Components_Test_Controller_TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    private AuthPlugin $authPlugin;

    /**
     * Standard set up for every test - just disable auth
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authPlugin = $this->getContainer()->get('plugins')->Backend()->Auth();
        $this->authPlugin->setNoAuth();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->authPlugin->setNoAuth(false);
    }

    /**
     * @dataProvider provideSearchString
     *
     * @param array<array{total: int, id: string, name: string, description: string, active: string, ordernumber: string, articleId: string, inStock: string, supplierName: string, supplierId: string, additionalText: string, price: float}> $expectedResults
     */
    public function testGetVariantsActionConfirmReturnValues(string $searchTerm, bool $hasResults, array $expectedResults = []): void
    {
        $params = [
            'articles' => 'true',
            'variants' => 'true',
            'configurator' => 'true',
            'page' => 1,
            'start' => 0,
            'limit' => 10,
            'filter' => json_encode(
                [[
                    'property' => 'free',
                    'value' => '%' . $searchTerm . '%',
                    'operator' => null,
                    'expression' => null,
                ]]
            ),
        ];

        $this->Request()->setMethod('GET')->setParams($params);
        $this->dispatch('backend/base/getVariants');

        $jsonBody = $this->View()->getAssign();

        static::assertIsArray($jsonBody);
        static::assertIsArray($jsonBody['data']);
        static::assertIsBool($jsonBody['success']);
        static::assertIsInt($jsonBody['total']);

        if (!$hasResults) {
            static::assertEmpty($jsonBody['data']);
            static::assertEquals(0, $jsonBody['total']);

            return;
        }

        static::assertLessThanOrEqual($params['limit'], \count($jsonBody['data']));
        static::assertArrayHasKey('id', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['id']);
        static::assertArrayHasKey('name', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['name']);
        static::assertArrayHasKey('description', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['description']);
        static::assertArrayHasKey('active', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['active']);
        static::assertArrayHasKey('ordernumber', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['ordernumber']);
        static::assertArrayHasKey('articleId', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['articleId']);
        static::assertArrayHasKey('inStock', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['inStock']);
        static::assertArrayHasKey('supplierName', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['supplierName']);
        static::assertArrayHasKey('supplierId', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['supplierId']);
        static::assertArrayHasKey('additionalText', $jsonBody['data'][0]);
        static::assertIsString($jsonBody['data'][0]['additionalText']);
        static::assertArrayHasKey('price', $jsonBody['data'][0]);
        static::assertIsFloat($jsonBody['data'][0]['price']);

        if (!empty($expectedResults)) {
            static::assertEquals($expectedResults['total'], $jsonBody['total']);
            static::assertEquals((int) $expectedResults['inStock'], (int) $jsonBody['data'][0]['inStock']);
            unset($expectedResults['inStock'], $expectedResults['total']);
            foreach ($expectedResults as $key => $value) {
                static::assertEquals($value, $jsonBody['data'][0][$key]);
            }
        }
    }

    /**
     * @return array<array{0: string, 1: bool, 2?:array{total: int, id: string, name: string, description: string, active: string, ordernumber: string, articleId: string, inStock: string, supplierName: string, supplierId: string, additionalText: string, price: float}}>
     */
    public function provideSearchString(): array
    {
        return [
            'orderNumber explicit' => ['SW10178', true, [
                'total' => 1,
                'id' => '407',
                'name' => 'Strandtuch "Ibiza"',
                'description' => 'paulatim Praecepio lex Edoceo sis conticinium Furtum Heidelberg casula Toto pes an jugiter pe.',
                'active' => '1',
                'ordernumber' => 'SW10178',
                'articleId' => '178',
                'inStock' => '84',
                'supplierName' => 'Beachdreams Clothes',
                'supplierId' => '12',
                'additionalText' => '',
                'price' => 19.95,
            ]],
            'orderNumber' => ['SW1022', true, [
                'total' => 10,
                'id' => '779',
                'name' => 'Magnete ABC',
                'description' => 'Cautus Plura hac res Gens Censeo, bos Os, dissemino hac vae ter Consonum nam lacrima increpo rogo. evoco tremo bene Corrumpo .',
                'active' => '1',
                'ordernumber' => 'SW10220',
                'articleId' => '226',
                'inStock' => '150',
                'supplierName' => 'Das blaue Haus',
                'supplierId' => '8',
                'additionalText' => '',
                'price' => 3.99,
            ]],
            'supplierName' => ['Sasse', true, [
                'total' => 10,
                'id' => '123',
                'name' => 'Münsterländer Lagerkorn 32%',
                'description' => '',
                'active' => '1',
                'ordernumber' => 'SW10002.1',
                'articleId' => '2',
                'inStock' => '15',
                'supplierName' => 'Feinbrennerei Sasse',
                'supplierId' => '2',
                'additionalText' => '1,5 Liter',
                'price' => 59.99,
            ]],
            'productName' => ['sommer', true, [
                'total' => 10,
                'id' => '364',
                'name' => 'Sommer Sandale Ocean Blue 36',
                'description' => 'Scelestus nam Comiter, tepesco ansa per ferox for Expiscor. Ex accuse homo avaritia sudo Gandavum.Sem furca pica.',
                'active' => '1',
                'ordernumber' => 'SW10160.1',
                'articleId' => '160',
                'inStock' => '19',
                'supplierName' => 'Beachdreams Clothes',
                'supplierId' => '12',
                'additionalText' => '36',
                'price' => 29.99,
            ]],
            'productName not exists' => ['lorem', false],
            'productName not mapped to category' => ['Bikini Ocean Blue', true, [
                'total' => 1,
                'id' => '297',
                'name' => 'Bikini Ocean Blue',
                'description' => 'Commodo cum mel voluptarius Pariter modicus opto coepto, maligo spes Resono Curvo escendo adsum per Frutex, ubi ait animadve.',
                'active' => '1',
                'ordernumber' => 'SW10150',
                'articleId' => '150',
                'inStock' => '45',
                'supplierName' => 'Beachdreams Clothes',
                'supplierId' => '12',
                'additionalText' => '',
                'price' => 9.99,
            ]],
        ];
    }
}
