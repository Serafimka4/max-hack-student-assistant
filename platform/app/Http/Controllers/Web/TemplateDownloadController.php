<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplateVersion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateDownloadController extends Controller
{
    /** Ссылка подписана и ограничена по времени (middleware signed). */
    public function __invoke(DocumentTemplateVersion $version): StreamedResponse
    {
        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }
}
