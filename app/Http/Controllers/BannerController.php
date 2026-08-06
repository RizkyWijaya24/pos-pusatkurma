<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'subtitle'    => 'nullable|string|max:255',
            'badge_text'  => 'nullable|string|max:80',
            'button_text' => 'required|string|max:60',
            'button_url'  => 'required|string|max:255',
            'bg_from'     => 'required|string|max:30',
            'bg_to'       => 'required|string|max:30',
            'sort_order'  => 'required|integer|min:0',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:3072', // max 3MB
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        Banner::create($data);

        return redirect()->back()->with('success', 'Banner berhasil ditambahkan!');
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'subtitle'    => 'nullable|string|max:255',
            'badge_text'  => 'nullable|string|max:80',
            'button_text' => 'required|string|max:60',
            'button_url'  => 'required|string|max:255',
            'bg_from'     => 'required|string|max:30',
            'bg_to'       => 'required|string|max:30',
            'sort_order'  => 'required|integer|min:0',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $banner->update($data);

        return redirect()->back()->with('success', 'Banner berhasil diperbarui!');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
        $banner->delete();
        return redirect()->back()->with('success', 'Banner berhasil dihapus!');
    }
}
