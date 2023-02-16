<?php

namespace App\Http\Controllers;

use App\AturanKualitas;
use App\AturanMutu;
use App\Gudang;
use App\KategoriKomoditas;
use App\KategoriKomoditasDetail;
use App\Komoditas;
use App\VerifikasiKualitas;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\PseudoTypes\True_;
use stdClass;
use Yajra\DataTables\DataTables;

class KomoditasController extends Controller
{
    public function cetakSuratKualitas($id)
    {
        $data = Komoditas::with('verifikasi_kualitas','user_info','kategori_komoditas_detail','gudang')->where('id',$id)->firstOrFail();
        $pdf = PDF::loadview('pdf.surat-kualitas', [
            'data' => $data
        ]);
        return $pdf->download('surat-uji-kualitas');
    }
    
    public function ujiKualitas(Request $request, $id)
    {
        request()->validate(
            [
                'warna' => 'required|numeric',
                'seragam' => 'numeric|required',
                'panjang' => 'numeric|required',
                'pangkal' => 'numeric|required',
                'kotor' => 'required|numeric',
                'busuk' => 'required|numeric',
            ],
            [
                'required' => 'Harap pilih salah satu',
                'numeric' => 'Tidak valid'
            ]
        );

        $poinWarna = AturanKualitas::where('keterangan', 'warna')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->warna)->where('nilai_max','>=',$request->warna);
        })->first();

        $poinSeragam = AturanKualitas::where('keterangan', 'seragam')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->seragam)->where('nilai_max','>=',$request->seragam);
        })->first();

        $poinPanjang = AturanKualitas::where('keterangan', 'panjang')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->panjang)->where('nilai_max','>=',$request->panjang);
        })->first();

        $poinPangkal = AturanKualitas::where('keterangan', 'pangkal')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->pangkal)->where('nilai_max','>=',$request->pangkal);
        })->first();

        $poinKotor = AturanKualitas::where('keterangan', 'kotor')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->kotor)->where('nilai_max','>=',$request->kotor);
        })->first();

        $poinBusuk = AturanKualitas::where('keterangan', 'busuk')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->busuk)->where('nilai_max','>=',$request->busuk);
        })->first();

        $totalPoin = $poinWarna->poin + $poinSeragam->poin + $poinPanjang->poin + $poinPangkal->poin + $poinKotor->poin + $poinBusuk->poin;

        $mutu = AturanMutu::where(function($query)use($totalPoin){
            $query->where('total_poin_min','<=',$totalPoin)->where('total_poin_max','>=',$totalPoin);
        })->first();

        DB::transaction(function () use($request,$id,$totalPoin,$mutu){
            $komoditas = Komoditas::findOrFail($id);
            $komoditas->status_uji_kualitas = 2;
            $komoditas->save();

            $verifikasiKualitas = new VerifikasiKualitas();
            $verifikasiKualitas->komoditas()->associate($komoditas); 
            $verifikasiKualitas->warna = $request->warna;
            $verifikasiKualitas->seragam = $request->seragam;
            $verifikasiKualitas->panjang = $request->panjang;
            $verifikasiKualitas->pangkal = $request->pangkal;
            $verifikasiKualitas->kotor = $request->kotor;
            $verifikasiKualitas->busuk = $request->busuk;
            $verifikasiKualitas->total_poin = $totalPoin;
            $verifikasiKualitas->mutu = $mutu->keterangan;
            $verifikasiKualitas->save();
        });

        return redirect()->back()->with('alert','Komoditas berhasil diuji.');
    }

    public function tolakKomoditas(Request $request, $id)
    {
        try {
            DB::transaction(function ()use($id) {
                $komoditas = Komoditas::findOrFail($id);
                $komoditas->status_pengajuan = 2;
                $komoditas->save();
            });
        } catch (\Throwable $th) {
            return redirect()->back()->with('alert','Gagal');
        }
        return redirect()->back()->with('alert','Penolakan komoditas berhasil dilakukan');
    }
    
    public function jsonKomoditas()
    {
        switch (Auth::user()->role_id) {
            case 1:                         //PETANI
                $user_info_id = Auth::user()->user_info->id;

                return DataTables::of(Komoditas::where('user_info_id',$user_info_id)->with('kategori_komoditas_detail','verifikasi_kualitas')->where('status',true)->get())
                    ->addIndexColumn()
                    ->addColumn('status_pengajuan', function($row){
                        switch ($row->status_pengajuan) {
                            case 1:
                                $action = '<a class="btn btn-xs btn-secondary" href="#">Belum diperiksa</a>';
                                break;
                            case 2:
                                $action = '<a class="btn btn-xs btn-warning" href="#">Ditolak</a>';
                                break;
                            case 3:
                                $action = '<a class="btn btn-xs btn-success" href="#">Disetujui</a>';
                                break;
                            
                            default:
                                $action = '<a class="btn btn-xs btn-danger" href="#">Undefined</a>';
                                break;
                        }
                        
                        return $action;
                    })
                    ->addColumn('kuantitas_tersisa', function($row){
                        $action = $row->kuantitas-$row->terjual;
                        
                        return $action; 
                    })
                    ->addColumn('telah_terjual', function($row){
                        $action = '
                            <small>Kuantitas awal</small> <a class="btn btn-xs btn-info">'.$row->kuantitas.'</a>
                            <br>
                            <small>Kuantitas terjual</small> <a class="btn btn-xs btn-danger">'.$row->terjual.'</a>
                        ';
                        
                        return $action; 
                    })
                    ->addColumn('mutu', function($row){
                        if (isset($row->verifikasi_kualitas)) {
                            $action = '
                                <a class="btn btn-xs btn-info">'.$row->verifikasi_kualitas->mutu.'</a>
                            ';
                        } else {
                            $action = '
                                <a class="btn btn-xs btn-warning">Belum diuji</a>
                            ';
                        }
                        return $action; 
                    })
                    ->addColumn('status_uji_kualitas', function($row){
                        switch ($row->status_uji_kualitas) {
                            case 1:
                                $action = '<a class="btn btn-xs btn-secondary" href="#">Belum diperiksa</a>';
                                break;
                            case 2:
                                $action = '
                                    <a class="btn btn-xs btn-successx" href="#">Disetujui</a>
                                    <a class="btn btn-xs btn-secondary" href="#">'.$row->verifikasi_kualitas->mutu.'</a>
                                ';
                                break;
                            
                            default:
                                $action = '<a class="btn btn-xs btn-danger" href="#">Undefined</a>';
                                break;
                        }
                        
                        return $action;
                    })
                    ->addColumn('action', function($row){
                        $id = $row->id;
                        $detailKomoditasUrl = route('komoditas.show',$id);
                        $editKomoditasUrl = route('komoditas.edit',$id);

                        if ($row->status_pengajuan == 1) {
                            $action = '
                                <a class="btn btn-xs btn-info" href="'.$detailKomoditasUrl.'">Detail</a>
                                <a class="btn btn-xs btn-secondary" href="'.$editKomoditasUrl.'">Edit</a>
                                <a class="btn btn-xs btn-danger hapus" id="'.$id.'">Hapus</a>
                            ';
                        } elseif($row->status_pengajuan == 2) {
                            $action = '
                                <a class="btn btn-xs btn-secondary" href="komoditas/'.$id.'">Detail</a>
                                <a class="btn btn-xs btn-danger hapus" id="'.$id.'">Hapus</a>
                            ';
                        }elseif($row->status_pengajuan == 3){
                            if ($row->status_uji_kualitas == 2) {
                                if ($row->terjual == $row->kuantitas) {
                                    $action = '
                                        <a class="btn btn-xs btn-info" href="'.$detailKomoditasUrl.'">Detail</a>
                                        <a class="btn btn-xs btn-dark" href="komoditas/cetak-surat-mutu/'.$id.'">Cetak</a>
                                    ';
                                } else {
                                    $action = '
                                        <a class="btn btn-xs btn-info" href="'.$detailKomoditasUrl.'">Detail</a>
                                        <a class="btn btn-xs btn-dark" href="komoditas/cetak-surat-mutu/'.$id.'">Cetak</a>
                                        <a class="btn btn-xs btn-success jual" id="'.$id.'">Terjual</a>
                                    ';
                                }
                                
                            }else{
                                $action = '
                                    <a class="btn btn-xs btn-info" href="'.$detailKomoditasUrl.'">Detail</a>
                                ';
                            }
                        }else{
                            $action = '
                                <a class="btn btn-xs btn-danger hapus" id="'.$id.'>Hapus</a>
                            ';
                        }
                        
                        return $action; 
                    })
                    ->rawColumns([
                        'action','status_pengajuan','status_uji_kualitas','mutu','telah_terjual'
                    ])
                    ->make(true);
                break;
            case 2:                         //LPK
                return DataTables::of(Komoditas::with('kategori_komoditas_detail','user_info')->where('status_pengajuan',3)->where('status', true))
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        $id = $row->id;
                        if ($row->status_uji_kualitas == 1) {
                            $action = '
                                <a class="btn btn-xs btn-secondary aksi" id="'.$id.'">Aksi</a>
                            ';
                        } else {
                            $url = route('komoditas.show',$row->id);
                            $action = '
                                <a class="btn btn-xs btn-info detail" href="'.$url.'">Detail</a>
                            ';
                        }
                        return $action; 
                    })
                    ->addColumn('mutu', function($row){
                        $id = $row->id;
                        if (isset($row->verifikasi_kualitas)) {
                            $action = '
                                <a class="btn btn-xs btn-info">'.$row->verifikasi_kualitas->mutu.'</a>
                            ';
                        } else {
                            $action = '
                                <a class="btn btn-xs btn-warning">Belum diuji</a>
                            ';
                        }
                        return $action; 
                    })
                    ->rawColumns([
                        'action',
                        'mutu',
                    ])
                    ->make(true);
                break;
            case 3:                         //PENGELOLA GUDANG
                return DataTables::of(Komoditas::with('kategori_komoditas_detail','user_info')->where('desa_id',Auth::user()->user_gudang->desa_id)->where('status',true))
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        $id = $row->id;

                        if ($row->status_pengajuan == 1) {
                            $action = '
                                <a class="btn btn-xs btn-info" href="komoditas/'.$id.'">Detail</a>
                            ';
                        } elseif($row->status_pengajuan == 2) {
                            $action = '
                                <a class="btn btn-xs btn-secondary" href="komoditas/'.$id.'">Detail</a>
                            ';
                        }elseif($row->status_pengajuan == 3){
                            if ($row->status_uji_kualitas == 2) {
                                $action = '
                                    <a class="btn btn-xs btn-info" href="komoditas/'.$id.'">Detail</a>
                                    <a class="btn btn-xs btn-dark" href="komoditas/cetak-surat-mutu/'.$id.'">Cetak</a>
                                ';
                            }else{
                                $action = '
                                    <a class="btn btn-xs btn-info" href="komoditas/'.$id.'">Detail</a>
                                ';
                            }
                        }else{
                            $action = '
                                <a class="btn btn-xs btn-danger hapus" id="'.$id.'>Hapus</a>
                            ';
                        }
                        
                        return $action; 
                    })
                    ->addColumn('telah_terjual', function($row){
                        $action = '
                            <small>Kuantitas awal</small> <a class="btn btn-xs btn-info">'.$row->kuantitas.'</a>
                            <br>
                            <small>Kuantitas terjual</small> <a class="btn btn-xs btn-danger">'.$row->terjual.'</a>
                        ';
                        
                        return $action; 
                    })
                    ->addColumn('kuantitas_tersisa', function($row){
                        $action = $row->kuantitas-$row->terjual;
                        
                        return $action; 
                    })
                    ->addColumn('mutu', function($row){
                        if (isset($row->verifikasi_kualitas)) {
                            $action = '
                                <a class="btn btn-xs btn-info">'.$row->verifikasi_kualitas->mutu.'</a>
                            ';
                        } else {
                            $action = '
                                <a class="btn btn-xs btn-warning">Belum diuji</a>
                            ';
                        }
                        return $action; 
                    })
                    ->addColumn('status_komoditas',function ($row){
                        if ($row->status_pengajuan == 1) {
                            $action = '
                                <a class="btn btn-xs btn-info">Belum diperiksa</a>
                            ';
                        } elseif($row->status_pengajuan == 2) {
                            $action = '
                            <a class="btn btn-xs btn-warnign">Ditolak</a>
                            ';
                        }elseif($row->status_pengajuan == 3){
                            $action = '
                                <a class="btn btn-xs btn-success">Disetujui</a>
                            ';
                        }else{
                            $action = '
                                <a class="btn btn-xs btn-danger">Unidentified</a>
                            ';
                        }
                        return $action;
                    })
                    ->rawColumns([
                        'action',
                        'status_komoditas',
                        'telah_terjual',
                        'mutu'
                    ])
                    ->make(true);
                break;
            default:
                Auth::logout();
                return redirect()->route('login');
                break;
        }
    }

    public function getDetailKategoriKomoditas($id)
    {
        return json_encode(
            KategoriKomoditasDetail::where('kategori_komoditas_id',$id)->get()
        );
    }

    public function getKomoditasById($id)
    {
        $result = Komoditas::with('kategori_komoditas_detail','gudang')->where('id',$id)->first();
        return response()->json($result);
    }
    
    public function index()
    {
        switch (Auth::user()->role_id) {
            case 1:
                return view('user.komoditas.index');
                break;
            case 2:
                return view('user.komoditas.index');
                break;
            case 3:
                return view('user.komoditas.index');
                break;
            default:
                Auth::logout();
                return redirect()->route('login');
                break;
        }
    }

    public function create()
    {
        $kategori = KategoriKomoditas::all();
        return view('user.komoditas.create', compact(
            'kategori'
        ));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role_id != 1) {
            return redirect()->back()->with('alert','anda tidak memiliki akses');
        }

        request()->validate(
            [
                'kuantitas' => 'required|numeric',
                'kategori' => 'numeric',
                'detail_kategori' => 'numeric',
                'harga_minimal' => 'required|numeric',
                'harga_maksimal' => 'required|numeric',
            ],
            [
                'kuantitas.required' => 'Harap isi.',
                'kategori.numeric' => 'Harap pilih salah satu',
                'detail_kategori.numeric' => 'Harap pilih salah satu',
                'harga_minimal.required' => 'Harap isi',
                'harga_maksimal.required' => 'Harap isi',
            ]
        );
        // dd($request);
        $detailKomoditas = KategoriKomoditasDetail::findOrFail($request->detail_kategori);

        $komoditas = new Komoditas;
        $komoditas->harga_minimal = $request->harga_minimal;
        $komoditas->harga_maksimal = $request->harga_maksimal;
        $komoditas->kuantitas = $request->kuantitas;
        $komoditas->kategori_komoditas_detail()->associate($detailKomoditas);
        $komoditas->user_info()->associate(Auth::user()->user_info);
        $komoditas->desa()->associate(Auth::user()->user_info->desa);
        $saved = $komoditas->save();

        if ($saved) {
            return redirect()->route('komoditas.index')->with('alert','Pengajuan Komoditas '.$detailKomoditas->keterangan.' berhasil');
        }

        return redirect()->route('komoditas.index')->with('alert','Pengajuan Komoditas '.$detailKomoditas->keterangan.' gagal');
    }

    public function show($id)
    {
        $komoditas = Komoditas::findOrFail($id);
        $gudang = Gudang::where('desa_id', $komoditas->desa_id)->get();
        return view('user.komoditas.show',compact(
            'komoditas',
            'gudang'
        ));
    }

    public function edit($id)
    {
        $komoditas = Komoditas::findOrFail($id);
        if ($komoditas->status_pengajuan != 1) {
            return redirect()->back()->with('alert','Tidak bisa melakukan proses edit');
        }
        $kategori = KategoriKomoditas::all();
        return view('user.komoditas.edit', compact(
            'komoditas',
            'kategori',
        ));
    }

    public function update(Request $request, $id)
    {
        request()->validate(
            [
                'kuantitas' => 'required|numeric',
                'kategori' => 'numeric',
                'detail_kategori' => 'numeric',
                'harga' => 'required|numeric',
            ],
            [
                'kuantitas.required' => 'Harap isi.',
                'kategori.numeric' => 'Harap pilih salah satu',
                'detail_kategori.numeric' => 'Harap pilih salah satu',
                'harga.required' => 'Harap isi',
            ]
        );
        
        $detailKomoditas = KategoriKomoditasDetail::findOrFail($request->detail_kategori);

        $komoditas = Komoditas::findOrFail($id);
        $komoditas->harga_harapan = $request->harga;
        $komoditas->kuantitas = $request->kuantitas;
        $komoditas->kategori_komoditas_detail()->associate($detailKomoditas);
        $komoditas->user_info()->associate(Auth::user()->user_info);
        $saved = $komoditas->save();

        if ($saved) {
            return redirect()->route('komoditas.index')->with('alert','Ubah pengajuan komoditas '.$detailKomoditas->keterangan.' berhasil');
        }

        return redirect()->route('komoditas.index')->with('alert','Ubah pengajuan komoditas '.$detailKomoditas->keterangan.' gagal');
    }

    public function destroy($id)
    {
        $id = (int)$id;
        $komoditas = Komoditas::findOrFail($id);
        $komoditas->status = false;
        $deleted = $komoditas->save();

        if ($deleted) {
            return redirect()->route('komoditas.index')->with('alert','Komoditas '.$komoditas->kategori_komoditas_detail->keterangan.' berhasil dihapus');
        }

        return redirect()->route('komoditas.index')->with('alert','Hapus pengajuan komoditas '.$komoditas->kategori_komoditas_detail->keterangan.' gagal');
    }

    public function penggudangan(Request $request)
    {
        
        request()->validate(
            [
                'harga_jual' => 'required|numeric',
                'warna' => 'required|numeric',
                'seragam' => 'numeric|required',
                'panjang' => 'numeric|required',
                'pangkal' => 'numeric|required',
                'kotor' => 'required|numeric',
                'busuk' => 'required|numeric',
            ],
            [
                'required' => 'Harap pilih salah satu',
                'numeric' => 'Tidak valid',
            ]
        );

        $komoditas = Komoditas::findOrFail($request->komoditas);
        $gudang = Gudang::findOrFail($request->gudang);

        if ($gudang->terisi+$komoditas->kuantitas > $gudang->kuota) {
            return redirect()->back()->with('alert','Kuota gudang penuh');
        }

        $poinWarna = AturanKualitas::where('keterangan', 'warna')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->warna)->where('nilai_max','>=',$request->warna);
        })->first();

        $poinSeragam = AturanKualitas::where('keterangan', 'seragam')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->seragam)->where('nilai_max','>=',$request->seragam);
        })->first();

        $poinPanjang = AturanKualitas::where('keterangan', 'panjang')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->panjang)->where('nilai_max','>=',$request->panjang);
        })->first();

        $poinPangkal = AturanKualitas::where('keterangan', 'pangkal')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->pangkal)->where('nilai_max','>=',$request->pangkal);
        })->first();

        $poinKotor = AturanKualitas::where('keterangan', 'kotor')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->kotor)->where('nilai_max','>=',$request->kotor);
        })->first();

        $poinBusuk = AturanKualitas::where('keterangan', 'busuk')->where(function($query)use($request){
            $query->where('nilai_min','<=',$request->busuk)->where('nilai_max','>=',$request->busuk);
        })->first();

        $totalPoin = $poinWarna->poin + $poinSeragam->poin + $poinPanjang->poin + $poinPangkal->poin + $poinKotor->poin + $poinBusuk->poin;

        $mutu = AturanMutu::where(function($query)use($totalPoin){
            $query->where('total_poin_min','<=',$totalPoin)->where('total_poin_max','>=',$totalPoin);
        })->first();

        try {
            DB::transaction(function () use($request,$totalPoin,$mutu, $komoditas){
                $komoditas->status_uji_kualitas = 2;
    
                $verifikasiKualitas = new VerifikasiKualitas();
                $verifikasiKualitas->komoditas()->associate($komoditas); 
                $verifikasiKualitas->warna = $request->warna;
                $verifikasiKualitas->seragam = $request->seragam;
                $verifikasiKualitas->panjang = $request->panjang;
                $verifikasiKualitas->pangkal = $request->pangkal;
                $verifikasiKualitas->kotor = $request->kotor;
                $verifikasiKualitas->busuk = $request->busuk;
                $verifikasiKualitas->total_poin = $totalPoin;
                $verifikasiKualitas->mutu = $mutu->keterangan;
                $verifikasiKualitas->save();
    
                $gudang = Gudang::findOrFail($request->gudang);
                $gudang->terisi = $gudang->terisi + $komoditas->kuantitas;
                $gudang->save();
    
                $komoditas->status_pengajuan = 3;
                
                $komoditas->harga_jual = $request->harga_jual;
                $komoditas->status_komoditas_di_gudang = 2;
                $komoditas->gudang()->associate($gudang);

                $komoditas->save();
                $verifikasiKualitas->save();
                $komoditas->save();
            });
        } catch (\Throwable $th) {
            return redirect()->route('komoditas.index')->with('alert','Komoditas gagal diproses');
        }

        return redirect()->route('komoditas.index')->with('alert','Komoditas '.$komoditas->kategori_komoditas_detail->keterangan.' berhasil masuk gudang');
    }

    public function manage(Request $request)
    {
        request()->validate(
            [
                'komoditas_id' => 'required|numeric'
            ],
            [
                'required' => 'Harap isi pilih salah satu',
                'numeric' => 'Tidak valid',
            ]
        );
        $komoditas = Komoditas::findOrFail($request->komoditas_id);
        $komoditas->status_komoditas_di_gudang = 3;
        
        $komoditas->gudang->terisi = $komoditas->gudang->terisi - $komoditas->kuantitas;
        
        $saved = $komoditas->gudang->save();
        if ($saved) {
            $saved = $komoditas->save();
    
            if ($saved) {
                return redirect()->back()->with('alert','Komoditas '.$komoditas->kategori_komoditas_detail->keterangan.' berhasil dikosongkang');
            }

            return redirect()->back()->with('alert','Komoditas '.$komoditas->kategori_komoditas_detail->keterangan.' gagal diproses');
        }

        return redirect()->back()->with('alert','Komoditas '.$komoditas->kategori_komoditas_detail->keterangan.' gagal diproses');
    }

    public function komoditasTerjual(Request $request, $id)
    {
        $komoditas = Komoditas::findOrFail($id);

        if ($komoditas->kuantitas < ($komoditas->terjual+$request->terjual)) {
            return redirect()->back()->with('alert','Kuantitas terjual melebihi kuantitas komoditas yang ada');
        }

        try {
            $komoditas->terjual = $komoditas->terjual + $request->terjual;
            if ($komoditas->kuantitas == ($komoditas->terjual + $request->terjual)) {
                $komoditas->terjual = true;
            }
            $komoditas->save();

            return redirect()->back()->with('alert','Pengurangan komoditas berhasil dilakukan');
        } catch (\Throwable $th) {
            return redirect()->with('alert','Pengurangan komoditas gagal');
        }
    }
}
