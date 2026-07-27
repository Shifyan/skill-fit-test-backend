<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResidentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Resident::with(['currentHistory.house']);

        if ($request->has('status') && in_array($request->status, ['settler', 'temporary'])) {
            $query->where('resident_status', $request->status);
        }

        $residents = $query->latest()->get();

        return $this->successResponse($residents, 'Daftar penghuni berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'ktp_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'resident_status' => 'required|in:settler,temporary',
            'phone_number' => 'required|string|max:20',
            'marriage_status' => 'required|in:single,married',
        ]);

        if ($request->hasFile('ktp_image')) {
            $path = $request->file('ktp_image')->store('ktp', 'public');
            $validated['ktp_image'] = $path;
        }

        $resident = Resident::create($validated);

        return $this->successResponse($resident, 'Data penghuni berhasil ditambahkan', 201);
    }

    public function show(string $id): JsonResponse
    {
        $resident = Resident::with([
            'currentHistory.house',
            'histories.house',
            'invoices.house'
        ])->find($id);

        if (!$resident) {
            return $this->errorResponse('Data penghuni tidak ditemukan', null, 404);
        }

        return $this->successResponse($resident, 'Detail penghuni berhasil diambil');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $resident = Resident::find($id);

        if (!$resident) {
            return $this->errorResponse('Data penghuni tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'fullname' => 'sometimes|required|string|max:255',
            'ktp_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'resident_status' => 'sometimes|required|in:settler,temporary',
            'phone_number' => 'sometimes|required|string|max:20',
            'marriage_status' => 'sometimes|required|in:single,married',
        ]);

        if ($request->hasFile('ktp_image')) {
            if ($resident->ktp_image && !str_contains($resident->ktp_image, 'default_ktp')) {
                Storage::disk('public')->delete($resident->ktp_image);
            }
            $path = $request->file('ktp_image')->store('ktp', 'public');
            $validated['ktp_image'] = $path;
        }

        $resident->update($validated);

        return $this->successResponse($resident, 'Data penghuni berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $resident = Resident::find($id);

        if (!$resident) {
            return $this->errorResponse('Data penghuni tidak ditemukan', null, 404);
        }

        if ($resident->ktp_image && !str_contains($resident->ktp_image, 'default_ktp')) {
            Storage::disk('public')->delete($resident->ktp_image);
        }

        $resident->delete();

        return $this->successResponse(null, 'Data penghuni berhasil dihapus');
    }
}
