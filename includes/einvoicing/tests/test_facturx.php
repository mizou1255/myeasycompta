<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/../model/InvoiceModel.php';
require_once __DIR__ . '/../facturx/FacturXGenerator.php';

$invoiceRef = new ReflectionClass(ECWP\EInvoicing\Model\InvoiceModel::class);
$invoice = $invoiceRef->newInstanceWithoutConstructor();

$set = function (string $property, $value) use ($invoiceRef, $invoice) {
    $p = $invoiceRef->getProperty($property);
    $p->setAccessible(true);
    $p->setValue($invoice, $value);
};

$set('id', 1);
$set('number', 'INV_0001');
$set('issueDate', new DateTime('2025-01-01'));
$set('dueDate', new DateTime('2025-01-31'));
$set('typeCode', '380');
$set('currency', 'EUR');
$set('status', 'draft');

$set('seller', [
    'name' => 'ACME SARL',
    'siren' => '12345678900011',
    'tax_number' => 'FR123456789',
    'vat_number' => 'FR123456789',
    'address' => [
        'line1' => '1 rue de Paris',
        'postal_code' => '75001',
        'city' => 'Paris',
        'country' => 'FR',
    ],
]);

$set('buyer', [
    'name' => 'CLIENT SAS',
    'siren' => '98765432100022',
    'tax_number' => 'FR987654321',
    'vat_number' => 'FR987654321',
    'address' => [
        'line1' => '10 avenue de Lyon',
        'postal_code' => '69001',
        'city' => 'Lyon',
        'country' => 'FR',
    ],
]);

$set('lines', [
    [
        'name' => 'Prestation',
        'ref' => 'PRESTA-1',
        'description' => 'Prestation de service',
        'quantity' => 1.0,
        'unit_price' => 100.00,
        'vat_rate' => 20.0,
        'discount' => 0.0,
        'total_price' => 100.00,
        'total_amount' => 120.00,
    ],
]);

$set('totals', [
    'amount_excl_tax' => 100.00,
    'amount_incl_tax' => 120.00,
]);

$generator = new ECWP\EInvoicing\FacturX\FacturXGenerator();
$xml = $generator->generateXml($invoice);

$validator = new Atgp\FacturX\XsdValidator();
$validator->validateWithException($xml, 'en16931');

echo "OK\n";
