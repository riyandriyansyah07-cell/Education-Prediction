<?php

namespace App\Http\Controllers;

use App\Models\RiskResult;
use App\Support\RiskModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function landing(): View
    {
        $sampleFeatures = [
            'attendance' => 76.0,
            'min_attendance' => 70.0,
            'low_attendance_courses' => 1,
            'attendance_delta' => -6.0,
            'ips' => 2.41,
            'ipk' => 2.65,
            'ips_delta' => -0.18,
            'failed_ratio' => 0.1111,
            'repeat_courses' => 1,
            'credits_delta' => 2,
            'credits' => 18,
        ];
        $sample = new RiskResult([
            'semester' => 6,
            'level' => 'sedang',
            'score' => 3.7,
            'components_count' => 3,
            'data_trust_level' => 2,
            'is_projection' => true,
            'features' => $sampleFeatures,
            'factors' => RiskModel::factors($sampleFeatures),
            'suggestions' => [],
            'llm_generated' => false,
            'data_as_of' => '2026-11-11',
        ]);
        $sample->suggestions = RiskModel::suggestions('sedang', $sample->factors);

        return view('landing', ['sample' => $sample]);
    }

    /** Halaman "Cara risiko dihitung": menampilkan tabel band, bobot, rumus N & U_min, serta rujukan skala UHAMKA. */
    public function methodology(): View
    {
        return view('methodology');
    }

    /** Arahkan pengguna ke halaman awal sesuai perannya. */
    public function home(Request $request): RedirectResponse
    {
        return redirect()->route(match ($request->user()->role) {
            'dpa' => 'dpa.bimbingan',
            'admin' => 'admin.akun',
            default => 'mhs.ringkasan',
        });
    }
}
