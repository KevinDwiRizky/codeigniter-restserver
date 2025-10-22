<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Receiver_model extends CI_Model
{
    public function insert_batch_with_files($post, $files)
{
    $count = count($post['nama']);
    $insert_data = [];

    $upload_path = './public/uploads/foto/';
    if (!is_dir($upload_path)) mkdir($upload_path, 0777, true);

    $this->load->library('upload');

    for ($i = 0; $i < $count; $i++) {
        $foto_name = null;

        if (isset($files['foto']['name'][$i]) && $files['foto']['name'][$i] != '') {
            $_FILES['temp_foto']['name']     = $files['foto']['name'][$i];
            $_FILES['temp_foto']['type']     = $files['foto']['type'][$i];
            $_FILES['temp_foto']['tmp_name'] = $files['foto']['tmp_name'][$i];
            $_FILES['temp_foto']['error']    = $files['foto']['error'][$i];
            $_FILES['temp_foto']['size']     = $files['foto']['size'][$i];

            $config['upload_path']   = $upload_path;
            $config['allowed_types'] = '*';
            $config['encrypt_name']  = true;
            $config['max_size']      = 10240;

            $this->upload->initialize($config);

            if ($this->upload->do_upload('temp_foto')) {
                $upload_data = $this->upload->data();
                $foto_name = $upload_data['file_name'];
            } else {
                log_message('error', 'Upload gagal untuk ' . $files['foto']['name'][$i] . ': ' . $this->upload->display_errors('', ''));
            }
        }

        $insert_data[] = [
            'nama'        => $post['nama'][$i],
            'nik'         => $post['nik'][$i],
            'pelayanan'   => $post['pelayanan'][$i],
            'foto'        => $foto_name,
            'no_antrian'  => $post['no_antrian'][$i],
            'loket'       => $post['loket'][$i],
        ];
    }

    if (!empty($insert_data)) {
        $this->db->insert_batch('dat_user_antrian', $insert_data);
    }

    return $insert_data;
}

}
