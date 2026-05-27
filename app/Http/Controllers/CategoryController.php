<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create($validated);

        // Fetch product count for the dynamic UI
        $category->products_count = 0;

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan!',
            'category' => $category
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        // Keep the old category name to update existing products assigned to this category
        $oldName = $category->name;

        $category->update($validated);

        // Safely update all products currently having the old category name string
        if ($oldName !== $category->name) {
            Product::where('category', $oldName)->update(['category' => $category->name]);
        }

        // Include product count for the dynamic UI
        $category->products_count = Product::where('category', $category->name)->count();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui!',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        // STRICT CHECK: Prevent deleting category if it has products assigned to it
        $productCount = Product::where('category', $category->name)->count();
        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kategori! Kategori ini sedang digunakan oleh ' . $productCount . ' produk.'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus!'
        ]);
    }
}
