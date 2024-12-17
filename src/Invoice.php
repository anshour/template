<?php

declare(strict_types=1);

namespace Inisiatif\Package\Template;

use Barryvdh\DomPDF\PDF;
use Inisiatif\Package\Template\Bridge\Donation;
use Inisiatif\Package\Template\Bridge\Donor;
use Illuminate\Http\Response;

final class Invoice
{
    private PDF $pdf;
    private string $paperSize = 'A4';

    public function __construct(PDF $pdf)
    {
        $this->pdf = $pdf;
    }

    public function make(Donor $donor, Donation $donation, $withSignature = true, array $details): self
    {
        $this->pdf->loadView('inisiatif::prints.invoice', compact('donation', 'donor', 'details', 'withSignature'))
            ->setPaper($this->paperSize);

        return $this;
    }

    public function download(string $fileName): Response
    {
        return new Response($this->output(), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' =>  'attachment; filename="' . $fileName . '"'
        ));
    }

    public function inline(string $fileName): Response
    {
        return new Response($this->output(), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ));
    }

    public function output(): string
    {
        $this->protect();

        return $this->pdf->output();
    }

    private function protect()
    {
        $dompdf = $this->pdf->getDomPDF();

        $dompdf->render();

        $pin = config('donation.modify_pdf_pin', 'IZI_PIN');

        /** @var CPDF $canvas */
        $canvas = $dompdf->getCanvas();
        $canvas->get_cpdf()->setEncryption('', $pin, [
            'print',
        ], 3);
    }
}
