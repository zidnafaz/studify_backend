<?php

namespace App\Http\Controllers;

use App\Models\PersonalSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PersonalScheduleController extends Controller
{
    /**
     * MENAMPILKAN SEMUA JADWAL PRIBADI (GET)
     * Sesuai F-04 & F-05
     */
    public function index()
    {
        // Kita cuma ambil jadwal milik user yang sedang LOGIN
        // Jadi user A tidak bisa lihat jadwal user B.
        $schedules = PersonalSchedule::where('user_id', Auth::id())
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'data' => $schedules
        ]);
    }

    /**
     * MEMBUAT JADWAL BARU (POST)
     * Sesuai F-05
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = Auth::id();

        $schedule = PersonalSchedule::create($data);

        return response()->json([
            'message' => 'Jadwal pribadi berhasil dibuat',
            'data' => $schedule,
        ], 201);
    }

    /**
     * MELIHAT DETAIL 1 JADWAL (GET)
     */
    public function show($id)
    {
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal pribadi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $schedule,
        ]);
    }

    /**
     * MENGEDIT JADWAL (PUT/PATCH)
     * Sesuai F-05
     */
    public function update(Request $request, $id)
    {
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal pribadi tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schedule->update($validator->validated());

        return response()->json([
            'message' => 'Jadwal pribadi berhasil diperbarui',
            'data' => $schedule,
        ]);
    }

    /**
     * MENGHAPUS JADWAL (DELETE)
     * Sesuai F-05
     */
    public function destroy($id)
    {
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal pribadi tidak ditemukan',
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'message' => 'Jadwal pribadi berhasil dihapus',
        ]);
    }
}
