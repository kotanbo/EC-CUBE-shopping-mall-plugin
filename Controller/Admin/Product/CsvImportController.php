<?php

namespace Plugin\ShoppingMall\Controller\Admin\Product;

use Eccube\Common\Constant;
use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\Product;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CsvImportService;
use Eccube\Util\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CsvImportController extends AbstractCsvImportController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * CsvImportController constructor.
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * CSVインポート
     *
     * @Route("/%eccube_admin_route%/product/shopping_mall/csv_import", name="shopping_mall_admin_product_csv_import")
     * @Template("@ShoppingMall/admin/Product/csv_product.twig")
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function importCsv(Request $request)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $columnConfig = $this->getColumnConfig();
        $errors = [];

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $form['import_file']->getData();

                if (!empty($formFile)) {
                    $csv = $this->getImportData($formFile);

                    try {
                        $this->entityManager->getConfiguration()->setSQLLogger(null);
                        $this->entityManager->getConnection()->beginTransaction();

                        $this->loadCsv($csv, $errors);

                        if ($errors) {
                            $this->entityManager->getConnection()->rollBack();
                        } else {
                            $this->entityManager->flush();
                            $this->entityManager->getConnection()->commit();

                            $this->addInfo('admin.common.csv_upload_complete', 'admin');
                        }
                    } finally {
                        $this->removeUploadedFile();
                    }
                }
            }
        }

        return [
            'form' => $form->createView(),
            'headers' => $columnConfig,
            'errors' => $errors,
        ];
    }

    /**
     * @param CsvImportService $csv
     * @param array &$errors
     */
    protected function loadCsv(CsvImportService $csv, &$errors)
    {
        $columnConfig = $this->getColumnConfig();

        if ($csv === false) {
            $errors[] = trans('admin.common.csv_invalid_format');
        }

        // 必須カラムの確認
        $requiredColumns = array_map(function ($value) {
            return $value['name'];
        }, array_filter($columnConfig, function ($value) {
            return $value['required'];
        }));
        $csvColumns = $csv->getColumnHeaders();
        if (count(array_diff($requiredColumns, $csvColumns)) > 0) {
            $errors[] = trans('admin.common.csv_invalid_format');

            return;
        }

        // 行数の確認
        $size = count($csv);
        if ($size < 1) {
            $errors[] = trans('admin.common.csv_invalid_format');

            return;
        }

        $columnNames = array_combine(array_keys($columnConfig), array_column($columnConfig, 'name'));

        foreach ($csv as $index => $row) {
            $line = $index + 1;
            $columnNameId = $columnNames['id'];
            // IDがなければエラー
            if (!isset($row[$columnNameId])) {
                $errors[] = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $columnNameId]);
                continue;
            }

            /* @var Product $Product */
            $Product = is_numeric($row[$columnNameId]) ? $this->productRepository->find($row[$columnNameId]) : null;

            // 存在しないIDはエラー
            if (is_null($Product)) {
                $errors[] = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $columnNameId]);
                continue;
            }

            $externalSalesUrl = isset($row[$columnNames['external_sales_url']]) ? $row[$columnNames['external_sales_url']] : null;
            if (StringUtil::isBlank($externalSalesUrl)) {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $columnNames['external_sales_url']]);
                $errors[] = $message;
                continue;
            }
            $Product->setExternalSalesUrl($externalSalesUrl);

            $shouldShowPrice = isset($row[$columnNames['should_show_price']]) ? $row[$columnNames['should_show_price']] : null;
            if ($shouldShowPrice == (string) Constant::DISABLED) {
                $Product->setShouldShowPrice(false);
            } elseif ($shouldShowPrice == (string) Constant::ENABLED) {
                $Product->setShouldShowPrice(true);
            } else {
                $message = trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $columnNames['should_show_price']]);
                $errors[] = $message;
                continue;
            }

            $this->entityManager->persist($Product);
            $this->productRepository->save($Product);
        }
    }

    /**
     * アップロード用CSV雛形ファイルダウンロード
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @Route("/%eccube_admin_route%/product/shopping_mall/csv_template", name="shopping_mall_admin_product_csv_template")
     */
    public function downloadCsvTemplate(Request $request)
    {
        $columns = array_column($this->getColumnConfig(), 'name');

        return $this->sendTemplateResponse($request, $columns, 'shop_product.csv');
    }

    /**
     * @return array
     */
    protected function getColumnConfig()
    {
        return [
            'id' => [
                'name' => trans('shopping_mall.admin.product.csv.product_id_col'),
                'description' => trans('shopping_mall.admin.product.csv.product_id_description'),
                'required' => true,
            ],
            'external_sales_url' => [
                'name' => trans('shopping_mall.admin.product.csv.external_sales_url_col'),
                'description' => trans('shopping_mall.admin.product.csv.external_sales_url_description'),
                'required' => true,
            ],
            'should_show_price' => [
                'name' => trans('shopping_mall.admin.product.csv.should_show_price_col'),
                'description' => trans('shopping_mall.admin.product.csv.should_show_price_description', [
                    '%disabled%' => Constant::DISABLED,
                    '%enabled%' => Constant::ENABLED,
                ]),
                'required' => true,
            ],
        ];
    }
}
