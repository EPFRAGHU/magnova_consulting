<?php

namespace App\Http\Controllers;

use App\Services\EcrExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EcrController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('ecr/index', [
            'month' => $request->integer('month') ?: (int) now()->format('n'),
            'year' => $request->integer('year') ?: (int) now()->format('Y'),
        ]);
    }

    public function download(Request $request, EcrExportService $ecr): HttpResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        $tenant = Auth::user()->tenant;
        $content = $ecr->generateForTenant($tenant->id, $data['month'], $data['year']);
        $fileName = sprintf('ECR_%s_%s%s.txt', $tenant->slug, str_pad((string) $data['month'], 2, '0', STR_PAD_LEFT), $data['year']);

        return Response::make($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
