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
            'pdf'  => $this->extractFromPdf($absolutePath),
            'docx' => $this->extractFromDocx($absolutePath),
            default => throw new RuntimeException("Неподдерживаемый формат файла: .{$extension}"),
        };
    }

    /**
     * Try to detect the candidate's grade from resume text.
     * First checks for explicit keywords (Junior/Middle/Senior/Lead),
     * then falls back to years-of-experience patterns.
     */
    public function detectGrade(string $text): ?string
    {
        $lower = mb_strtolower($text);

        $keywords = [
            'lead'   => ['lead', 'лид', 'tech lead', 'team lead'],
            'senior' => ['senior', 'сеньор', 'sr.', 'sr '],
            'middle' => ['middle', 'мидл', 'mid-level', 'mid level'],
            'junior' => ['junior', 'джуниор', 'jr.', 'jr '],
        ];

        foreach ($keywords as $grade => $variants) {
            foreach ($variants as $variant) {
                if (str_contains($lower, $variant)) {
                    return $grade;
                }
            }
        }

        $patterns = [
            '/(\d+)\+?\s*(?:лет|год[а]?|years?)\s*(?:of\s*)?(?:опыт[а]?|experience)/iu',
            '/(?:опыт|experience)[^\d]*(\d+)\+?\s*(?:лет|год[а]?|years?)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $years = (int) $matches[1];

                return match (true) {
                    $years >= 6 => 'lead',
                    $years >= 4 => 'senior',
                    $years >= 2 => 'middle',
                    default     => 'junior',
                };
            }
        }

        return null;
    }

    /**
     * Try to detect the candidate's location from resume text.
     * Looks for "City, Country" patterns and common location labels.
     */
    public function detectLocation(string $text): ?string
    {
        $labelPatterns = [
            '/(?:location|город|city|локация|местоположение|место\s*жительства)\s*[:\-–]?\s*([А-ЯЁA-Z][а-яёa-z]+(?:[\s,]+[А-ЯЁA-Z][а-яёa-z]+)*)/iu',
        ];

        foreach ($labelPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $location = trim($matches[1]);
                if (mb_strlen($location) >= 3 && mb_strlen($location) <= 60) {
                    return $location;
                }
            }
        }

        $preview = mb_substr($text, 0, 500);
        if (preg_match('/\b([A-ZА-ЯЁ][a-zа-яё]{2,}(?:\s[A-ZА-ЯЁ][a-zа-яё]{2,})?),\s*([A-ZА-ЯЁ][a-zа-яё]{2,}(?:\s[A-ZА-ЯЁ][a-zа-яё]{2,})?)\b/u', $preview, $matches)) {
            $exclude = ['january','february','march','april','june','july','august','september','october','november','december'];
            $city = mb_strtolower($matches[1]);

            if (! in_array($city, $exclude)) {
                return trim($matches[1].', '.$matches[2]);
            }
        }

        return null;
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