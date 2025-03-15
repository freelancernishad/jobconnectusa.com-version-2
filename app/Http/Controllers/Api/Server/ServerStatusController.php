<?php

namespace App\Http\Controllers\Api\Server;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

class ServerStatusController extends Controller
{


    public function status(): JsonResponse
    {
        $status = [
            'app' => [
                'version' => config('app.version'),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
            ],
            'database' => $this->checkDatabaseConnection(),
            'server' => $this->getServerStatus(),
        ];

        return response()->json($status);
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getServerStatus(): array
    {
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();
        $diskUsage = $this->getDiskUsage();

        return [
            'cpu_usage' => $cpuUsage,
            'memory_usage' => $memoryUsage,
            'disk_usage' => $diskUsage,
        ];
    }

    private function getCpuUsage(): ?string
    {
        $process = new Process(['top', '-bn1']);
        $process->run();

        if ($process->isSuccessful()) {
            preg_match('/%Cpu\(s\):\s+(\d+\.\d+)\s+us/', $process->getOutput(), $matches);
            return $matches[1] ?? null;
        }

        return null;
    }

    private function getMemoryUsage(): ?string
    {
        $process = new Process(['free', '-m']);
        $process->run();

        if ($process->isSuccessful()) {
            preg_match('/Mem:\s+(\d+)\s+(\d+)\s+/', $process->getOutput(), $matches);
            if (count($matches) > 2) {
                $totalMemory = $matches[1];
                $usedMemory = $matches[2];
                return round(($usedMemory / $totalMemory) * 100, 2) . '%';
            }
        }

        return null;
    }

    private function getDiskUsage(): string
    {
        return round(disk_free_space("/") / disk_total_space("/") * 100, 2) . '%';
    }















    //  /**
    //  * Check the server status.
    //  *
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // public function checkStatus()
    // {
    //     // Check the database connection
    //     $databaseStatus = $this->checkDatabaseConnection();

    //     // Check the cache status
    //     $cacheStatus = $this->checkCacheStatus();

    //     // Check if queue is running
    //     $queueStatus = $this->checkQueueStatus();

    //     // Check if the application is in maintenance mode
    //     $maintenanceModeStatus = $this->checkMaintenanceMode();

    //     // Prepare the status data
    //     $status = [
    //         'database' => $databaseStatus,
    //         'cache' => $cacheStatus,
    //         'queue' => $queueStatus,
    //         'maintenance_mode' => $maintenanceModeStatus,
    //         'uptime' => $this->getServerUptime(),
    //         'memory_usage' => $this->getMemoryUsage(),
    //         'disk_space' => $this->getDiskSpace(),
    //         'timestamp' => now(),
    //     ];

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $status
    //     ]);
    // }

    // /**
    //  * Check the database connection status.
    //  *
    //  * @return string
    //  */
    // private function checkDatabaseConnection()
    // {
    //     try {
    //         DB::connection()->getPdo();
    //         return 'connected';
    //     } catch (\Exception $e) {
    //         return 'disconnected';
    //     }
    // }

    // /**
    //  * Check the cache status.
    //  *
    //  * @return string
    //  */
    // private function checkCacheStatus()
    // {
    //     try {
    //         Cache::store()->put('test', 'test', 1);
    //         return 'working';
    //     } catch (\Exception $e) {
    //         return 'not working';
    //     }
    // }

    // /**
    //  * Check if the queue is running.
    //  *
    //  * @return string
    //  */
    // private function checkQueueStatus()
    // {
    //     $queueStatus = Artisan::call('queue:listen');
    //     return $queueStatus == 0 ? 'running' : 'not running';
    // }

    // /**
    //  * Check if the application is in maintenance mode.
    //  *
    //  * @return string
    //  */
    // private function checkMaintenanceMode()
    // {
    //     return app()->isDownForMaintenance() ? 'active' : 'inactive';
    // }

    // /**
    //  * Get server uptime.
    //  *
    //  * @return string
    //  */
    // private function getServerUptime()
    // {
    //     $uptime = shell_exec('uptime -p');
    //     return $uptime ?: 'N/A';
    // }

    // /**
    //  * Get memory usage.
    //  *
    //  * @return string
    //  */
    // private function getMemoryUsage()
    // {
    //     $memory = shell_exec('free -h | grep Mem');
    //     return $memory ?: 'N/A';
    // }

    // /**
    //  * Get disk space.
    //  *
    //  * @return string
    //  */
    // private function getDiskSpace()
    // {
    //     $disk = shell_exec('df -h /');
    //     return $disk ?: 'N/A';
    // }
}
