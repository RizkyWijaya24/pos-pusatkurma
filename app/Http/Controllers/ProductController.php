<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Product store request all: ', $request->all());
        \Illuminate\Support\Facades\Log::info('Product store request hasFile image: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        if ($request->hasFile('image')) {
            \Illuminate\Support\Facades\Log::info('Product store request file image mime: ' . $request->file('image')->getMimeType());
        }

        if (empty($request->sku)) {
            $request->merge(['sku' => 'PK-' . strtoupper(substr(uniqid(), -6))]);
        }

        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'cost_price' => 'required|integer|min:0',
            'selling_price' => 'required|integer|min:0',
            'price_unit' => 'required|string|in:gram,kg,pcs,pack,dus',
            'stock' => 'required|numeric|min:0',
            'image' => 'nullable|image', // No max size limit, auto-compress is handled below
        ]);

        if ($request->hasFile('image')) {
            $path = $this->compressAndStoreImage($request->file('image'));
            $validated['image_path'] = $path;
        }

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan!',
            'product' => $product
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        \Illuminate\Support\Facades\Log::info('Product update request all: ', $request->all());
        \Illuminate\Support\Facades\Log::info('Product update request hasFile image: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        if ($request->hasFile('image')) {
            \Illuminate\Support\Facades\Log::info('Product update request file image mime: ' . $request->file('image')->getMimeType());
        }

        if (empty($request->sku)) {
            $request->merge(['sku' => $product->sku ?: 'PK-' . strtoupper(substr(uniqid(), -6))]);
        }

        $validated = $request->validate([
            'sku' => ['required', 'string', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'cost_price' => 'required|integer|min:0',
            'selling_price' => 'required|integer|min:0',
            'price_unit' => 'required|string|in:gram,kg,pcs,pack,dus',
            'stock' => 'required|numeric|min:0',
            'image' => 'nullable|image', // No max size limit, auto-compress is handled below
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists from physical public/storage folder
            if ($product->image_path) {
                $oldPath = public_path('storage/' . $product->image_path);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            
            $path = $this->compressAndStoreImage($request->file('image'));
            $validated['image_path'] = $path;
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui!',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        // Delete image if exists from physical public/storage folder
        if ($product->image_path) {
            $oldPath = public_path('storage/' . $product->image_path);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus!'
        ]);
    }

    /**
     * Compress, auto-resize and save the uploaded product photo.
     * Reduces phone camera images (often 5MB+) to highly compressed, crisp JPEGs (~100KB).
     * Features graceful fallback if GD extension is not loaded or missing.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string  Relative storage path
     */
    private function compressAndStoreImage($file)
    {
        // Graceful GD Fallback: If GD extension is not loaded or crucial functions are missing,
        // save directly to public/storage/products to avoid symlink issues on shared hosting
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            // Ensure public/storage/products exists
            $targetDir = public_path('storage/products');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            return 'products/' . $filename;
        }

        $mime = $file->getMimeType();
        $source = null;

        // Load image resource from file path based on format and availability
        if (($mime === 'image/jpeg' || $mime === 'image/jpg') && function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg($file->getRealPath());
        } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            $source = @imagecreatefrompng($file->getRealPath());
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $source = @imagecreatefromwebp($file->getRealPath());
        } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
            $source = @imagecreatefromgif($file->getRealPath());
        }

        // Fallback: If loader fails to parse resource, store standard file directly in public/storage
        if (!$source) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('storage/products');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            return 'products/' . $filename;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        
        // Auto-Resize if dimensions exceed mobile-optimal boundary (800px max)
        // Phone screens rarely exceed 1080px — 800px looks crisp and is ~50% smaller than 1200px
        $maxDimension = 800;
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = round(($height / $width) * $maxDimension);
            } else {
                $newHeight = $maxDimension;
                $newWidth = round(($width / $height) * $maxDimension);
            }
            $target = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparent PNG backgrounds (fill with white)
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $white);
            
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $target;
        } else {
            // Clean up PNG background for non-resizes
            $target = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $white);
            imagecopy($target, $source, 0, 0, 0, 0, $width, $height);
            imagedestroy($source);
            $source = $target;
        }

        // Generate unique name and local storage destination path inside public/storage
        $filename = 'products/' . uniqid() . '.jpg';
        $fullPath = public_path('storage/' . $filename);

        // Ensure directories are dynamically created
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Save JPEG with 75% quality (optimal quality/size ratio)
        imagejpeg($source, $fullPath, 75);
        imagedestroy($source);

        return $filename;
    }
}
