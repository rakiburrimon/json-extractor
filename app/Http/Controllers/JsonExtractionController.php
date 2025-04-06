<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use thiagoalessio\TesseractOCR\TesseractOCR;

class JsonExtractionController extends Controller
{
    public function extract(Request $request)
    {
        // Validate the request
        $request->validate([
            'imageBase64' => 'required|string'
        ]);

        try {
            // Get base64 image from request
            $imageBase64 = $request->input('imageBase64');

            // Remove data URL prefix if present
            if (Str::startsWith($imageBase64, 'data:image/png;base64,')) {
                $imageBase64 = Str::after($imageBase64, 'base64,');
            }

            // Decode base64 to image
            $imageData = base64_decode($imageBase64);
            $image = Image::make($imageData);

            // Save to temp file for OCR
            $tempPath = tempnam(sys_get_temp_dir(), 'ocr') . '.png';
            $image->save($tempPath);

            // Perform OCR
            $text = (new TesseractOCR($tempPath))
                ->lang('eng')
                ->run();

            // Clean up temp file
            unlink($tempPath);

            // Extract JSON from text
            $jsonStart = strpos($text, '{');
            $jsonEnd = strrpos($text, '}');
            $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);

            // Decode JSON
            $extractedData = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse extracted JSON');
            }

            return response()->json([
                'success' => true,
                'data' => $extractedData,
                'message' => 'Successfully extracted JSON from image'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }
}
