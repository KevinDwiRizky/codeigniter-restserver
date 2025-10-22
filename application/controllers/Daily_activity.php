<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Daily_activity extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Daily_activity_model');
        $this->load->model('Operator_model');
    }

    public function list_employee_get()
    {
        // Mengambil header Authorization dari request
        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        // Mengambil nilai shop dan user_id dari payload JWT
        $shop = $jwt_payload->shop_id;
        $id = $jwt_payload->user_id;

        // Memanggil model untuk mendapatkan operator berdasarkan shop
        $this->load->model('Daily_activity_model');
        $operators = $this->Daily_activity_model->get_operators_by_shop($shop);

        // Filter data untuk menghilangkan user dengan ID yang sama
        $filtered_operators = array_filter($operators, function($operator) use ($id) {
            return $operator['id'] != $id;
        });

        // Jika data ditemukan, kembalikan sebagai respons
        if ($filtered_operators) {
            // Menyusun response sesuai format yang diinginkan
            $formatted_operators = array_map(function($operator) {
                return [
                    'id' => (int) $operator['id'],
                    'name' => $operator['nama'],
                    'role' => $operator['role'],
                    'picture' => $operator['foto']
                ];
            }, $filtered_operators);

            $this->response([
                'status' => true,
                'data' => $formatted_operators
            ], 200);
        } else {
            // Jika data tidak ditemukan, berikan respons error
            $this->response([
                'status' => false,
                'message' => 'Tidak ada operator yang ditemukan untuk shop ini.'
            ], 200);
        }
    }

    public function get_daily_activity_get()
    {
        $id_operator = $this->get('id_operator');
        $date = $this->get('date');


        if (!$id_operator) {
            $this->response([
                'status' => false,
                'message' => 'ID Operator wajib diisi.'
            ], 400);
            return;
        }

        // Mendapatkan aktivitas dari model
        $activities = $this->Daily_activity_model->get_today_activity($id_operator, $date);
        $operator = $this->Operator_model->get_operator_by_id($id_operator);

        if ($activities) {

            $status_approved = true;
            foreach ($activities as $activity) {
                if (!isset($activity['id_pemeriksa']) || $activity['id_pemeriksa'] === null || $activity['id_pemeriksa'] === '') {
                    $status_approved = false;
                    break;
                }
            }

            $level = $this->Daily_activity_model->get_level_by_id($operator['level']);
            // Informasi pengguna
            $user = [
                'id' => $operator['id'],
                'name' => $operator['nama'],
                'role' => $level->nama_level,
                'picture' => base_url('public/images/karyawan/profile/' . $operator['foto'])
            ];


            // Format aktivitas sesuai dengan respons yang diminta
            $formatted_activities = array_map(function($activity) {
            $category  = $this->Daily_activity_model->get_ref_category($activity['id_daily']);

            $status_approved = true;
            if (!isset($activity['id_pemeriksa']) || $activity['id_pemeriksa'] === null || $activity['id_pemeriksa'] === '') {
                $status_approved = false;
            }

                return [
                    'id' => (int) $activity['id'],
                    'title'    => isset($category['keterangan']) ? $category['keterangan'] : '',
                    'category' => isset($category['nama_kategori']) ? $category['nama_kategori'] : '',
                    'ceklis'   => isset($activity['ceklis']) ? (int) $activity['ceklis'] : 0,
                    'approved' => isset($status_approved) ? $status_approved : false,
                    'time'     => isset($activity['waktu_daily']) ? $activity['waktu_daily'] : '',
                    'type'     => isset($activity['pekerjaan']) ? $activity['pekerjaan'] : '',
                    'evidence' => $activity['lampiran'] ? base_url('public/uploads/da/' . $activity['lampiran']) : null
                ];
            }, $activities);

            // Kirim response dengan format yang diminta
            $this->response([
                'status' => true,
                'data' => [
                    'approved' => $status_approved,
                    'user' => $user,
                    'activity' => $formatted_activities
                ]
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Tidak ada aktivitas yang ditemukan untuk parameter yang diberikan.'
            ], 200);
        }
    }

    public function get_activity_by_id_get()
    {
        $id_activity = $this->get('id');

        if (!$id_activity) {
            $this->response([
                'status' => false,
                'message' => 'ID Activity is required.'
            ], 400);
            return;
        }

        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id_user = $jwt_payload->user_id;


        $activity = $this->Daily_activity_model->get_activity_by_id($id_user, $id_activity);
        $ref_category = $this->Daily_activity_model->get_ref_category($id_activity);



        if ($activity) {
            $response = [
                'id' => (int) $activity['id'],
                'id_daily' => (int) $activity['id_daily'],
                'title' => $ref_category['keterangan'],
                'category' => $ref_category['nama_kategori'],
                'time' => $activity['waktu_daily'],
                'type' => $activity['pekerjaan'] === 'team' ? 'Team' : 'Individu',
                'evidence' => base_url('public/uploads/da/' . $activity['lampiran'])
            ];

            $this->response([
                'status' => true,
                'data' => $response
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Activity not found.'
            ], 200);
        }
    }

    public function get_daily_activity_list_get()
    {
        $categories = $this->Daily_activity_model->get_all_categories();

        if ($categories) {
            $formatted_activities = array_map(function($category) {
                return [
                    'id' => (int) $category['id'],
                    'title' => $category['nama_kategori'],
                    'status' => false,
                    'category' => $category['nama_kategori']
                ];
            }, $categories);

            $this->response([
                'status' => true,
                'data' => [
                    'submited' => false,
                    'activity' => $formatted_activities
                ]
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'No categories found.'
            ], 200);
        }
    }

    public function get_activities_get() {
        header('Content-Type: application/json');

        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id_level = $jwt_payload->level_id;
        $user_id = $jwt_payload->user_id;

        if (!$id_level) {
            $response = [
                'status' => false,
                'message' => 'id_level is required'
            ];
            echo json_encode($response);
            return;
        }

        $operator = $this->Operator_model->findById($user_id);

        if (!$operator) {
            $response = [
                'status' => false,
                'message' => 'Operator not found'
            ];
            echo json_encode($response);
            return;
        }

        $id_shift = (int) $operator->id_shift;

        // Tanggal hari ini
        $date = date('Y-m-d');

        $activity_by_shift = $this->Daily_activity_model->get_activity_by_shift($id_shift, $id_level, $date, $user_id);
        // $activity_by_shift = $this->Daily_activity_model->get_activity_by_shift(9, 7, $date, 546);


        $formatted_activities = [];
        foreach ($activity_by_shift as $activity) {
            $status = $this->Daily_activity_model->status_activity($user_id, $activity['id'], $date);
            $ref_daily_id = (int) $activity['id'];

            if ((int) $activity['weekly'] == 1) {
                $check_weekly_already = $this->Daily_activity_model->check_weekly_already($ref_daily_id, $user_id);

                if ($check_weekly_already) {
                    continue;
                }
            }

            $start_date = $activity['start_date'];
            $end_date = $activity['end_date'];

            if ($start_date && $end_date) {
                $priode = date('Y-m-d', strtotime($start_date)) . " - " . date('Y-m-d', strtotime($end_date));
            } else {
                $priode = null;
            }

            $formatted_activities[] = [
                'id' => (int)$activity['id'],
                'title' => $activity['title'],
                'status' => $status,
                'required_image' => $activity['evidence'] == 1 ? true : false,
                'category' => $activity['category'],
                'type' => $activity['tim'],
                'weekly' => (int) $activity['weekly'],
                'priode' => $priode
            ];
        }


        $submitted_activities = $this->Daily_activity_model->get_submitted_activities($user_id);

        // Susun response
        $response = [
            'status' => true,
            'data' => [
                'submited' => $submitted_activities,
                'activity' => $formatted_activities
            ]
        ];

        // Kembalikan response dalam format JSON
        echo json_encode($response);
    }

    public function send_activity_post()
    {
        $config['upload_path']   = './public/uploads/da/';
        $config['allowed_types'] = '*';
        $config['max_size']      = 10240;
        $config['encrypt_name']  = TRUE;

        $this->upload->initialize($config);

        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id = $jwt_payload->user_id;

        $required_fields = [
            'id'
        ];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (!$this->input->post($field) || trim($this->input->post($field)) === '') {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $this->response([
                'status' => false,
                'message' => 'Field berikut wajib diisi: ' . implode(', ', $missing_fields)
            ], 400);
            return;
        }

        $id_daily = $this->input->post('id');
        $today = date('Y-m-d');

        $existing_activity = $this->Daily_activity_model->check_existing_activity($id_daily, $today, $id);

        if ($existing_activity) {
            $this->response([
                'status' => false,
                'message' => 'ID Daily sudah ada dengan status terceklis untuk hari ini. Harap masukkan ID Daily yang lain.'
            ], 400);
            return;
        }

        $ref_daily_activity = $this->Daily_activity_model->get_ref_daily_activity($id_daily);
        $ref_evidence = (int) $ref_daily_activity->evidence;

        $lampiran = null;

        // Jika $ref_evidence bernilai 1, maka lampiran wajib
        if ($ref_evidence === 1 && empty($_FILES['evidence']['name'])) {
            $this->response([
                'status' => false,
                'message' => 'Evidence wajib dikirimkan untuk aktivitas ini.'
            ], 400);
            return;
        }

        // Proses upload file jika ada file yang diunggah
        if (!empty($_FILES['evidence']['name'])) {
            if (!$this->upload->do_upload('evidence')) {
                $this->response([
                    'status' => false,
                    'message' => $this->upload->display_errors()
                ], 400);
                return;
            } else {
                $uploadData = $this->upload->data();
                $lampiran = $uploadData['file_name'];
            }
        }
        $id_daily = $this->input->post('id');

        $daily = $this->Daily_activity_model->get_ref_by_id($id_daily);
        $nama = $daily->keterangan;
        $point = $daily->point;



        $type = null;

        if ($daily->tim == 'Tim') {
            $type = 'team';
        } elseif ($daily->tim == 'Individu') {
            $type = 'individu';
        }


        $data = [
            'id_daily'    => $id_daily,
            'ceklis'      => '1',
            'nama_daily'   => $nama,
            'ref_point'    => $point,
            'lampiran'    => $lampiran,
            'waktu_daily' => date('Y-m-d H:i:s'),
            'pekerjaan'   => $type,
            'id_operator' => $id
        ];

        if ($this->Daily_activity_model->insert_activity($data)) {
            $this->response([
                'status' => true,
                'message' => 'Tugas Berhasil Dikirim'
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal menyimpan aktivitas.'
            ], 500);
        }
    }

    public function update_activity_post()
    {
        $config['upload_path']   = './public/uploads/da/';
        $config['allowed_types'] = '*';
        $config['max_size']      = 10240;
        $config['encrypt_name']  = TRUE;

        $this->upload->initialize($config);

        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id_operator = $jwt_payload->user_id;

        $required_fields = ['id'];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (!$this->input->post($field) || trim($this->input->post($field)) === '') {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $this->response([
                'status' => false,
                'message' => 'Field berikut wajib diisi: ' . implode(', ', $missing_fields)
            ], 400);
            return;
        }

        $activity_id = $this->input->post('id');
        $id_daily = $this->input->post('id_daily');
        $type = $this->input->post('type');

        $existing_activity = $this->Daily_activity_model->get_activity_by_id($id_operator, $activity_id);

        if (!$existing_activity) {
            $this->response([
                'status' => false,
                'message' => 'Aktivitas tidak ditemukan.'
            ], 404);
            return;
        }

        // Pastikan data referensi aktivitas ada sebelum mengakses propertinya
        $ref_daily_activity = $this->Daily_activity_model->get_ref_daily_activity($id_daily);
        $ref_evidence = ($ref_daily_activity) ? (int) $ref_daily_activity->evidence : null;

        $update_data = [
            'pekerjaan' => $type,
            'waktu_daily' => date('Y-m-d H:i:s'),
            'id_operator' => $id_operator
        ];

        // Cek apakah evidence wajib diunggah
        if ($ref_evidence === 1 && empty($_FILES['evidence']['name']) && empty($existing_activity->lampiran)) {
            $this->response([
                'status' => false,
                'message' => 'Lampiran wajib diunggah untuk aktivitas ini.'
            ], 400);
            return;
        }

        // Proses upload file jika ada file yang diunggah
        if (!empty($_FILES['evidence']['name'])) {
            if (!$this->upload->do_upload('evidence')) {
                $this->response([
                    'status' => false,
                    'message' => $this->upload->display_errors()
                ], 400);
                return;
            } else {
                $uploadData = $this->upload->data();
                $update_data['lampiran'] = $uploadData['file_name'];
            }
        }

        $updated = $this->Daily_activity_model->update_activity($activity_id, $update_data);

        if ($updated) {
            $this->response([
                'status' => true,
                'message' => 'Aktivitas berhasil diperbarui.'
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal memperbarui aktivitas.'
            ], 500);
        }
    }

    public function rollback_activity_post() {
        $json_input = file_get_contents("php://input");
        $data = json_decode($json_input, true);

        $username = isset($data['username']) ? trim($data['username']) : null;
        $date = isset($data['date']) ? trim($data['date']) : null;

        // Validasi username harus ada
        if (empty($username)) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['status' => false, 'message' => 'Username wajib diisi']));
            return;
        }

        $result = $this->Daily_activity_model->delete_activity_by_username_and_date($username, $date);

        $this->output
            ->set_content_type('application/json')
            ->set_status_header($result['status'] ? 200 : 400)
            ->set_output(json_encode($result));
    }

    public function delete_activity_post() {
        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $shop = $jwt_payload->shop_id;
        $id = $jwt_payload->user_id;

        // Ambil id_daily dari input
        $id_daily = $this->input->post('id');
        $today = date('Y-m-d');

        // Cek apakah aktivitas dengan id_daily sudah ada di hari ini
        $existing_activity = $this->Daily_activity_model->check_existing_activity($id_daily, $today, $id);

        if (!$existing_activity) {
            $this->response([
                'status' => false,
                'message' => 'Tidak ada aktivitas dengan ID Daily tersebut untuk hari ini.'
            ], 400);
            return;
        }

        // Hapus aktivitas
        if ($this->Daily_activity_model->delete_activity($id_daily)) {
            // Hapus file lampiran jika ada
            $lampiran = $existing_activity['lampiran'];
            if ($lampiran && file_exists('./public/uploads/da/' . $lampiran)) {
                unlink('./public/uploads/da/' . $lampiran);
            }

            $this->response([
                'status' => true,
                'message' => 'Aktivitas berhasil dihapus.'
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal menghapus aktivitas.'
            ], 500);
        }
    }

    public function submit_all_activity_post() {
        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id = $jwt_payload->user_id;
        $name = $jwt_payload->name;

        $date = date('Y-m-d');

        // Simpan ke database
        $operator = $this->Operator_model->findById($id);
        $level = $operator->level;
        $id_shift = (int) $operator->id_shift;


        $result = $this->Daily_activity_model->insert_master_activity($id, $name, $date, $level, $id_shift);

        if ($result === false) {
            $this->response([
                'status' => false,
                'message' => 'Data dengan ID operator dan tanggal yang sama sudah ada.'
            ], 400);
        } elseif ($result) {
            $this->response([
                'status' => true,
                'message' => 'Telah Berhasil Submit Activity.'
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal menyimpan data.'
            ], 500);
        }
    }

    public function activities_today_get() {
        
        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id = $jwt_payload->user_id;
        $id_toko = $jwt_payload->shop_id;

        $operator = $this->Operator_model->findById($id);
        $level = $operator->level;

        $date = date('Y-m-d');

        $activities = $this->Daily_activity_model->get_activities_by_date($date, $id_toko,$level);
        
        if (!empty($activities)) {
            $mapped_activities = array_map(function($activity) {
                $id_user = $activity['id'];

                $operator = $this->Operator_model->findbyid($id_user);

                return [
                    'id' => (string)$activity['id'],  
                    'name' => $activity['name'],
                    'role' => $activity['role'],
                    'date'=> $activity['tanggal'],
                    'picture' => base_url('public/uploads/karyawan/profile/' . $operator->foto),
                ];
            }, $activities);

            $this->response([
                'status' => true,
                'message' => 'Data aktivitas berhasil ditemukan.',
                'count' => count($mapped_activities),
                'data' => $mapped_activities
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Tidak ada data aktivitas untuk hari ini.'
            ], 200);
        }
    }


    public function approved_post()
    {
        $json_data = json_decode($this->input->raw_input_stream, true);

        if (isset($json_data['id_operator']) && !empty($json_data['id_operator'])) {
            $id_operator = $json_data['id_operator'];
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'id_operator tidak ditemukan atau tidak valid.'
            ]);
            return;
        }

        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);

        $id_pemeriksa = $jwt_payload->user_id;
        $name = $jwt_payload->name;

        $date = $json_data['date'];

        $result = $this->Daily_activity_model->approved_today($id_operator, $id_pemeriksa, $name, $date);

        echo json_encode([
            'status' => $result['status'],
            'message' => $result['message']
        ]);
    }

    public function reject_post()
    {
        $config['upload_path']   = './public/uploads/da/';
        $config['allowed_types'] = '*';
        $config['max_size']      = 10240;
        $config['encrypt_name']  = TRUE;

        $this->upload->initialize($config);

        $token = get_authorization_token();
        $jwt_payload = get_jwt_payload($token);
        $id_pemeriksa = $jwt_payload->user_id;

        // Validasi input id_activity
        if (!$this->input->post('id_activity') || trim($this->input->post('id_activity')) === '') {
            $this->response([
                'status' => false,
                'message' => 'id_activity tidak ditemukan atau tidak valid.'
            ], 400);
            return;
        }

        $id_activity = $this->input->post('id_activity');

        // 🔍 Cek dulu status ceklis di database
        $activity = $this->db->get_where('dat_daily_activity', ['id' => $id_activity])->row();

        if (!$activity) {
            $this->response([
                'status' => false,
                'message' => 'Aktivitas tidak ditemukan.'
            ], 404);
            return;
        }

        if ($activity->ceklis == '0') {
            $this->response([
                'status' => false,
                'message' => 'Aktivitas ini belum dikerjakan, tidak dapat direject.'
            ], 400);
            return;
        }

        // Validasi evidence wajib
        if (empty($_FILES['evidence']['name'])) {
            $this->response([
                'status' => false,
                'message' => 'Evidence wajib diunggah.'
            ], 400);
            return;
        }

        // Proses upload
        if (!$this->upload->do_upload('evidence')) {
            $this->response([
                'status' => false,
                'message' => $this->upload->display_errors()
            ], 400);
            return;
        }

        $uploadData = $this->upload->data();
        $original_path = $uploadData['full_path'];
        $original_name = $uploadData['file_name'];

        // Tambahkan prefix "reject_"
        $renamed_file = 'reject_' . $original_name;
        $renamed_path = $uploadData['file_path'] . $renamed_file;

        // Rename file fisik
        rename($original_path, $renamed_path);

        // Simpan nama file yang baru untuk ke DB
        $evidence_file = $renamed_file;

        // Panggil model untuk update
        $result = $this->Daily_activity_model->reject_activity($id_activity, $evidence_file);

        if ($result) {
            $this->response([
                'status' => true,
                'message' => 'Aktivitas berhasil ditolak dan evidence tersimpan.'
            ], 200);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal menolak aktivitas.'
            ], 500);
        }
    }

    public function rollback_reject_post()
{
    $token = get_authorization_token();
    $jwt_payload = get_jwt_payload($token);
    $id_user = $jwt_payload->user_id;

    // Validasi input id_activity
    if (!$this->input->post('id_activity') || trim($this->input->post('id_activity')) === '') {
        $this->response([
            'status' => false,
            'message' => 'id_activity tidak ditemukan atau tidak valid.'
        ], 400);
        return;
    }

    $id_activity = $this->input->post('id_activity');

    // Cek apakah datanya ada
    $activity = $this->db->get_where('dat_daily_activity', ['id' => $id_activity])->row();

    if (!$activity) {
        $this->response([
            'status' => false,
            'message' => 'Aktivitas tidak ditemukan.'
        ], 404);
        return;
    }

    // Update kolom yang diperlukan
    $this->db->set('ceklis', '1');
    $this->db->set('ditolak', 0);
    $this->db->set('lampiran_validasi', null);
    $this->db->where('id', $id_activity);
    $updated = $this->db->update('dat_daily_activity');

    if ($updated) {
        $this->response([
            'status' => true,
            'message' => 'Reject berhasil di-rollback. Aktivitas dikembalikan seperti semula.'
        ], 200);
    } else {
        $this->response([
            'status' => false,
            'message' => 'Gagal melakukan rollback.'
        ], 500);
    }
}


}
