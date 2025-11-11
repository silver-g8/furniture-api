<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class SystemHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sys:health
                            {--json : Output in JSON format}
                            {--check=* : Specific checks to run (db,cache,queue,storage)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system health (database, cache, queue, storage)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = $this->option('check');
        $runAll = empty($checks);

        $results = [];
        $allPassed = true;

        // Database check
        if ($runAll || in_array('db', $checks)) {
            $results['database'] = $this->checkDatabase();
            if (! $results['database']['status']) {
                $allPassed = false;
            }
        }

        // Cache check
        if ($runAll || in_array('cache', $checks)) {
            $results['cache'] = $this->checkCache();
            if (! $results['cache']['status']) {
                $allPassed = false;
            }
        }

        // Queue check
        if ($runAll || in_array('queue', $checks)) {
            $results['queue'] = $this->checkQueue();
            if (! $results['queue']['status']) {
                $allPassed = false;
            }
        }

        // Storage check
        if ($runAll || in_array('storage', $checks)) {
            $results['storage'] = $this->checkStorage();
            if (! $results['storage']['status']) {
                $allPassed = false;
            }
        }

        // Output results
        if ($this->option('json')) {
            $json = json_encode([
                'status' => $allPassed ? 'healthy' : 'unhealthy',
                'checks' => $results,
                'timestamp' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT);
            $this->line($json !== false ? $json : '{}');
        } else {
            $this->displayResults($results, $allPassed);
        }

        return $allPassed ? 0 : 1;
    }

    /**
     * Check database connectivity
     *
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');

            return [
                'status' => true,
                'message' => 'Database connection successful',
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity
     *
     * @return array<string, mixed>
     */
    private function checkCache(): array
    {
        try {
            $key = '__health_check__';
            $value = 'test_'.time();

            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            if ($retrieved === $value) {
                return [
                    'status' => true,
                    'message' => 'Cache read/write successful',
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => false,
                'message' => 'Cache value mismatch',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Cache operation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connectivity
     *
     * @return array<string, mixed>
     */
    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size();

            return [
                'status' => true,
                'message' => 'Queue connection successful',
                'connection' => $connection,
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Queue connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage writability
     *
     * @return array<string, mixed>
     */
    private function checkStorage(): array
    {
        try {
            $filename = '__health_check__.txt';
            $content = 'test_'.time();

            Storage::put($filename, $content);
            $retrieved = Storage::get($filename);
            Storage::delete($filename);

            if ($retrieved === $content) {
                return [
                    'status' => true,
                    'message' => 'Storage read/write successful',
                    'driver' => config('filesystems.default'),
                ];
            }

            return [
                'status' => false,
                'message' => 'Storage content mismatch',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Storage operation failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Display results in console
     *
     * @param  array<string, array<string, mixed>>  $results
     */
    private function displayResults(array $results, bool $allPassed): void
    {
        $this->newLine();
        $this->info('System Health Check Results');
        $this->line(str_repeat('=', 50));
        $this->newLine();

        foreach ($results as $component => $result) {
            $status = $result['status'] ? '✅' : '❌';
            $this->line(sprintf(
                '%s %s: %s',
                $status,
                ucfirst($component),
                $result['message']
            ));

            if (isset($result['error'])) {
                $this->error('   Error: '.$result['error']);
            }
        }

        $this->newLine();
        $this->line(str_repeat('=', 50));

        if ($allPassed) {
            $this->info('✅ All checks passed');
        } else {
            $this->error('❌ Some checks failed');
        }

        $this->newLine();
    }
}
