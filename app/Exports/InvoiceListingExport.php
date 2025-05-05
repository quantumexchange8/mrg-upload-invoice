<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceListingExport implements FromCollection, WithHeadings
{
    protected $invoices;

    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    public function headings(): array
    {
        return [
            trans('public.date'),
            trans('public.doc_no'),
            trans('public.doc_date'),
            trans('public.code'),
            trans('public.company_name'),
            trans('public.address_1'),
            trans('public.address_2'),
            trans('public.postcode'),
            trans('public.city'),
            trans('public.state'),
            trans('public.country'),
            trans('public.terms'),
            trans('public.due_date'),
            trans('public.phone'),
            trans('public.email'),
            trans('public.seq'),
            trans('public.account'),
            trans('public.description_hdr'),
            trans('public.description_dtl'),
            trans('public.item_code'),
            trans('public.qty'),
            trans('public.uom'),
            trans('public.unit_price'),
            trans('public.amount'),
        ];
    }

    public function collection(): Collection
    {
        $rows = [];

        foreach ($this->invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $rows[] = [
                    $invoice->created_at->format('Y/m/d'),
                    $invoice->doc_no,
                    $invoice->doc_date,
                    $invoice->code,
                    $invoice->company_name,
                    $invoice->address1,
                    $invoice->address2,
                    $invoice->postcode,
                    $invoice->city,
                    $invoice->state,
                    $invoice->country,
                    $invoice->terms,
                    $invoice->due_date,
                    $invoice->phone,
                    $invoice->email,
                    $item->seq,
                    $item->account,
                    $item->description_hdr,
                    $item->description_dtl,
                    $item->item_code,
                    $item->qty,
                    $item->uom,
                    $item->unit_price,
                    $item->amount,
                ];
            }
        }

        return collect($rows);
    }
}
