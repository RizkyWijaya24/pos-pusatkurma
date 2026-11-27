<?php

namespace App\Http\Controllers;

use App\Services\SystemRepairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemRepairController extends Controller
{
    public function __construct(
        protected SystemRepairService $repairService,
    ) {}

    public function index()
    {
        $diagnostics = $this->repairService->diagnose();
        $recentErrors = $this->repairService->getRecentErrors(50);
        $repairHistory = $this->repairService->getRepairHistory(15);
        $rescueKey = (string) config('app.repair_rescue_key', '');
        $hasRescueKey = $rescueKey !== '';
        $rescueUrl = $hasRescueKey
            ? url('system-rescue.php') . '?key=' . rawurlencode($rescueKey)
            : null;

        return view('owner.system-repair', compact(
            'diagnostics',
            'recentErrors',
            'repairHistory',
            'hasRescueKey',
            'rescueUrl'
        ));
    }

    public function diagnose(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'diagnostics' => $this->repairService->diagnose(),
            'recent_errors' => $this->repairService->getRecentErrors(50),
            'repair_history' => $this->repairService->getRepairHistory(15),
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'modules' => 'nullable|array',
            'modules.*' => 'string',
        ]);

        $modules = $validated['modules'] ?? ['all'];
        $outcome = $this->repairService->repair($modules, (int) auth()->id());

        return response()->json([
            'success' => true,
            'executed_at' => $outcome['executed_at'],
            'results' => $outcome['results'],
            'diagnostics' => $outcome['diagnostics'],
            'recent_errors' => $this->repairService->getRecentErrors(50),
            'repair_history' => $this->repairService->getRepairHistory(15),
        ]);
    }

    public function clearLogs(): JsonResponse
    {
        $result = $this->repairService->archiveOrClearLogs();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'recent_errors' => $this->repairService->getRecentErrors(50),
            'diagnostics' => $this->repairService->diagnose(),
        ]);
    }

    public function generateRescueKey(): JsonResponse
    {
        $envPath = base_path('.env');
        if (!is_file($envPath) || !is_writable($envPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File .env tidak dapat ditulisi. Tambahkan REPAIR_RESCUE_KEY secara manual di cPanel.',
            ], 422);
        }

        $newKey = bin2hex(random_bytes(24));
        $envContent = (string) file_get_contents($envPath);

        if (preg_match('/^REPAIR_RESCUE_KEY=.*$/m', $envContent)) {
            $envContent = (string) preg_replace('/^REPAIR_RESCUE_KEY=.*$/m', 'REPAIR_RESCUE_KEY=' . $newKey, $envContent);
        } else {
            $separator = str_ends_with($envContent, "\n") ? '' : "\n";
            $envContent .= $separator . "\n# System Self-Repair Pre-Boot Rescue Key\nREPAIR_RESCUE_KEY=" . $newKey . "\n";
        }

        file_put_contents($envPath, $envContent);

        // Ensure cached config does not hold an empty stale key
        if (is_file(base_path('bootstrap/cache/config.php'))) {
            Artisan::call('config:clear');
        }

        return response()->json([
            'success' => true,
            'message' => 'Kunci Pre-Boot Rescue berhasil dibuat & disimpan di .env.',
            'rescue_url' => url('system-rescue.php') . '?key=' . rawurlencode($newKey),
        ]);
    }
}
