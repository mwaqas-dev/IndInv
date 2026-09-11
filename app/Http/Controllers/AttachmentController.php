<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Release;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(Request $request, string $type, int $id, AuditService $audit)
    {
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,text/plain,application/vnd.openxmlformats-officedocument.wordprocessingml.document']]);
        $model = $type === 'product' ? Product::findOrFail($id) : Release::findOrFail($id);
        $file = $request->file('file');
        $path = $file->store('private/'.Str::plural($type).'/'.$id);
        $attachment = $model->attachments()->create(['uploaded_by' => $request->user()->id, 'path' => $path, 'original_name' => basename($file->getClientOriginalName()), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
        $audit->record('FILE_UPLOAD', 'Documents', "Uploaded {$attachment->original_name}", $model);

        return back()->with('success', 'Attachment stored securely.');
    }
}
