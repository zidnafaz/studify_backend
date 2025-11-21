<?php

namespace App\Http\Controllers;

use App\Models\PersonalSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * MEMBUAT JADWAL BARU (POST)
     * Sesuai F-05
     */
    public function store(Request $request)
    {
        // 1. Validasi data yang dikirim dari Frontend (Flutter)
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time', // Waktu selesai harus setelah mulai
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        // 2. Otomatis isi user_id dengan ID user yang sedang login
        $validated['user_id'] = Auth::id();

        // 3. Simpan ke Database
        $schedule = PersonalSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dibuat',
            'data' => $schedule
        ], 201);
    }

    /**
     * MELIHAT DETAIL 1 JADWAL (GET)
     */
    public function show($id)
    {
        // Cari jadwal berdasarkan ID, tapi harus punya user yang login
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Jadwal tidak ditemukan atau bukan milikmu'], 404);
        }

        return response()->json(['success' => true, 'data' => $schedule]);
    }

    /**
     * MENGEDIT JADWAL (PUT/PATCH)
     * Sesuai F-05
     */
    public function update(Request $request, $id)
    {
        // Pastikan jadwal itu milik user yang login
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        // Validasi input baru
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        // Update data
        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil diupdate',
            'data' => $schedule
        ]);
    }

    /**
     * MENGHAPUS JADWAL (DELETE)
     * Sesuai F-05
     */
    public function destroy($id)
    {
        // Cari jadwal milik user
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        // Hapus (Soft Delete)
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dihapus'
        ]);
    }
}