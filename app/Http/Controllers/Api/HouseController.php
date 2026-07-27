<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseHistory;
use App\Models\Resident;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $houses = House::with(['currentHistory.resident'])->orderBy('house_number')->get();

        return $this->successResponse($houses, 'Daftar rumah berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'house_number' => 'required|string|unique:houses,house_number',
            'house_status' => 'nullable|in:occupied,vacant',
        ]);

        $house = House::create([
            'house_number' => $validated['house_number'],
            'house_status' => $validated['house_status'] ?? 'vacant',
        ]);

        return $this->successResponse($house, 'Rumah berhasil ditambahkan', 201);
    }

    public function show(string $id): JsonResponse
    {
        $house = House::with([
            'currentHistory.resident',
            'histories.resident',
            'invoices.resident'
        ])->find($id);

        if (!$house) {
            return $this->errorResponse('Data rumah tidak ditemukan', null, 404);
        }

        return $this->successResponse($house, 'Detail rumah berhasil diambil');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $house = House::find($id);

        if (!$house) {
            return $this->errorResponse('Data rumah tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'house_number' => 'required|string|unique:houses,house_number,' . $id,
            'house_status' => 'required|in:occupied,vacant',
        ]);

        $house->update($validated);

        return $this->successResponse($house, 'Data rumah berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $house = House::find($id);

        if (!$house) {
            return $this->errorResponse('Data rumah tidak ditemukan', null, 404);
        }

        $house->delete();

        return $this->successResponse(null, 'Rumah berhasil dihapus');
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        $house = House::find($id);

        if (!$house) {
            return $this->errorResponse('Data rumah tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'start_date' => 'nullable|date',
        ]);

        $resident = Resident::find($validated['resident_id']);
        $startDate = $validated['start_date'] ?? Carbon::now()->format('Y-m-d');

        DB::transaction(function () use ($house, $resident, $startDate) {
            $house->update(['house_status' => 'occupied']);

            HouseHistory::where('house_id', $house->id)
                ->whereNull('end_date')
                ->update(['end_date' => $startDate]);

            HouseHistory::create([
                'house_id' => $house->id,
                'resident_id' => $resident->id,
                'start_date' => $startDate,
                'end_date' => null,
            ]);
        });

        $house->load(['currentHistory.resident']);

        return $this->successResponse($house, 'Penghuni berhasil ditempatkan di rumah ini');
    }

    public function unassign(Request $request, string $id): JsonResponse
    {
        $house = House::find($id);

        if (!$house) {
            return $this->errorResponse('Data rumah tidak ditemukan', null, 404);
        }

        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        DB::transaction(function () use ($house, $endDate) {
            HouseHistory::where('house_id', $house->id)
                ->whereNull('end_date')
                ->update(['end_date' => $endDate]);

            $house->update(['house_status' => 'vacant']);
        });

        $house->load(['currentHistory.resident']);

        return $this->successResponse($house, 'Status rumah berhasil diubah menjadi tidak dihuni (kosong)');
    }
}
