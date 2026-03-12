<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class ExportDownloadController extends Controller
{
    public function __invoke(string $file)
    {
        if (preg_match('/[^a-zA-Z0-9_\-\.]/', $file) || str_contains($file, '..')) {
            abort(404);
        }
        $path = 'exports/' . $file;
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $content = Storage::disk('local')->get($path);
        Storage::disk('local')->delete($path);
        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $file . '"',
        ]);
    }
}
