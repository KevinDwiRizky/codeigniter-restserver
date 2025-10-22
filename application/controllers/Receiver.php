<?php
use chriskacerguis\RestServer\RestController;

defined('BASEPATH') or exit('No direct script access allowed');

class Receiver extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Receiver_model');
    }

    public function receiver_batch_post()
    {
        try {
            // Ambil semua data POST dan FILES
            $postData = $this->input->post();
            $fileData = $_FILES;

            // Panggil model dan simpan hasilnya
            $inserted = $this->Receiver_model->insert_batch_with_files($postData, $fileData);

            // Respon sukses
            $this->response([
                'status'  => TRUE,
                'message' => count($inserted) . ' data berhasil disimpan',
                'data'    => $inserted
            ], RestController::HTTP_OK);

        } catch (Exception $e) {
            // Respon error
            $this->response([
                'status'  => FALSE,
                'message' => $e->getMessage(),
                'data'    => []
            ], RestController::HTTP_BAD_REQUEST);
        }
    }
}
