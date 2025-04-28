<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\InvoicesImport;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
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
            return redirect()->back()->with('success', 'Invoices and items uploaded successfully!');
            // return redirect()->back()->with('toast', [
            //     'title' => 'test',
            //     'type' => 'success'
            // ]);
    
        } catch (\Exception $e) {
            // If an error occurs during the import, return an error message
            return redirect()->back()->with('error', 'An error occurred while uploading the invoices: ' . $e->getMessage());
        }
    }
}
