<?php

namespace App\Console\Commands;

use App\Jobs\ExportPricebookJob;
use App\Models\Pricebook\PricebookExport;
use Illuminate\Console\Command;

class ExportPricebook extends Command
{
    protected $signature = 'pricebook:export
                            {--force : Skip confirmation prompt}
                            {--sync : Run synchronously instead of queuing}
                            {--path= : Override output path (defaults to PRICEBOOK_PATH)}';

    protected $description = 'Export pricebook data from the database back to the BT9000 XML file';

    public function handle(): int
    {
        $outputPath = $this->option('path') ?? env('PRICEBOOK_PATH');

        if (empty($outputPath)) {
            $this->error('No output path provided. Set PRICEBOOK_PATH in .env or use --path option.');
            return self::FAILURE;
        }

        if (! str_starts_with($outputPath, '/')) {
            $outputPath = base_path($outputPath);
        }

        if (! $this->option('force')) {
            if (! $this->confirm("This will overwrite {$outputPath} with current database data. Continue?")) {
                $this->info('Export cancelled.');
                return self::SUCCESS;
            }
        }

        $export = PricebookExport::create([
            'file_path'  => $outputPath,
            'started_at' => now(),
            'status'     => 'running',
        ]);

        $job = new ExportPricebookJob($outputPath, $export->id);

        if ($this->option('sync')) {
            $this->info('Running export synchronously...');
            try {
                $job->handle();
                $this->info("Export complete: {$outputPath}");
            } catch (\Throwable $e) {
                $this->error('Export failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            dispatch($job);
            $this->info("Export job queued (export ID: {$export->id}). Monitor progress in the admin panel.");
        }

        return self::SUCCESS;
    }
}
