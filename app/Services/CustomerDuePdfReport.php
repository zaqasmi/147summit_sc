<?php

namespace App\Services;

use App\Models\CustomerDue;
use Illuminate\Support\Collection;

class CustomerDuePdfReport
{
    private const PAGE_WIDTH = 842;

    private const PAGE_HEIGHT = 595;

    private const MARGIN = 36;

    private const ROW_HEIGHT = 20;

    public function generate(): string
    {
        $rows = CustomerDue::query()
            ->orderBy('customer_name')
            ->get();

        $pages = $this->buildPages($rows);

        return $this->buildPdf($pages);
    }

    /**
     * @param  Collection<int, CustomerDue>  $rows
     * @return array<int, string>
     */
    private function buildPages(Collection $rows): array
    {
        $pages = [];
        $chunks = $rows->chunk(20);

        if ($chunks->isEmpty()) {
            $chunks = collect([collect()]);
        }

        foreach ($chunks as $pageNumber => $chunk) {
            $isLastPage = $pageNumber === $chunks->count() - 1;
            $pages[] = $this->pageContent($chunk, $rows, $pageNumber + 1, $isLastPage);
        }

        return $pages;
    }

    /**
     * @param  Collection<int, CustomerDue>  $rows
     * @param  Collection<int, CustomerDue>  $allRows
     */
    private function pageContent(Collection $rows, Collection $allRows, int $pageNumber, bool $isLastPage): string
    {
        $content = [];
        $content[] = $this->text('Customer Dues Report', 36, 552, 18, true);
        $content[] = $this->text('Generated: '.now()->format('d M Y h:i A'), 36, 530, 9);
        $content[] = $this->text('Page '.$pageNumber, 760, 530, 9);

        $headers = ['Customer', 'Opening', 'Dues added', 'Paid', 'Balance due'];
        $widths = [270, 120, 130, 120, 130];
        $x = self::MARGIN;
        $y = 500;

        foreach ($headers as $index => $header) {
            $content[] = $this->rect($x, $y - 15, $widths[$index], self::ROW_HEIGHT);
            $content[] = $this->text($header, $x + 5, $y - 2, 9, true);
            $x += $widths[$index];
        }

        $y -= self::ROW_HEIGHT;

        foreach ($rows as $row) {
            $x = self::MARGIN;
            $values = [
                $this->truncate($row->customer_name, 34),
                $this->money($row->opening_balance),
                $this->money($row->total_charged),
                $this->money($row->total_paid),
                $this->money($row->balance_due),
            ];

            foreach ($values as $index => $value) {
                $content[] = $this->rect($x, $y - 15, $widths[$index], self::ROW_HEIGHT);
                $content[] = $this->text($value, $x + 5, $y - 2, 9);
                $x += $widths[$index];
            }

            $y -= self::ROW_HEIGHT;
        }

        if ($isLastPage) {
            $x = self::MARGIN;
            $totals = [
                'Total',
                $this->money($allRows->sum(fn (CustomerDue $due): float => (float) $due->opening_balance)),
                $this->money($allRows->sum(fn (CustomerDue $due): float => (float) $due->total_charged)),
                $this->money($allRows->sum(fn (CustomerDue $due): float => (float) $due->total_paid)),
                $this->money($allRows->sum(fn (CustomerDue $due): float => (float) $due->balance_due)),
            ];

            foreach ($totals as $index => $total) {
                $content[] = $this->rect($x, $y - 15, $widths[$index], self::ROW_HEIGHT);
                $content[] = $this->text($total, $x + 5, $y - 2, 9, true);
                $x += $widths[$index];
            }
        }

        return implode("\n", $content);
    }

    /**
     * @param  array<int, string>  $pages
     */
    private function buildPdf(array $pages): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageObjectIds = [];
        $contentObjectIds = [];
        $nextObjectId = 4;

        foreach ($pages as $page) {
            $pageObjectIds[] = $nextObjectId++;
            $contentObjectIds[] = $nextObjectId++;
        }

        $kids = collect($pageObjectIds)
            ->map(fn (int $id): string => $id.' 0 R')
            ->implode(' ');

        $objects[] = '<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pages).' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($pages as $index => $page) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObjectIds[$index].' 0 R >>';
            $objects[] = '<< /Length '.strlen($page)." >>\nstream\n".$page."\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    private function text(string $text, int $x, int $y, int $size = 10, bool $bold = false): string
    {
        $weight = $bold ? '0 Tr' : '0 Tr';

        return sprintf(
            'BT /F1 %d Tf %s %d %d Td (%s) Tj ET',
            $size,
            $weight,
            $x,
            $y,
            $this->escape($text),
        );
    }

    private function rect(int $x, int $y, int $width, int $height): string
    {
        return sprintf('%d %d %d %d re S', $x, $y, $width, $height);
    }

    private function escape(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function truncate(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length - 3).'...' : $text;
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
