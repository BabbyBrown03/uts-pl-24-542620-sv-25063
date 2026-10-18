<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarangRequest;
use App\Http\Resources\BarangResource;
use App\Models\Barang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarangController extends Controller
{
    /**
     * GET /api/barang - tampil semua barang.
     */
    public function index(): AnonymousResourceCollection
    {
        $barang = Barang::orderBy('id')->get();

        return BarangResource::collection($barang);
    }

    /**
     * GET /api/barang/{barang} - detail satu barang.
     */
    public function show(Barang $barang): BarangResource
    {
        return new BarangResource($barang);
    }

    /**
     * POST /api/barang - tambah barang (201 Created).
     */
    public function store(BarangRequest $request): JsonResponse
    {
        $barang = Barang::create($request->validated());

        return response()->json([
            'message' => 'Barang berhasil ditambahkan.',
            'data' => new BarangResource($barang),
        ], 201);
    }

    /**
     * PUT /api/barang/{barang} - ubah barang (200 OK).
     */
    public function update(BarangRequest $request, Barang $barang): JsonResponse
    {
        $barang->update($request->validated());

        return response()->json([
            'message' => 'Barang berhasil diperbarui.',
            'data' => new BarangResource($barang->fresh()),
        ], 200);
    }

    /**
     * DELETE /api/barang/{barang} - hapus barang (200 OK).
     */
    public function destroy(Barang $barang): JsonResponse
    {
        $barang->delete();

        return response()->json([
            'message' => 'Barang berhasil dihapus.',
        ], 200);
    }
}