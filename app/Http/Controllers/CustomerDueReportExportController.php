<?php

namespace App\Http\Controllers;

use App\Services\CustomerDuePdfReport;
use Symfony\Component\HttpFoundation\Response;

class CustomerDueReportExportController extends Controller
{
    public function __invoke(CustomerDuePdfReport $report): Response
    {
        abort_unless(auth()->user()?->canViewCustomerDues(), 403);

        $filename = 'customer-dues-report-'.now()->format('Y-m-d').'.pdf';

        return response($report->generate(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
