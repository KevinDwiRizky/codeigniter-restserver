<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth_middleware
{
    public function check_token()
    {
        $CI = &get_instance();

        $excluded_routes = [
            'api/v1/login',
            'auth/get_token',
            'absen/job',
            'cleanup'
        ];

        if (in_array($CI->uri->uri_string(), $excluded_routes)) {
            return;
        }

        try {
            $headers = $CI->input->get_request_header('Authorization', TRUE);

            if (!$headers || !preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                throw new Exception('Unauthorized');
            }

            $token = $matches[1];
            $decoded = JWT::decode($token, new Key($_ENV['SECRET_KEY'], 'HS256'));

            if (!$decoded) {
                throw new Exception('Invalid token');
            }

            if ($decoded->exp < time()) {
                throw new Exception('Token expired');
            }

            if ($CI->uri->uri_string() === 'api/v1/karyawan/save') {
                $CI->master_db  = $CI->load->database('master_db', TRUE);
                $level_id       = $decoded->level_id;

                $authorization_config = $CI->master_db->get_where(
                    'level_operator',
                    ['id' => $level_id]
                )->row();

                if ($authorization_config->users_add != 1 || $authorization_config->users_edit != 1) {
                    throw new Exception('Unauthorized');
                }
            }

            if ($CI->uri->uri_string() === 'api/v1/karyawan/delete') {
                $CI->master_db  = $CI->load->database('master_db', TRUE);
                $level_id       = $decoded->level_id;

                $authorization_config = $CI->master_db->get_where(
                    'level_operator',
                    ['id' => $level_id]
                )->row();

                if ($authorization_config->users_del != 1) {
                    throw new Exception('Unauthorized');
                }
            }
        } catch (Exception $e) {
            $CI->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status'    => FALSE,
                    'message'   => $e->getMessage(),
                    'data'      => []
                ]))
                ->_display();
            exit;
        }
    }
}
