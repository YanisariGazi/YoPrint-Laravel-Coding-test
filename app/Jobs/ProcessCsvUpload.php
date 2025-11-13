<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Models\Product;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Exception as CsvException;
use Throwable;

class ProcessCsvUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $batch = 1000;

    public function __construct(
        protected $fileUpload,
        // protected Product $product,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $upload = FileUpload::find($this->fileUpload->id);
            if (!$upload) {
                return;
            }

            $upload->update(['status' => 'processing', 'processed_at' => now()]);

            $raw = Storage::get($upload->path);
            $raw = $this->toUtf8($raw);

            $csv = Reader::createFromString($raw);
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $chunks = collect();

            foreach ($records as $i => $record) {
                $row = $this->normalizeRow($record);

                if (empty($row['UNIQUE_KEY'])) {
                    continue;
                }

                $chunks->push([
                    'unique_key' => $row['UNIQUE_KEY'],
                    'product_title' => $row['PRODUCT_TITLE'] ?? null,
                    'product_description' => $row['PRODUCT_DESCRIPTION'] ?? null,
                    'style' => $row['STYLE#'] ?? null,
                    'sanmar_mainframe_color' => $row['SANMAR_MAINFRAME_COLOR'] ?? null,
                    'size' => $row['SIZE'] ?? null,
                    'color_name' => $row['COLOR_NAME'] ?? null,
                    'piece_price' => $this->parsePrice($row['PIECE_PRICE'] ?? null),
                    'updated_at' => now(),
                ]);
            }

            $chunks->chunk($this->batch)->each(function ($chunk) {
                DB::table('products')->upsert(
                    $chunk->toArray(),
                    ['unique_key'],
                    [
                        'unique_key',
                        'product_title',
                        'product_description',
                        'style',
                        'sanmar_mainframe_color',
                        'size',
                        'color_name',
                        'piece_price',
                        'updated_at',
                    ]
                );
            });

            $upload->update(['status' => 'completed', 'message' => 'success', 'finished_at' => now()]);
        } catch (\Throwable $e) {
            if ($e instanceof CsvException) {
                $upload->update(['status' => 'failed', 'error' => 'CSV parse error: '.$e->getMessage(), 'finished_at' => now()]);
            } else {
                $upload->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
            }
        }
    }

    private function toUtf8(string $text): string
    {
        // Remove BOM
        $text = preg_replace('/\x{FEFF}/u', '', $text);
        // Convert if not valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8//IGNORE');
        }
        // Remove control chars except newline and tab
        $text = preg_replace('/[^\P{C}\n\t]+/u', '', $text);
        return $text;
    }

    private function normalizeRow(array $record): array
    {
        $normalized = [];
        foreach ($record as $k => $v) {
            $key = strtoupper(trim($k));

            if (is_string($v)) {
                $v = trim($v);
                // Tambahkan titik koma jika entitas numeric HTML tidak diakhiri dengan ;
                $v = preg_replace('/&#(\d+)(?!;)/', '&#$1;', $v);
                // Decode entitas menjadi karakter asli
                $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $normalized[$key] = $v;
        }
        return $normalized;
    }

    private function parsePrice($val)
    {
        if ($val === null) return null;
        // remove non-digit except dot and minus
        $clean = preg_replace('/[^\d\.\-]/', '', $val);
        if ($clean === '') return null;
        return (float)$clean;
    }

    public function failed(Throwable $e): void
    {
        $upload = FileUpload::find($this->fileUpload->id);
        if ($upload) {
            $upload->update(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()]);
        }
    }
}
