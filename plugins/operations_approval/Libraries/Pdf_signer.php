<?php

namespace operations_approval\Libraries;

/**
 * Shared FPDI stamping logic behind the e-signature feature, used by both
 * the mobile API (Operations_api::signAttachment, an uploaded image file)
 * and the web UI (Operations::signAttachment, a canvas-drawn signature
 * decoded straight into memory) so the two never drift apart.
 */
class Pdf_signer
{
    /**
     * @param string $signatureSource Anything TCPDF's Image() accepts as
     *                                 $file: a path, or raw image bytes
     *                                 prefixed with '@' (no temp file needed).
     * @param int $page Page to stamp, or 0 for "the last page".
     * @return string The signed PDF's filename inside $destDir.
     */
    public static function stamp(string $sourcePath, string $signatureSource, int $page, float $x, float $y, float $w, float $h, string $destDir, string $destFileNameBase): string
    {
        require_once PLUGINPATH . 'operations_approval/Vendor/autoload.php';

        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pageCount = $pdf->setSourceFile($sourcePath);
        if ($page <= 0) $page = $pageCount;
        if ($page > $pageCount) throw new \DomainException("This document only has {$pageCount} page(s)");

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
            if ($pageNo === $page) {
                $pdf->Image($signatureSource, $x * $size['width'], $y * $size['height'], $w * $size['width'], $h * $size['height'], 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
            }
        }

        $destDir = rtrim($destDir, '/\\');
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) throw new \RuntimeException('Could not prepare storage');
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '-', $destFileNameBase) . '-signed-' . bin2hex(random_bytes(4)) . '.pdf';
        $pdf->Output($destDir . '/' . $fileName, 'F');
        return $fileName;
    }
}
