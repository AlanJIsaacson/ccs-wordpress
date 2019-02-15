<?php

use App\Model\LotSupplier;
use App\Repository\FrameworkRepository;
use App\Repository\LotRepository;
use App\Repository\LotSupplierRepository;
use App\Repository\SupplierRepository;
use App\Services\Salesforce\SalesforceApi;

add_action( 'admin_menu', 'ccs_salesforce_import_admin_menu' );

/**
 *
 */
function ccs_salesforce_import_admin_menu() {
	add_menu_page( 'Run import', 'Salesforce import', 'manage_options', '/salesforce-import', 'ccs_salesforce_import', 'dashicons-welcome-widgets-menus', 60  );
}

/**
 *
 */
function ccs_salesforce_import(){
    $imported = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        set_time_limit(120);
        $response = run_import();
        $imported = true;
    }
        // Load the view to upload a file
        require(__DIR__ . '/templates/admin.php');
        exit;
}

/**
 * Run Salesforce import
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 * @throws \ReflectionException
 */
function run_import() {

    $importCount = [
      'frameworks' => 0,
      'lots'       => 0,
      'suppliers'  => 0
    ];

    $errorCount = [
      'frameworks' => 0,
      'lots'       => 0,
      'suppliers'  => 0
    ];

    $salesforceApi = new SalesforceApi();
    
    $frameworks = $salesforceApi->getAllFrameworks();
    $frameworkRepository = new FrameworkRepository();
    $lotRepository = new LotRepository();

    foreach ($frameworks as $framework)
    {
        if (!$frameworkRepository->createOrUpdate('salesforce_id', $framework->getSalesforceId(), $framework))
        {
            $errorCount['frameworks']++;
            continue;
        }


        $importCount['frameworks']++;

        $lots = $salesforceApi->getFrameworkLots($framework->getSalesforceId());

        foreach ($lots as $lot)
        {
            if (!$lotRepository->createOrUpdate('salesforce_id', $lot->getSalesforceId(), $lot))
            {
                $errorCount['lots']++;
                continue;
            }

            $importCount['lots']++;

            $suppliers = $salesforceApi->getLotSuppliers($lot->getSalesforceId());

            $supplierRepository = new SupplierRepository();
            $lotSupplierRepository = new LotSupplierRepository();

            // Remove all the current relationships to this lot, and create fresh ones.
            $lotSupplierRepository->deleteById($lot->getSalesforceId(), 'lot_id');

            foreach ($suppliers as $supplier)
            {
                if (!$supplierRepository->createOrUpdate('salesforce_id', $supplier->getSalesforceId(), $supplier))
                {
                    $errorCount['suppliers']++;
                    continue;
                }

                $importCount['suppliers']++;
                $lotSuppler = new LotSupplier(['lot_id' => $lot->getSalesforceId(), 'supplier_id' => $supplier->getSalesforceId()]);
                $lotSupplierRepository->create($lotSuppler);
            }

        }
    }

    return $response = ['importCount' => $importCount, 'errorCount' => $errorCount];
}
