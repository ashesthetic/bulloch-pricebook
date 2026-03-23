<?php

namespace App\Console\Commands;

use App\Jobs\ImportPricebookJob;
use App\Models\Pricebook\PricebookImport;
use Illuminate\Console\Command;

class ImportPricebook extends Command
{
    protected $signature = 'pricebook:import
                            {--force : Skip confirmation prompt}
                            {--sync : Run synchronously instead of queuing}
                            {--path= : Override PRICEBOOK_PATH env variable}';

    protected $description = 'Full-replace import of BT9000 XML pricebook into the database';

    public function handle(): int
    {
        $filePath = $this->option('path') ?? env('PRICEBOOK_PATH');

        if (empty($filePath)) {
            $this->error('No file path provided. Set PRICEBOOK_PATH in .env or use --path option.');
            return self::FAILURE;
        }

        // Resolve relative paths against the application base path
        if (! str_starts_with($filePath, '/')) {
            $filePath = base_path($filePath);
        }

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('This will truncate all pricebook tables and re-import. Continue?')) {
                $this->info('Import cancelled.');
                return self::SUCCESS;
            }
        }

        // Create the import record
        $import = PricebookImport::create([
            'file_path'  => $filePath,
            'started_at' => now(),
            'status'     => 'running',
        ]);

        $job = new ImportPricebookJob($filePath, $import->id);

        if ($this->option('sync')) {
            $this->info("Running import synchronously (import ID: {$import->id})...");
            try {
                $job->handle();
                $import->refresh();
                $this->info("Import completed. Records: " . json_encode($import->records_imported));
            } catch (\Throwable $e) {
                $this->error("Import failed: " . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            dispatch($job);
            $this->info("Import job queued (import ID: {$import->id}). Monitor progress in the admin panel.");
        }

        return self::SUCCESS;
    }
}
