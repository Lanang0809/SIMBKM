<?php

namespace App\Http\Controllers;

use App\Models\CommentLaporan;
use App\Models\Mbkm;
use App\Models\User;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\Fakultas;
use App\Models\Laporan;
use App\Models\ProgramMbkm;
use App\Models\TahunAjaranMbkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class MbkmController extends Controller
{

    public function programIndex()
    {
        return view('dashboard.program-mbkm', [
            'title' => 'Program MBKM',
            'title_page' => 'Program MBKM',
            'active' => 'Program Mbkm',
            'name' => auth()->user()->name,
            'programMbkm' => ProgramMbkm::all()
        ]);
    }

    public function create()
    {
        return view('dashboard.create-program-mbkm', [
            'title' => 'Create',
            'title_page' => 'Program MBKM / Create',
            'active' => 'Program Mbkm',
            'name' => auth()->user()->name,
        ]);
    }

    public function storeProgram(Request $request)
    {
        
        $validatedData = $request->validate([
            'name' => 'required|unique:program_mbkms',
            'status' => 'required',
            'deskripsi' => 'required',
            'fotoikon' => 'required|image'
        ]);
        if ($request->hasFile('fotoikon')) {
            if ($request->file('fotoikon')->isValid()) {
                $validatedData['fotoikon'] = $request->fotoikon->storeAs('img', 'public');
            } else {
                return redirect('/dashboard/program-mbkm')->with('error', 'Upload file gagal!');
            }
        } else {
            return redirect('/dashboard/program-mbkm')->with('error', 'File tidak ditemukan!');
        }
        
        ProgramMbkm::create($validatedData);

        return redirect('/dashboard/program-mbkm')->with('success', 'Program MBKM Berhasil Dibuat!');
    }

    public function edit($id)
    {
        return view('dashboard.edit-program-mbkm', [
            'title' => 'Edit',
            'title_page' => 'Program Mbkm / Edit',
            'active' => 'Program Mbkm',
            'name' => auth()->user()->name,
            'program' => ProgramMbkm::find($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $mbkm = ProgramMbkm::find($id);
    
        if ($mbkm) {
            $validatedData = $request->validate([
                'name' => 'required|unique:program_mbkms,name,' . $mbkm->id,
                'status' => 'required',
                'deskripsi' => 'required',
                'fotoikon' => 'image'
            ]);
    
            if ($request->hasFile('fotoikon')) {
                if ($request->file('fotoikon')->isValid()) {
                    // Delete the old file
                    if ($mbkm->fotoikon && Storage::disk('public')->exists('img/' . $mbkm->fotoikon)) {
                        $deleteStatus = Storage::disk('public')->delete('img/' . $mbkm->fotoikon);
            
                        if (!$deleteStatus) {
                            // Log an error message if the file couldn't be deleted
                            Log::error("Could not delete image: public/img/" . $mbkm->fotoikon);
                        }
                    }
            
                    // Store the new file
                    $filename = $request->fotoikon->store('img', 'public');
                    $validatedData['fotoikon'] = $filename;
                } else {
                    // If no new image is uploaded, keep the old image
                    $validatedData['fotoikon'] = $mbkm->fotoikon;
                }
            }
    
            $mbkm->update($validatedData);
    
            return redirect('/dashboard/program-mbkm')->with('success', 'Data Program Mbkm has been updated!');
        } else {
            return redirect('/dashboard/program-mbkm')->with('error', 'Program Mbkm not found!');
        }
    }

    public function destroy($id)
    {
        $mbkm = ProgramMbkm::find($id);
        if ($mbkm) {
            $mbkm->delete();
            return redirect('/dashboard/program-mbkm')->with('success', 'Program Mbkm has been deleted!');
        } else {
            return redirect('/dashboard/program-mbkm')->with('error', 'Program Mbkm not found!');
        }
    }

    
    

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'nim' => 'required',
            'fakultas' => 'required',
            'jurusan' => 'required',
            'semester' => 'required',
            'program' => 'required',
            'tanggal_mulai' => 'required',
            'mobilisasi' => 'nullable',
            'lokasi_program' => 'nullable',
            'lokasi_mobilisasi' => 'nullable',
            'pembimbing_industri' => 'nullable',
            'dosen_pembimbing' => 'required',
            'informasi_tambahan' => 'nullable',
            'tanggal_selesai' => 'required',
            'tahun_ajaran' => 'required',
            'tempat_program_perusahaan' => 'required',
            'program_keberapa' => 'required',
        ]);

        $validatedData['user'] = auth()->user()->id;

        Mbkm::create($validatedData);

        $lastIdMbkm = DB::table('mbkms')
            ->select('id')
            ->where('user', '=', auth()->user()->id)
            ->orderByDesc('id')
            ->limit(1)
            ->get();

        Logbook::create([
            'name' => auth()->user()->name,
            'mbkm' => $lastIdMbkm[0]->id,
            'user' => auth()->user()->id
        ]);

        Laporan::create([
            'mbkm' => $lastIdMbkm[0]->id,
            'owner' => auth()->user()->id
        ]);

        $lastIdLaporan = DB::table('laporans')
            ->select('id')
            ->where('owner', '=', auth()->user()->id)
            ->orderByDesc('id')
            ->limit(1)
            ->get();

        CommentLaporan::create([
            'body' => 'Belum ada komen',
            'laporan' => $lastIdLaporan[0]->id,
            'user' => auth()->user()->id
        ]);

        return redirect('/dashboard/informasi-mbkm')->with('success', 'New Data Mbkm has been added!');
    }

    public function myForm()
    {
        return view('dashboard.my-mbkm-form', [
            'title' => 'My Mbkm Form',
            'title_page' => 'Informasi Mbkm / Form Mbkm Saya',
            'active' => 'Informasi MBKM',
            'name' => auth()->user()->name,
            'mbkms' => Mbkm::where('user', auth()->user()->id)->with('listPI')->with('listUser')->get()
        ]);
    }

    public function editMyForm($mbkm)
    {

        //     if($author != auth()->user()->id) {
        //         abort(403);
        //    }

        return view('dashboard.edit-my-mbkm-form', [
            'title' => 'Edit',
            'title_page' => 'Informasi Mbkm / Form Mbkm Saya / Edit',
            'active' => 'Informasi MBKM',
            'name' => auth()->user()->name,
            'mbkm' => Mbkm::find($mbkm),
            'fakultas' => Fakultas::where('status', 'Aktif')->get(),
            'programs' => ProgramMbkm::where('status', 'Aktif')->get(),
            'tahun_ajaran' => TahunAjaranMbkm::all()->sortByDesc('id'),
            'jurusans' => Jurusan::where('status', 'Aktif')->get(),
            'dosbing' => User::where('role', '4')->orWhere('role_kedua', '4')->orWhere('role_ketiga', '4')->get(),
            'pembimbing_industri' => User::where('role', '6')->orWhere('role_kedua', '6')->orWhere('role_ketiga', '6')->get(),
        ]);
    }

    public function updateMyForm(Request $request, $mbkm)
    {

        //     if($mbkm != auth()->user()->id) {
        //         abort(403);
        //    }

        $form = Mbkm::find($mbkm);

        $rules = [
            'name' => 'required|max:255',
            'nim' => 'required',
            'fakultas' => 'required',
            'jurusan' => 'required',
            'semester' => 'required',
            'program' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'required',
            'mobilisasi' => 'nullable',
            'lokasi_program' => 'nullable',
            'lokasi_mobilisasi' => 'nullable',
            'pembimbing_industri' => 'nullable',
            'dosen_pembimbing' => 'required',
            'informasi_tambahan' => 'nullable',
            'tahun_ajaran' => 'required',
            'tempat_program_perusahaan' => 'required',
            'program_keberapa' => 'required',
        ];

        $form->update($request->validate($rules));
        return redirect('/dashboard/informasi-mbkm/personal')->with('success', 'Data Mbkm has been updated!');
    }


    public function getAllMBKM()
    {
        return view('dashboard.list-mbkm-mhsw', [
            'title' => 'Daftar MBKM Mahasiswa',
            'title_page' => 'MBKM Mahasiswa',
            'active' => 'Mbkm Mahasiswa',
            'name' => auth()->user()->name,
            'programMbkm' => Mbkm::all()
        ]);
    }


    public function getMBKM($mbkm)
    {
        return view('dashboard.get-mbkm-mhsw', [
            'title' => 'Lihat',
            'title_page' => 'Informasi Mbkm Mahasiswa',
            'active' => 'Informasi MBKM',
            'name' => auth()->user()->name,
            'mbkm' => Mbkm::find($mbkm),
            'fakultas' => Fakultas::where('status', 'Aktif')->get(),
            'programs' => ProgramMbkm::where('status', 'Aktif')->get(),
            'tahun_ajaran' => TahunAjaranMbkm::all()->sortByDesc('id'),
            'jurusans' => Jurusan::where('status', 'Aktif')->get(),
            'dosbing' => User::where('role', '4')->orWhere('role_kedua', '4')->orWhere('role_ketiga', '4')->get(),
            'pembimbing_industri' => User::where('role', '6')->orWhere('role_kedua', '6')->orWhere('role_ketiga', '6')->get(),
        ]);
    }
}
