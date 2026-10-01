<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class Csv
{
    /** @return array<int,array<string,string>> rows keyed by lower-cased header */
    public static function read(UploadedFile $file): array
    {
        $h = fopen($file->getRealPath(), 'r');
        $head = fgetcsv($h);
        if (! $head) { return []; }
        $head = array_map(fn ($c) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c))), $head);
        $rows = [];
        while (($line = fgetcsv($h)) !== false) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) { continue; }
            $line = array_pad($line, count($head), '');
            $rows[] = array_map('trim', array_combine($head, array_slice($line, 0, count($head))));
        }
        fclose($h);
        return $rows;
    }

    public static function download(string $filename, array $header, iterable $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $o = fopen('php://output', 'w');
            fputcsv($o, $header);
            foreach ($rows as $r) { fputcsv($o, $r); }
            fclose($o);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
