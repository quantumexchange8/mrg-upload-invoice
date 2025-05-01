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
            // if ($request->has('exportStatus') && $request->exportStatus) {
            //     $rebateHistory = $query->get();

            //     return Excel::download(new RebateHistoryExport($rebateHistory), now() . '-rebate-report.xlsx');
            // }

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
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); // Extract parameters from lazyEvent
            $invoiceId = $data['invoice_id'] ?? null;
    
            // Build the query to fetch invoice items
            $query = InvoiceItem::with('invoice:id,email,phone')
                        ->where('invoice_id', $invoiceId);
    
            // Handle search functionality
            $search = $data['filters']['global'];
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('invoice', function ($query) use ($search) {
                        $query->where('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhere('doc_no', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
                });
            }
    
            // Handle date range filtering
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
    
            // Fetch the results
            $invoiceItems = $query->get();
    
            // Initialize an array to group invoice items by doc_date
            $invoiceReports = [];
    
            // Group the invoice items by doc_date using foreach
            foreach ($invoiceItems as $item) {
                // Format doc_date to group by month/year (YYYY-MM format)
                $docDate = Carbon::parse($item->doc_date)->format('m/Y'); // Example: '05/2023'
    
                // Initialize the group array for the specific doc_date if not exists
                if (!isset($invoiceReports[$docDate])) {
                    $invoiceReports[$docDate] = [
                        'doc_date' => $docDate,
                        'total_amount' => 0, // Initialize any other fields you need
                        'invoice_items' => []
                    ];
                }
    
                // Add the invoice item details to the grouped array
                $invoiceReports[$docDate]['total_amount'] += $item->amount; // Assuming there's an amount field
                $invoiceReports[$docDate]['invoice_items'][] = [
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
    
            // Prepare the final response
            return response()->json([
                'success' => true,
                'data' => array_values($invoiceReports), // Re-index the array to avoid key issues
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
