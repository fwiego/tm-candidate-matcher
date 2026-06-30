<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Spatie\PdfToText\Pdf;

class ResumeParserService
{
    /**
     * Extract plain text from a resume file (PDF or DOCX).
     *
     * @throws RuntimeException if the file type is unsupported or parsing fails.
     */
    public function extractText(string $absolutePath, string $originalExtension): string
    {
        $extension = strtolower($originalExtension);

        return match ($extension) {
            'pdf' => $this->extractFromPdf($absolutePath),
            'docx' => $this->extractFromDocx($absolutePath),
            default => throw new RuntimeException("Неподдерживаемый формат файла: .{$extension}"),
        };
    }

    /**
     * Extract text from a PDF file using the `pdftotext` binary (via spatie/pdf-to-text).
     */
    protected function extractFromPdf(string $absolutePath): string
    {
        try {
            return trim(Pdf::getText($absolutePath));
        } catch (\Throwable $e) {
            throw new RuntimeException('Не удалось извлечь текст из PDF: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Extract text from a DOCX file using PhpWord.
     */
    protected function extractFromDocx(string $absolutePath): string
    {
        try {
            $phpWord = IOFactory::load($absolutePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractElementText($element)."\n";
                }
            }

            return trim($text);
        } catch (\Throwable $e) {
            throw new RuntimeException('Не удалось извлечь текст из DOCX: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Recursively extract text from a PhpWord element (text runs, paragraphs, tables, etc.).
     */
    protected function extractElementText(mixed $element): string
    {
        if (method_exists($element, 'getText')) {
            $text = $element->getText();

            return is_string($text) ? $text : '';
        }

        if (method_exists($element, 'getElements')) {
            $text = '';

            foreach ($element->getElements() as $child) {
                $text .= $this->extractElementText($child).' ';
            }

            return $text;
        }

        if (method_exists($element, 'getRows')) {
            // Table element.
            $text = '';

            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractElementText($cellElement).' ';
                    }
                }
            }

            return $text;
        }

        return '';
    }
}