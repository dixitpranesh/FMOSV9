<?php

declare(strict_types=1);

namespace Fmos\Support;

/**
 * Minimal PDF writer (text + rectangles) — no external dependency.
 */
final class SimplePdf
{
    /** @var list<array{w:float,h:float,ops:list<string>}> */
    private array $pages = [];
    private int $page = -1;

    public function addPage(float $widthPt = 841.89, float $heightPt = 595.28): void
    {
        $this->pages[] = ['w' => $widthPt, 'h' => $heightPt, 'ops' => []];
        $this->page = count($this->pages) - 1;
    }

    public function setFillColor(int $r, int $g, int $b): void
    {
        $this->op(sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255));
    }

    public function setStrokeColor(int $r, int $g, int $b): void
    {
        $this->op(sprintf('%.3f %.3f %.3f RG', $r / 255, $g / 255, $b / 255));
    }

    public function setLineWidth(float $w): void
    {
        $this->op(sprintf('%.2f w', $w));
    }

    public function rect(float $x, float $y, float $w, float $h, bool $fill = false, bool $stroke = true): void
    {
        $mode = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');
        $this->op(sprintf('%.2f %.2f %.2f %.2f re %s', $x, $y, $w, $h, $mode));
    }

    public function text(float $x, float $y, string $text, float $size = 10): void
    {
        $safe = $this->escape($text);
        $this->op('BT');
        $this->op(sprintf('/F1 %.2f Tf', $size));
        $this->op(sprintf('1 0 0 1 %.2f %.2f Tm (%s) Tj', $x, $y, $safe));
        $this->op('ET');
    }

    public function output(): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $pageObjIds = [];
        $contentObjIds = [];
        // obj 1 = catalog, obj 2 = pages (filled later)
        $nextId = 3;
        foreach ($this->pages as $i => $page) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $contentObjIds[$i] = $contentId;
            $pageObjIds[$i] = $pageId;
            $kids[] = $pageId . ' 0 R';
        }
        $fontId = $nextId++;
        $objects[0] = '<< /Type /Catalog /Pages 2 0 R >>';
        // We'll assemble with numeric keys carefully
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($this->pages));
        $objs[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($this->pages as $i => $page) {
            $stream = "q\n" . implode("\n", $page['ops']) . "\nQ";
            $contentId = $contentObjIds[$i];
            $pageId = $pageObjIds[$i];
            $objs[$contentId] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($stream), $stream);
            $objs[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << /F1 %d 0 R >> >> >>',
                $page['w'],
                $page['h'],
                $contentId,
                $fontId
            );
        }

        ksort($objs);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objs as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $maxId = max(array_keys($objs));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $off = $offsets[$i] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function op(string $op): void
    {
        if ($this->page < 0) {
            $this->addPage();
        }
        $this->pages[$this->page]['ops'][] = $op;
    }

    private function escape(string $text): string
    {
        $text = \Fmos\Support\TextNormalizer::ascii($text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
