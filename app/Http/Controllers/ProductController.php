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

        if (is_string($request->price_tiers)) {
            $priceTiers = json_decode($request->price_tiers, true);
            if (is_array($priceTiers)) {
                $request->merge(['price_tiers' => $priceTiers]);
            } else {
                $request->merge(['price_tiers' => null]);
            }
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
            'price_tiers' => 'nullable|array',
            'price_tiers.*.min_qty' => 'required|numeric|min:0',
            'price_tiers.*.max_qty' => 'nullable|numeric|min:0',
            'price_tiers.*.price' => 'required|integer|min:0',
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

        if (is_string($request->price_tiers)) {
            $priceTiers = json_decode($request->price_tiers, true);
            if (is_array($priceTiers)) {
                $request->merge(['price_tiers' => $priceTiers]);
            } else {
                $request->merge(['price_tiers' => null]);
            }
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
            'price_tiers' => 'nullable|array',
            'price_tiers.*.min_qty' => 'required|numeric|min:0',
            'price_tiers.*.max_qty' => 'nullable|numeric|min:0',
            'price_tiers.*.price' => 'required|integer|min:0',
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
     * Display the fast product photo upload page.
     */
    public function fastUploadPage(Request $request)
    {
        $search   = $request->input('search', '');
        $filter   = $request->input('filter', 'no_photo'); // all | no_photo | has_photo
        $category = $request->input('category', '');

        $query = Product::query();

        if ($filter === 'no_photo') {
            $query->where(function ($q) {
                $q->whereNull('image_path')->orWhere('image_path', '');
            });
        } elseif ($filter === 'has_photo') {
            $query->whereNotNull('image_path')->where('image_path', '<>', '');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($category && $category !== 'Semua') {
            $query->where('category', $category);
        }

        $products   = $query->orderBy('name')->paginate(16)->withQueryString();
        $categories = \App\Models\Category::select('id', 'name')->get();

        return view('admin.fast-upload-photos', compact('products', 'categories', 'search', 'filter', 'category'));
    }

    /**
     * Upload (and optionally Gemini-AI-rebuild) a product photo via AJAX.
     * POST /admin/products/{product}/upload-photo
     */
    public function uploadPhoto(Request $request, Product $product)
    {
        $request->validate([
            'image'      => 'required|image|max:20480',
            'rebuild_ai' => 'nullable',
        ]);

        $useAI = filter_var($request->input('rebuild_ai', false), FILTER_VALIDATE_BOOLEAN);

        // Delete old image if exists
        if ($product->image_path) {
            $oldPath = public_path('storage/' . $product->image_path);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        if ($useAI) {
            try {
                $imagePath = $this->geminiRebuildAndStore($request->file('image'), $product);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gemini AI rebuild failed: ' . $e->getMessage());
                // Graceful fallback: compress original image if AI fails
                $imagePath = $this->compressAndStoreImage($request->file('image'));
            }
        } else {
            $imagePath = $this->compressAndStoreImage($request->file('image'));
        }

        $product->update(['image_path' => $imagePath]);

        return response()->json([
            'success'    => true,
            'message'    => $useAI ? 'Foto berhasil di-rebuild dengan Gemini AI!' : 'Foto berhasil diunggah!',
            'image_url'  => '/storage/' . $imagePath,
            'image_path' => $imagePath,
        ]);
    }

    /**
     * Analyze the uploaded image using Gemini AI vision, generate an optimized
     * product photo prompt, then fetch a studio-quality image from Pollinations.ai.
     */
    private function geminiRebuildAndStore($file, Product $product): string
    {
        $geminiKey   = config('services.gemini.key');
        $geminiModel = config('services.gemini.model', 'gemini-2.0-flash');

        // ---------------------------------------------------------------
        // Step 1: Send image to Gemini to get an AI-optimized photo prompt
        // ---------------------------------------------------------------
        $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
        $imageMime   = $file->getMimeType();

        $geminiPrompt = "You are a professional product photography prompt engineer. "
            . "Analyze this product image. The product name is: '{$product->name}' (category: {$product->category}). "
            . "Generate a single, highly detailed, studio-quality text-to-image prompt in English "
            . "that will produce a premium, clean, white-background product photo of this item. "
            . "Describe: lighting (soft studio lighting), camera angle, background (clean white or warm beige), "
            . "product presentation (arranged neatly on surface), and relevant props. "
            . "Output ONLY the prompt text. No explanations. Max 200 words.";

        $geminiPayload = [
            'contents' => [[
                'parts' => [
                    ['text' => $geminiPrompt],
                    [
                        'inline_data' => [
                            'mime_type' => $imageMime,
                            'data'      => $imageBase64,
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => 300,
            ],
        ];

        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}";

        $ch = curl_init($geminiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($geminiPayload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $geminiResponse = curl_exec($ch);
        $geminiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($geminiHttpCode !== 200 || !$geminiResponse) {
            throw new \Exception("Gemini API error (HTTP {$geminiHttpCode}): " . $geminiResponse);
        }

        $geminiData  = json_decode($geminiResponse, true);
        $imagePrompt = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');

        if (empty($imagePrompt)) {
            throw new \Exception('Gemini returned an empty prompt.');
        }

        \Illuminate\Support\Facades\Log::info('[GeminiAI] Prompt for "' . $product->name . '": ' . $imagePrompt);

        // ---------------------------------------------------------------
        // Step 2: Generate a studio product photo using Pollinations.ai
        // ---------------------------------------------------------------
        $encodedPrompt   = urlencode($imagePrompt . ', product photography, studio, isolated on white background, ultra sharp, 4k');
        $seed            = rand(1, 99999);
        $pollinationsUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=800&height=800&seed={$seed}&nologo=true&enhance=true&model=flux";

        $ch = curl_init($pollinationsUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 90,
        ]);
        $imageData     = curl_exec($ch);
        $imageHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($imageHttpCode !== 200 || !$imageData || strlen($imageData) < 5000) {
            throw new \Exception("Pollinations.ai error (HTTP {$imageHttpCode}). Response too small.");
        }

        // ---------------------------------------------------------------
        // Step 3: Save the generated image to public/storage/products
        // ---------------------------------------------------------------
        $targetDir = public_path('storage/products');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = 'products/ai_' . uniqid() . '.jpg';
        $fullPath = public_path('storage/' . $filename);
        file_put_contents($fullPath, $imageData);

        return $filename;
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
