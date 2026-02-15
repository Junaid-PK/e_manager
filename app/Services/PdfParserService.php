<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfParserService
{
    public function parseBankStatement(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        $movements = [];

        $movements = $this->parseSabadellFormat($lines);

        if (empty($movements)) {
            $movements = $this->parseGenericFormat($lines);
        }

        return $movements;
    }

    private function parseSabadellFormat(array $lines): array
    {
        $movements = [];
        $datePattern = '/^(\d{2}\/\d{2}\/\d{4})/';
        $i = 0;

        while ($i < count($lines)) {
            $line = trim($lines[$i]);

            if (preg_match($datePattern, $line, $matches)) {
                $date = $matches[1];
                $concept = '';
                $valueDate = null;
                $amount = null;
                $balance = null;

                $remaining = trim(substr($line, strlen($date)));

                if (preg_match('/^(.+?)\s+(\d{2}\/\d{2}\/\d{4})\s+([\-\d\.,]+)\s+([\d\.,]+)\s*$/', $remaining, $parts)) {
                    $concept = trim($parts[1]);
                    $valueDate = $parts[2];
                    $amount = $this->parseSpanishAmount($parts[3]);
                    $balance = $this->parseSpanishAmount($parts[4]);
                } elseif (preg_match('/([\-\d\.,]+)\s+([\d\.,]+)\s*$/', $remaining, $parts)) {
                    $concept = trim(str_replace($parts[0], '', $remaining));
                    $amount = $this->parseSpanishAmount($parts[1]);
                    $balance = $this->parseSpanishAmount($parts[2]);
                } else {
                    $concept = $remaining;
                }

                $nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
                if ($nextLine && !preg_match($datePattern, $nextLine) && !empty($nextLine)) {
                    if (preg_match('/([\-\d\.,]+)\s+([\d\.,]+)\s*$/', $nextLine, $numParts)) {
                        if (!$amount) {
                            $amount = $this->parseSpanishAmount($numParts[1]);
                            $balance = $this->parseSpanishAmount($numParts[2]);
                        }
                        $extraConcept = trim(str_replace($numParts[0], '', $nextLine));
                        if ($extraConcept) {
                            $concept .= ' ' . $extraConcept;
                        }
                        $i++;
                    } else {
                        $concept .= ' ' . $nextLine;
                        $i++;
                    }
                }

                if ($amount !== null) {
                    $deposit = $amount > 0 ? $amount : null;
                    $withdrawal = $amount < 0 ? abs($amount) : null;

                    $movements[] = [
                        'date' => $this->convertDate($date),
                        'value_date' => $valueDate ? $this->convertDate($valueDate) : null,
                        'concept' => trim($concept),
                        'deposit' => $deposit,
                        'withdrawal' => $withdrawal,
                        'balance' => $balance,
                    ];
                }
            }
            $i++;
        }

        return $movements;
    }

    private function parseGenericFormat(array $lines): array
    {
        $movements = [];
        $datePattern = '/(\d{2}[\/-]\d{2}[\/-]\d{4})/';

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match($datePattern, $line, $matches)) {
                $date = $matches[1];
                $remaining = trim(str_replace($date, '', $line));

                if (preg_match_all('/([\-\d\.,]+)/', $remaining, $amounts)) {
                    $numericValues = array_filter($amounts[1], function($v) {
                        return strlen($v) > 1;
                    });
                    $numericValues = array_values($numericValues);

                    if (count($numericValues) >= 1) {
                        $concept = trim(preg_replace('/([\-\d\.,]{3,})/', '', $remaining));
                        $amount = $this->parseSpanishAmount(end($numericValues));

                        $movements[] = [
                            'date' => $this->convertDate($date),
                            'value_date' => null,
                            'concept' => $concept ?: 'Unknown',
                            'deposit' => $amount > 0 ? $amount : null,
                            'withdrawal' => $amount < 0 ? abs($amount) : null,
                            'balance' => null,
                        ];
                    }
                }
            }
        }

        return $movements;
    }

    private function parseSpanishAmount(string $value): float
    {
        $value = trim($value);
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        $amount = (float) $value;
        return $negative ? -$amount : $amount;
    }

    private function convertDate(string $date): string
    {
        $date = str_replace('-', '/', $date);
        $parts = explode('/', $date);
        if (count($parts) === 3 && strlen($parts[2]) === 4) {
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }
        return $date;
    }
}
