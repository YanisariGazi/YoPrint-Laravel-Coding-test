<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileUploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'filename' => $this->filename,
            'status' => $this->status,
            'message' => $this->message,
            'error' => $this->error,
            'processed_at' => $this->processed_at->format('Y-m-d H:i:s'),
            'finished_at' => $this->finished_at->format('Y-m-d H:i:s'),
        ];
    }
}
