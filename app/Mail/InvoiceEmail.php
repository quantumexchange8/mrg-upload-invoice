<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoiceEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $invoice;

    // Constructor to accept the invoice data
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        // Setting up the subject
        $subject = 'Invoice ' . $this->invoice->doc_no . ' - Payment Reminder';

        // HTML content for the email with strong tags for data emphasis
        $htmlContent = "
            <html>
            <body>
                <h1>Invoice: <strong>{$this->invoice->doc_no}</strong></h1>
                <p>Dear Customer,</p>
                <p>We would like to remind you about your last 10% payment for invoice <strong>{$this->invoice->doc_no}</strong>. Below are the details:</p>
                <ul>
                    <li>Invoice Number: <strong>{$this->invoice->doc_no}</strong></li>
                    <li>Due Date: <strong>{$this->invoice->due_date}</strong></li>
                </ul>
                <p>Please make the payment at your earliest convenience.</p>
                <p>Thank you for using our services!</p>
            </body>
            </html>
        ";

        // Build the email with subject and HTML body content
        return $this->subject($subject)
                    ->html($htmlContent);
    }
}
