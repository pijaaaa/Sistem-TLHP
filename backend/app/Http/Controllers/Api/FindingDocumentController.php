<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FindingDocument;
use Illuminate\Support\Facades\Storage;

class FindingDocumentController extends Controller
{
    public function download(FindingDocument $document)
    {
        return Storage::disk(config('upload.disk'))->download($document->path, $document->name);
    }
}
