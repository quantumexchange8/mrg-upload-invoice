<?php

namespace App\Imports;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoicesImport implements ToCollection, WithHeadingRow
{
    // Helper function to parse dates
    private function parseDate($dateRaw)
    {
        return is_numeric($dateRaw)
            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRaw)->format('Y-m-d')
            : Carbon::parse($dateRaw)->format('Y-m-d');
    }

    public function collection(Collection $rows)
    {
        // Filter out any rows without doc_no
        $rows = $rows->filter(function ($row) {
            return !empty($row['doc_no']);
        });

        $groupedData = $rows->groupBy('doc_no');
        
        foreach ($groupedData as $docNo => $invoiceItems) {
            // Access first item to get doc_date and due_date for the invoice
            $firstItem = $invoiceItems->first();

            // Handle doc_date and due_date conversion for the invoice (use the first item)
            try {
                $docDate = $this->parseDate($firstItem['doc_date']);
                $dueDate = $this->parseDate($firstItem['due_date']);
            } catch (\Exception $e) {
                Log::error('Date error: ' . $e->getMessage(), [
                    'docdate_raw' => $firstItem['doc_date'],
                    'duedate_raw' => $firstItem['due_date'],
                ]);
                continue; // Skip this invoice if dates are invalid
            }
            
            // Create or update the Invoice
            $invoice = Invoice::firstOrCreate(
                [
                    'doc_no' => $docNo,
                    'doc_date' => $docDate,
                    'code' => $firstItem['code']
                ],
                [
                    'doc_no' => $docNo,
                    'doc_date' => $docDate,
                    'code' => $firstItem['code'],
                    'company_name' => $firstItem['company_name'] ?? null,
                    'address1' => $firstItem['address1'] ?? null,
                    'address2' => $firstItem['address2'] ?? null,
                    'postcode' => $firstItem['postcode'] ?? null,
                    'city' => $firstItem['city'] ?? null,
                    'state' => $firstItem['state'] ?? null,
                    'country' => $firstItem['country'] ?? null,
                    'terms' => $firstItem['terms'] ?? null,
                    'due_date' => $dueDate,
                    'phone' => $firstItem['phone'] ?? null,
                    'email' => $firstItem['email'] ?? null,
                ]
            );

            // Loop through invoice items and create each one individually
            foreach ($invoiceItems as $item) {
                try {
                    $itemDocDate = $this->parseDate($item['doc_date']);
                    $itemDueDate = $this->parseDate($item['due_date']);
                    
                    // Create the Invoice Item
                    InvoiceItem::firstOrCreate(
                        [
                        'invoice_id' => $invoice->id,
                        'doc_no' => $item['doc_no'],
                        'doc_date' => $itemDocDate,
                        'code' => $item['code'],
                        'description_hdr' => $item['description_hdr'] ?? null,
                        'seq' => $item['seq'] ?? null,
                        'description_dtl' => $item['description_dtl'] ?? null,
                        'qty' => $item['qty'] ?? null,
                        'uom' => $item['uom'] ?? null,
                        'unit_price' => $item['unit_price'] ?? null,
                        'amount' => $item['amount'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'account' => $item['account'] ?? null,
                        'due_date' => $itemDueDate,
                        ],
                        [
                        'invoice_id' => $invoice->id,
                        'doc_no' => $item['doc_no'],
                        'doc_date' => $itemDocDate,
                        'code' => $item['code'],
                        'description_hdr' => $item['description_hdr'] ?? null,
                        'seq' => $item['seq'] ?? null,
                        'description_dtl' => $item['description_dtl'] ?? null,
                        'qty' => $item['qty'] ?? null,
                        'uom' => $item['uom'] ?? null,
                        'unit_price' => $item['unit_price'] ?? null,
                        'amount' => $item['amount'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'account' => $item['account'] ?? null,
                        'due_date' => $itemDueDate,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error creating invoice item: ' . $e->getMessage(), [
                        'doc_no' => $item['doc_no'],
                        'item' => $item,
                    ]);
                }
            }
        }
    }
}
