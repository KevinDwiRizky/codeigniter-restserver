<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Forward extends RestController
{
    private $base_url = "http://localhost/aplikasi-antrian-cloud/api/v1";

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['file', 'url']);
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    }

    public function execute_post()
    {
        try {
            $start_date = $this->post('start_date') ?? '2025-10-01';
            $end_date   = $this->post('end_date') ?? '2025-10-22';

            echo "<h3>1️⃣ Login</h3>";
            $login_data = $this->curl_post_json($this->base_url.'/login', [
                'username'=>'admin',
                'password'=>'123qwe'
            ]);
            var_dump($login_data);

            if (empty($login_data['token'])) throw new Exception("Login gagal: token tidak ditemukan");
            $token = $login_data['token'];

            echo "<h3>2️⃣ Ambil data dari receiver/data</h3>";
            $data = $this->curl_get($this->base_url.'/receiver/data', $token, [
                'start_date'=>$start_date,
                'end_date'=>$end_date
            ]);
            var_dump($data);

            if (empty($data['data'])) {
                echo "<p>Tidak ada data ditemukan.</p>";
                return;
            }

            $records = $data['data'];
            $sent_count = 0;

            echo "<h3>3️⃣ Forward setiap record ke receiver/register</h3>";
            foreach ($records as $index => $row) {
                echo "<hr><strong>Record #".($index+1)."</strong><br>";
                var_dump($row);

                // Persiapkan form fields
                $post_fields = [
                    'nama'       => $row['nama'],
                    'nik'        => $row['nik'],
                    'pelayanan'  => $row['pelayanan'],
                    'no_antrian' => $row['no_antrian'],
                    'loket'      => $row['loket']
                ];

                // Ambil foto dari URL project luar
                if (!empty($row['foto'])) {
                    $foto_path = $this->download_temp_file_external($row['foto'], $index);
                    if ($foto_path && file_exists($foto_path)) {
                        // Kirim sebagai file
                        $post_fields['foto'] = new CURLFile($foto_path);
                        echo "Foto siap dikirim: ".$foto_path."<br>";
                    } else {
                        echo "Foto tidak ditemukan / gagal download.<br>";
                    }
                } else {
                    echo "Foto tidak tersedia.<br>";
                    // You can either skip this record or assign a default image here
                    // Assign default image if foto is required
                    //$post_fields['foto'] = new CURLFile('path/to/default/image.jpg');
                }

                // Before sending the request, check all necessary fields
                if (empty($post_fields['foto'])) {
                    echo "Foto tidak tersedia, record akan dilewatkan.<br>";
                    continue; // Skip this record if foto is missing
                }

                // Kirim form-data ke receiver/register
                echo "Kirim ke receiver/register ...<br>";
                $raw_response = $this->curl_post_multipart_raw($this->base_url.'/receiver/register', $token, $post_fields);

                echo "Response mentah:<br>";
                var_dump($raw_response);

                $response = json_decode($raw_response, true);
                echo "Response decode:<br>";
                var_dump($response);

                $sent_count++;

                // Hapus foto sementara
                if ($foto_path && file_exists($foto_path)) unlink($foto_path);
            }

            echo "<h3>✅ Forward selesai. Total: $sent_count item</h3>";

        } catch (Exception $e) {
            echo "<h3>❌ Terjadi kesalahan:</h3>";
            echo "<pre>".$e->getMessage()."</pre>";
        }
    }

    /**
     * Download foto dari URL eksternal ke temp folder
     */
    private function download_temp_file_external($url, $index = null)
    {
        if (empty($url)) return null;

        // Buat nama file unik pakai index atau uniqid
        $filename = ($index !== null ? $index . '_' : '') . uniqid() . '_' . basename($url);

        $temp_dir = FCPATH.'temp_uploads/';
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);

        $temp_path = $temp_dir . $filename;

        // Download file
        $content = @file_get_contents($url);
        if ($content === false) {
            echo "Gagal download file: $url<br>";
            return null;
        }

        if (file_put_contents($temp_path, $content) === false) {
            echo "Gagal simpan file ke temp_uploads.<br>";
            return null;
        }

        echo "Foto berhasil diunduh: $temp_path<br>";
        return $temp_path;
    }

    private function curl_post_json($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        if (curl_errno($ch)) echo 'cURL error: '.curl_error($ch);
        curl_close($ch);
        return json_decode($result,true);
    }

    private function curl_get($url, $token, $params=[])
    {
        $url .= '?'.http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$token]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) echo 'cURL error: '.curl_error($ch);
        curl_close($ch);
        return json_decode($result,true);
    }

    private function curl_post_multipart_raw($url, $token, $fields)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Hanya kirim Authorization, biarkan cURL atur Content-Type multipart
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$token]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        if (curl_errno($ch)) echo 'cURL error: '.curl_error($ch);
        curl_close($ch);

        return $result;
    }
}
