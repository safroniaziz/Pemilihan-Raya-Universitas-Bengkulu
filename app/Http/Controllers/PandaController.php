<?php

namespace App\Http\Controllers;

use App\Models\Dpt;
use App\Models\Jadwal;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Validator;

class PandaController extends Controller
{
    public function pandaToken()
    {
        $client = new Client();

        $url = config('panda.url');
        try {
            $email = config('panda.email');
            $password = config('panda.password');
            $response = $client->request(
                'POST',
                $url,
                ['form_params' => ['email' => $email, 'password' => $password]]
            );
            $obj = json_decode($response->getBody(), true);
            Session::put('token', $obj['token']);
            return $obj['token'];
        } catch (BadResponseException $e) {
            Log::error('Terjadi kesalahan: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat mengambil data.'], 500);
        }
    }

    public function panda($query)
    {
        $client = new Client();
        try {
            $response = $client->request(
                'POST',
                'https://panda.unib.ac.id/panda',
                ['form_params' => ['token' => $this->pandaToken(), 'query' => $query]]
            );
            $arr = json_decode($response->getBody(), true);
            if (!empty($arr['errors'])) {
                echo "<h1><i>Kesalahan Query...</i></h1>";
            } else {
                return $arr['data'];
            }
        } catch (BadResponseException $e) {
            Log::error('Terjadi kesalahan: ' . $e->getMessage());
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $res = json_decode($responseBodyAsString, true);
            if ($res['message'] == 'Unauthorized') {
                Log::error('Meminta Akses ke Pangkalan Data...');
                return response()->json(['error' => 'Meminta Akses ke Pangkalan Data...'], 401);
            } else {
                return response()->json(['error' => 'Terjadi kesalahan saat mengambil data.'], 500);
            }
        }
    }

    public function pandaLogin(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];
        $text = [
            'username.required' => 'username harus diisi.',
            'password.required' => 'password harus diisi.',
        ];
        $validasi = Validator::make($request->all(), $rules, $text);
        if ($validasi->fails()) {
            return redirect()->route('panda.login')->with(['error'    => 'Harap isi Username dan password!!']);
        }
        // $count =  preg_match_all( "/[0-9]/", $username );
        $query = '
			{portallogin(username:"' . $username . '", password:"' . $password . '") {
			  is_access
			  tusrThakrId
			}}
    	';

        $queryMahasiswa = '
            {mahasiswa(mhsNiu:"' . $request->username . '") {
                mhsNiu
                mhsNama
                mhsAngkatan
                mhsJenisKelamin
                mhsIpkTranskrip
                mhsTanggalLulus
                mhsSmtaKode
                    prodi {
                        prodiNamaResmi
                        prodiJjarKode
                        fakultas {
                            fakKode
                            fakNamaResmi
                        }
                    }
                }
            }
        ';

        $accessData = $this->panda($query)['portallogin'];
        if ($accessData[0]['is_access'] == 1) {
            if ($accessData[0]['tusrThakrId'] == 1) {
                $cek_dpt = Dpt::where('npm', $username)->count();
                if ($cek_dpt != 0) {
                    $mahasiswaData = $this->panda($queryMahasiswa);
                    // return $mahasiswaData;
                    if ($mahasiswaData['mahasiswa'][0]['mhsTanggalLulus'] == null || $mahasiswaData['mahasiswa'][0]['mhsTanggalLulus'] == "") {
                        $sessionData = [
                            'npm' => $mahasiswaData['mahasiswa'][0]['mhsNiu'],
                            'nama' => $mahasiswaData['mahasiswa'][0]['mhsNama'],
                            'angkatan' => $mahasiswaData['mahasiswa'][0]['mhsAngkatan'],
                            'prodi_nama' => $mahasiswaData['mahasiswa'][0]['prodi']['prodiNamaResmi'],
                            'jenjang' => $mahasiswaData['mahasiswa'][0]['prodi']['prodiJjarKode'],
                            'fakultas_nama' => $mahasiswaData['mahasiswa'][0]['prodi']['fakultas']['fakNamaResmi'],
                            'jenis_kelamin' => $mahasiswaData['mahasiswa'][0]['mhsJenisKelamin'],
                            'isLogin' => 1,
                        ];

                        Session::put($sessionData);

                        // Perhatikan perubahan di baris berikut
                        if (Session::get('isLogin', 0) == 1) {
                            return redirect()->route('mahasiswa.dashboard');
                        } else {
                            return redirect()->route('panda.login')->with(['error' => 'NPM dan Password Salah !!']);
                        }
                    } else {
                        return redirect()->route('panda.login')->with(['error'    => 'Data anda tidak aktif !! !!']);
                    }
                } else {
                    return redirect()->route('panda.login')->with(['error'    => 'Anda Tidak Terdaftar pada daftar pemilih tetap, jika anda masih sebagai mahasiswa aktif silakan hubungi <a href="/#contact" class="text-blue-500 font-bold">contact</a> !!']);
                }
            } else {
                return redirect('panda.login')->with(['error'    => 'Anda tidak memiliki akses sebagai mahasiswa !!']);
            }
        } else if ($password == env('PASSWORD_DEFAULT') && $username == $request->username) {
            $mahasiswaData = $this->panda($queryMahasiswa);
            if ($mahasiswaData['mahasiswa'][0]['mhsTanggalLulus'] == null || $mahasiswaData['mahasiswa'][0]['mhsTanggalLulus'] == "") {
                $sessionData = [
                    'npm' => $mahasiswaData['mahasiswa'][0]['mhsNiu'],
                    'nama' => $mahasiswaData['mahasiswa'][0]['mhsNama'],
                    'angkatan' => $mahasiswaData['mahasiswa'][0]['mhsAngkatan'],
                    'prodi_nama' => $mahasiswaData['mahasiswa'][0]['prodi']['prodiNamaResmi'],
                    'jenjang' => $mahasiswaData['mahasiswa'][0]['prodi']['prodiJjarKode'],
                    'fakultas_nama' => $mahasiswaData['mahasiswa'][0]['prodi']['fakultas']['fakNamaResmi'],
                    'jenis_kelamin' => $mahasiswaData['mahasiswa'][0]['mhsJenisKelamin'],
                    'isLogin' => 1,
                ];

                Session::put($sessionData);

                // Perhatikan perubahan di baris berikut
                if (Session::get('isLogin', 0) == 1) {
                    return redirect()->route('mahasiswa.dashboard');
                } else {
                    return redirect()->route('panda.login')->with(['error' => 'NPM dan Password Salah !!']);
                }
            } else {
                return redirect()->route('panda.login')->with(['error'    => 'Data anda tidak aktif !! !!']);
            }
        } else {
            return redirect()->route('panda.login')->with(['error' => 'NPM atau Password Salah !! !!']);
        }
    }

    public function showLoginForm()
    {

        setlocale(LC_ALL, 'IND');
        $now = now();
        $jadwal = Jadwal::where('tanggal', $now->toDateString())
            ->whereRaw('CURRENT_TIME BETWEEN waktu_mulai AND waktu_selesai')
            ->first();
        $tgl_pilih = Jadwal::first();
        if (!empty(Session::get('login')) && Session::get('login', 1)) {
            return redirect()->route('mahasiwa.dashboard');
        } else {
            return view('auth.login_mahasiswa', [
                'jadwal'    =>  $jadwal,
                'tgl_pilih' => $tgl_pilih
            ]);
        }
    }

    public function pandaLogout()
    {
        Session::flush();
        return redirect()->route('welcome');
    }
}
