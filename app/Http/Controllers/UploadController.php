<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileUploadResource;
use App\Jobs\ProcessCsvUpload;
use App\Models\FileUpload;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function __construct(
        protected FileUpload $fileUpload,
    ) {}

    public function index()
    {
        return view('index');
        
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'file' => 'required|mimes:csv',
            ], [
                'file.required' => 'File Upload Is Required.',
                'file.mimes' => 'File Format Must Be CSV.',
            ]);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $getContents = file_get_contents($file->getRealPath());

            $contents = str_replace(["\r\n", "\r"], "\n", $getContents);

            $checksum = hash('sha256', $contents);

            $existing = $this->fileUpload->where('checksum', $checksum)->exists();
            if ($existing) {
                return $this->error('File already uploaded', 422);
            }

            $path = 'upload/csv/' . now()->format('YmdHis');
            $storedName = Str::random(12) . '_' . $originalName;
            Storage::put("$path/$storedName", $contents);

            $upload = $this->fileUpload->create([
                'filename' => $originalName,
                'path' => "$path/$storedName",
                'checksum' => $checksum,
            ]);

            ProcessCsvUpload::dispatch($upload)->afterCommit();
            DB::commit();
            return $this->success(code: 201, data: $upload);
        } catch (\Throwable $e) {
            DB::rollback();
            if ($e instanceof ValidationException) {
                return $this->error($e->getMessage(), 400);
            }
            return $this->error_code($e->getMessage(), $e->getCode());
        }
    }

    public function list() {
        try {
            $datas = $this->fileUpload->orderBy('created_at', 'desc')->get();

            return $this->success(data: FileUploadResource::collection($datas));
        } catch (\Throwable $e) {
            return $this->error_code($e->getMessage(), $e->getCode());
        }
    }

    private function success($message = 'success', $code = 200, $data = []) {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ], $code);
    }

    private function error($message = 'error', $code = 404) {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => []
        ], $code);
    }

    private function error_code($message = 'errors', $code = 500) {
        if ($code > 500 || $code <= 0) $code = 500;
        return response()->json([
            'message' => $message,
            'code' => $code,
        ], $code);
    }
}
