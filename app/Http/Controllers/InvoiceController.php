<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Invoice;
use App\Mail\InvoiceEmail;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use App\Imports\InvoicesImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvoiceListingExport;

class InvoiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Invoice/Invoice');
    }

    public function getInvoicelisting(Request $request)
    {

        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); //only() extract parameters in lazyEvent

            $query = Invoice::query();

            // Handle search functionality
            $search = $data['filters']['global'];
            if ($search) {
                $query->where(function ($query) use ($search) {
                    // $query->whereHas('downline', function ($query) use ($search) {
                    //     $query->where('name', 'like', '%' . $search . '%')
                    //         ->orWhere('email', 'like', '%' . $search . '%')
                    //         ->orWhere('id_number', 'like', '%' . $search . '%');
                    // })
                    // ->orWhereHas('upline', function ($query) use ($search) {
                    //     $query->where('name', 'like', '%' . $search . '%')
                    //         ->orWhere('email', 'like', '%' . $search . '%')
                    //         ->orWhere('id_number', 'like', '%' . $search . '%');
                    // })
                    $query->where('doc_no', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            $startDate = $data['filters']['start_date'];
            $endDate = $data['filters']['end_date'];
            
            if ($startDate && $endDate) {
                $start_date = Carbon::parse($startDate)->addDay()->startOfDay();
                $end_date = Carbon::parse($endDate)->addDay()->endOfDay();
    
                $query->whereBetween('created_at', [$start_date, $end_date]);
            } else {
                $query->whereDate('created_at', '>=', '2020-01-01');
            }
        
            $startClosedDate = $data['filters']['start_due_date'];
            $endClosedDate = $data['filters']['end_due_date'];

            if ($startClosedDate && $endClosedDate) {
                $start_due_date = Carbon::parse($startClosedDate)->addDay()->startOfDay();
                $end_due_date = Carbon::parse($endClosedDate)->addDay()->endOfDay();

                $query->whereBetween('due_date', [$start_due_date, $end_due_date]);
            } else {
                $query->whereDate('due_date', '>=', '2020-01-01');
            }

            // Handle sorting
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            // Handle pagination
            $rowsPerPage = $data['rows'] ?? 15; // Default to 15 if 'rows' not provided
                    
            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                // Check if there are selected invoices for export
                $selectedIds = $request->input('selected_ids', default: []);

                if (!empty($selectedIds)) {
                    // If selected invoices are provided, filter by selected IDs
                    $invoiceListing = $query->whereIn('id', $selectedIds)->with('items')->get();
                } else {
                    // Otherwise, fetch all invoices
                    $invoiceListing = $query->with('items')->get();
                }

                return Excel::download(new InvoiceListingExport($invoiceListing), now() . '-invoice-report.xlsx');
            }

            $invoices = $query->paginate($rowsPerPage);
        }
        
        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);

    }

    public function getInvoiceItems(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);
            $invoiceId = $data['invoice_id'] ?? null;
    
            $query = InvoiceItem::where('invoice_id', $invoiceId);
    
            // // Search filter
            // $search = $data['filters']['global'];
            // if ($search) {
            //     $query->where(function ($query) use ($search) {
            //         $query->whereHas('invoice', function ($query) use ($search) {
            //             $query->where('email', 'like', "%{$search}%")
            //                 ->orWhere('phone', 'like', "%{$search}%");
            //         })
            //         ->orWhere('doc_no', 'like', "%{$search}%")
            //         ->orWhere('code', 'like', "%{$search}%");
            //     });
            // }
    
            // // Date filters
            // $startDate = $data['filters']['start_date'];
            // $endDate = $data['filters']['end_date'];
            // if ($startDate && $endDate) {
            //     $query->whereBetween('created_at', [
            //         Carbon::parse($startDate)->addDay()->startOfDay(),
            //         Carbon::parse($endDate)->addDay()->endOfDay(),
            //     ]);
            // } else {
            //     $query->whereDate('created_at', '>=', '2020-01-01');
            // }
    
            // $startClosedDate = $data['filters']['start_due_date'];
            // $endClosedDate = $data['filters']['end_due_date'];
            // if ($startClosedDate && $endClosedDate) {
            //     $query->whereBetween('due_date', [
            //         Carbon::parse($startClosedDate)->addDay()->startOfDay(),
            //         Carbon::parse($endClosedDate)->addDay()->endOfDay(),
            //     ]);
            // } else {
            //     $query->whereDate('due_date', '>=', '2020-01-01');
            // }
    
            // // Sorting
            // if ($data['sortField'] && $data['sortOrder']) {
            //     $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
            //     $query->orderBy($data['sortField'], $order);
            // } else {
            //     $query->orderByDesc('created_at');
            // }
    
            $invoiceItems = $query->get();
    
            $formattedItems = [];
            foreach ($invoiceItems as $item) {
                $formattedItems[] = [
                    'id' => $item->id,
                    'doc_no' => $item->doc_no,
                    'code' => $item->code,
                    'description_hdr' => $item->description_hdr,
                    'seq' => $item->seq,
                    'description_dtl' => $item->description_dtl,
                    'qty' => $item->qty,
                    'uom' => $item->uom,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                    'item_code' => $item->item_code,
                    'account' => $item->account,
                    'due_date' => $item->due_date,
                ];
            }
    
            return response()->json([
                'success' => true,
                'data' => $formattedItems,
            ]);
        }
    }
        
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx,ods',
        ]);

        // Get the uploaded file
        $file = $request->file('file');

        try {
            // Process the file directly into an array using the InvoicesImport class
            Excel::import(new InvoicesImport, $file);
    
            // After the import process finishes, redirect with a success message
            // return redirect()->back()->with('success', 'Invoices and items uploaded successfully!');
            return redirect()->back()->with('toast', [
                'title' => 'Invoices and items uploaded successfully!',
                'type' => 'success'
            ]);
    
        } catch (\Exception $e) {
            // If an error occurs during the import, return an error message
            // return redirect()->back()->with('error', 'An error occurred while uploading the invoices: ' . $e->getMessage());
            return redirect()->back()->with('toast', [
                'title' => 'An error occurred while uploading the invoices!',
                'type' => 'error'
            ]);

        }
    }

    public function sendEmails(Request $request)
    {
        $invoiceIds = $request->invoice_ids;  // Expecting an array of invoice IDs
        $invoices = Invoice::whereIn('id', $invoiceIds)->get();  // Get the selected invoices from the database
    
        try {
            // Ensure the emails are queued
            foreach ($invoices as $invoice) {
                try {
                    // Queue each email to be sent later
                    Mail::to($invoice->email)->send(new InvoiceEmail($invoice));
                } catch (\Exception $e) {
                    // If there’s an error with a specific email, log the error and continue
                    Log::error("Failed to send email for invoice {$invoice->doc_no}: " . $e->getMessage());
                    // Optionally, add specific error handling for individual emails
                }
            }
    
            // After queuing all emails, immediately return success
            return redirect()->back()->with('toast', [
                'title' => 'Emails have been queued successfully!',
                'type' => 'success'
            ]);
    
        } catch (\Exception $e) {
            // If there's a general error, show the error message
            Log::error("An error occurred while queuing the emails: " . $e->getMessage());
            return redirect()->back()->with('toast', [
                'title' => 'An error occurred while queuing the emails!',
                'type' => 'error'
            ]);
        }
    }
            
}
