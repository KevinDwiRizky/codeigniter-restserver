<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;

class Auth extends RestController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function verify_post()
    {
        try {
            $_POST = json_decode(file_get_contents('php://input'), TRUE);

            $is_valid_request = $this->_validate_request();

            if ($is_valid_request !== TRUE) {
                throw new Exception('Invalid request');
            }

            $username = $this->post('username', TRUE);
            $password = md5($this->post('password', TRUE));

            // Ambil user dari dat_users
            $query = $this->db->get_where('dat_users', [
                'username' => $username,
                'password' => $password
            ]);

            if ($query->num_rows() > 0) {
                $user = $query->row();

                $payload = [
                    'iss'       => base_url(),
                    'iat'       => time(),
                    'exp'       => time() + 60 * 60 * 12, // 12 jam
                    'user_id'   => $user->id,
                    'name'      => $user->nama,
                    'username'  => $user->username,
                    'level'     => $user->level
                ];

                $token = JWT::encode($payload, $_ENV['SECRET_KEY'], 'HS256');

                $this->response([
                    'status'  => TRUE,
                    'message' => 'User verified',
                    'data'    => [
                        'user' => [
                            "id"       => (int) $user->id,
                            "name"     => $user->nama,
                            "username" => $user->username,
                            "level"    => $user->level
                        ]
                    ],
                    'token'   => $token
                ], RestController::HTTP_OK);
            } else {
                throw new Exception('Invalid username or password');
            }
        } catch (Exception $e) {
            $this->response([
                'status'  => FALSE,
                'message' => $e->getMessage(),
                'data'    => []
            ], RestController::HTTP_OK);
        }
    }

    public function get_token_post()
    {
        $username = $this->post('username', TRUE);
        $password = md5($this->post('password', TRUE));

        $query = $this->db->get_where('dat_users', [
            'username' => $username,
            'password' => $password
        ]);

        if ($query->num_rows() > 0) {
            $user = $query->row();

            $payload = [
                'iss'       => base_url(),
                'iat'       => time(),
                'exp'       => time() + 60 * 60 * 12,
                'user_id'   => $user->id,
                'name'      => $user->nama,
                'username'  => $user->username,
                'level'     => $user->level
            ];

            $token = JWT::encode($payload, $_ENV['SECRET_KEY'], 'HS256');

            $this->response([
                'status'  => TRUE,
                'message' => 'User verified',
                'token'   => $token
            ]);
        } else {
            $this->response([
                'status'  => FALSE,
                'message' => 'User invalid',
                'token'   => NULL
            ]);
        }
    }

    private function _validate_request()
    {
        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('username', 'Username', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            return [
                'status'   => FALSE,
                'messages' => $this->form_validation->error_array()
            ];
        } else {
            return TRUE;
        }
    }
}
