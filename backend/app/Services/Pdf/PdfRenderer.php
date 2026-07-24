<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfRenderer
{
    public function __construct(private readonly ArabicShaper $arabicShaper)
    {
    }

    public function render(string $view, array $data, string $filename): Response
    {
        $html = view($view, $data)->render();
        $html = $this->arabicShaper->shapeHtml($html);

        return Pdf::loadHTML($html)->download($filename);
    }
}
