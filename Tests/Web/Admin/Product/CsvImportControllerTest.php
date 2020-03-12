<?php

namespace Plugin\ShoppingMall\Tests\Web\Admin\Product;

use Eccube\Service\CsvImportService;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Plugin\ShoppingMall\Controller\Admin\Product\CsvImportController;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvImportControllerTest extends AbstractAdminWebTestCase
{
    public function testLoadCsv()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        $Product = $this->createProduct();
        $this->entityManager->flush();
        self::assertNull($Product->getExternalSalesUrl());
        self::assertEquals(true, $Product->getShouldShowPrice());

        $url = $faker->url;
        $this->loadCsv([
            '商品ID,外部販売サイトURL,価格を表示',
            $Product->getId().','.$url.',0',
        ]);

        $this->entityManager->refresh($Product);

        self::assertEquals($url, $Product->getExternalSalesUrl());
        self::assertEquals(false, $Product->getShouldShowPrice());
    }

    public function testLoadCsv_FlippedColumns()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        $Product = $this->createProduct();
        $this->entityManager->flush();
        self::assertNull($Product->getExternalSalesUrl());
        self::assertEquals(true, $Product->getShouldShowPrice());

        $url = $faker->url;
        $this->loadCsv([
            '商品ID,価格を表示,外部販売サイトURL',
            $Product->getId().',0,'.$url,
        ]);

        $this->entityManager->refresh($Product);

        self::assertEquals($url, $Product->getExternalSalesUrl());
        self::assertEquals(false, $Product->getShouldShowPrice());
    }

    /**
     * @dataProvider loadCsvInvalidFormatProvider
     */
    public function testLoadCsv_InvalidFormat($csv, $errorMessage)
    {
        $Product = $this->createProduct();
        self::assertNull($Product->getExternalSalesUrl());
        self::assertEquals(true, $Product->getShouldShowPrice());

        $errors = $this->loadCsv(array_map(function ($row) use ($Product) {
            return preg_replace('/\{id}/', $Product->getId(), $row);
        }, $csv));

        $this->entityManager->refresh($Product);

        self::assertEquals($errors[0], $errorMessage);
    }

    public function loadCsvInvalidFormatProvider()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        return [
            [
                [
                    '外部販売サイトURL,価格を表示',
                    $faker->url.',0',
                ], 'CSVのフォーマットが一致しません',
            ],
            [
                [
                    '外部販売サイトURL,価格を表示',
                ], 'CSVのフォーマットが一致しません',
            ],
            [
                [
                    '商品ID,外部販売サイトURL,価格を表示',
                    '99999999,'.$faker->url.',0',
                ], '2行目の商品IDが存在しません',
            ],
            [
                [
                    '商品ID,外部販売サイトURL,価格を表示',
                    'x,'.$faker->url.',0',
                ], '2行目の商品IDが存在しません',
            ],
            [
                [
                    '商品ID,外部販売サイトURL,価格を表示',
                    '{id},'.$faker->url.',a',
                ], '2行目の価格を表示が設定されていません',
            ],
        ];
    }

    private function loadCsv($csvRows)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_controller_test');
        $csvContent = implode(PHP_EOL, $csvRows);

        // see https://github.com/EC-CUBE/ec-cube/pull/1781
        if ('\\' === DIRECTORY_SEPARATOR) {
            // Windows 環境では、 ロケールとファイルエンコーディングを一致させる必要がある
            setlocale(LC_ALL, '');
            if (mb_detect_encoding($csvContent) === 'UTF-8') {
                $csvContent = mb_convert_encoding($csvContent, 'SJIS-win', 'UTF-8');
            }
        }
        file_put_contents($tempFile, $csvContent);

        $csv = new CsvImportService(new \SplFileObject($tempFile));
        $csv->setHeaderRowNumber(0);

        $controller = $this->container->get(CsvImportController::class);
        $rc = new \ReflectionClass(CsvImportController::class);
        $method = $rc->getMethod('loadCsv');
        $method->setAccessible(true);
        $errors = [];
        $method->invokeArgs($controller, [$csv, &$errors]);

        $this->entityManager->flush();

        return $errors;
    }

    public function testImportCsv()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        $Product1 = $this->createProduct();
        $Product2 = $this->createProduct();
        $Product3 = $this->createProduct();
        $this->entityManager->flush();

        $url1 = $faker->url;
        $url2 = $faker->url;
        $url3 = $faker->url;
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_controller_test');
        file_put_contents($tempFile, implode(PHP_EOL, [
            '商品ID,外部販売サイトURL,価格を表示',
            $Product1->getId().','.$url1.',0',
            $Product2->getId().','.$url2.',1',
            $Product3->getId().','.$url3.',0',
        ]));

        $file = new UploadedFile($tempFile, 'shop_product.csv', 'text/csv', null, null, true);

        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('shopping_mall_admin_product_csv_import'),
            [
                'admin_csv_import' => [
                    '_token' => 'dummy',
                    'import_file' => $file,
                ],
            ],
            ['import_file' => $file]
        );

        $this->assertRegexp(
            '/CSVファイルをアップロードしました/u',
            $crawler->filter('div.alert-primary')->text()
        );

        $this->entityManager->refresh($Product1);
        self::assertEquals($url1, $Product1->getExternalSalesUrl());
        self::assertEquals(false, $Product1->getShouldShowPrice());

        $this->entityManager->refresh($Product2);
        self::assertEquals($url2, $Product2->getExternalSalesUrl());
        self::assertEquals(true, $Product2->getShouldShowPrice());

        $this->entityManager->refresh($Product3);
        self::assertEquals($url3, $Product3->getExternalSalesUrl());
        self::assertEquals(false, $Product3->getShouldShowPrice());
    }
}
