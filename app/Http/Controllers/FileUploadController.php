<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="📄 File Upload",
 *     description="File upload services for doctor applications"
 * )
 */
class FileUploadController extends Controller
{
    /**
     * @OA\Post(
     *     path="/upload/document",
     *     operationId="uploadDocument",
     *     tags={"📄 File Upload"},
     *     summary="Upload document (PDF)",
     *     description="Upload PDF documents for doctor applications (CV, diploma, etc.)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "type"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="PDF file to upload (max 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"cv", "diploma", "certificate"},
     *                     description="Type of document being uploaded"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File uploaded successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="file_url", type="string", example="http://localhost:8000/storage/documents/cv_1234567890.pdf"),
     *                 @OA\Property(property="file_path", type="string", example="documents/cv_1234567890.pdf"),
     *                 @OA\Property(property="file_size", type="integer", example=1024576),
     *                 @OA\Property(property="file_type", type="string", example="cv")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Upload failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to upload file")
     *         )
     *     )
     * )
     */
    public function uploadDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:pdf|max:5120', // Max 5MB
                'type' => 'required|in:cv,diploma,certificate'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $file = $request->file('file');
            $type = $request->type;

            // Generate unique filename
            $timestamp = time();
            $fileName = $type . '_' . $timestamp . '.' . $file->getClientOriginalExtension();

            // Store file in documents directory
            $filePath = $file->storeAs('documents', $fileName, 'public');

            // Generate full URL
            $fileUrl = Storage::url($filePath);

            return ResponseHelper::success([
                'file_url' => url($fileUrl),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'file_type' => $type,
                'original_name' => $file->getClientOriginalName()
            ], 'File uploaded successfully');

        } catch (Exception $e) {
            return ResponseHelper::error('Failed to upload file', 500, $e->getMessage());
        }
    }
}