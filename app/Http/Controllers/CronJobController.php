<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronJobController extends Controller
{
    public function ticket_escalation_level(Request $request)
    {
        // Keep the legacy URL on exactly the same rules as the scheduled command.
        $exitCode = Artisan::call('tickets:check-escalations', ['--dry-run' => $request->boolean('dry_run')]);
        return response()->json([
            'success' => $exitCode === 0,
            'message' => trim(Artisan::output()),
        ], $exitCode === 0 ? 200 : 500);
    }
}
