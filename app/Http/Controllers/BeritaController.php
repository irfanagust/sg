<?php

namespace App\Http\Controllers;

use App\KategoriKomoditas; //DELETE SOON
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BeritaController extends Controller
{
    public function home()
    {
        return view('berita.index');
    }

    public function detail()
    {
        return view('detail');
    }

    public function beranda()
    {
        switch (Auth::user()->role_id) {
            case 1: //PETANI
                return view('user.index');
                break;
            case 2: //LPK
                # code...
                break;
            case 3: //PENGELOLA GUDANG
                # code...
                break;
            default:
                Auth::logout();
                return redirect()->route('login');
                break;
        }

        return view('user.index');
    }
}
