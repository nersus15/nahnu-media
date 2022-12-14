<?php defined('BASEPATH') or exit('No direct script access allowed');
class Ws extends CI_Controller{
    function login(){
        $this->load->library('Authentication');

        if(!httpmethod())
            response("Metode akses ilegal");

        list($input) = $this->authentication->persiapan($_POST);
        $this->authentication->login($input);
    }
    function update_profile(){
        if(!httpmethod())
            response("Metode akses ilegal", 403);
        if(!is_login())
            response("Anda belum login", 403);

        $post = $_POST;
        $dataUser = fieldmapping('user', $post);
        if(is_login('member')){
            $dataMember = fieldmapping('member', $_POST);
            $dataMember['penanggung_jawab'] = $dataUser['nama'];
            $dataUser['member'] = $post['memberid'];
        }

        if(isset($dataUser['password']) && !empty($dataUser['password']))
            $dataUser['password'] = password_hash($dataUser['password'], PASSWORD_DEFAULT);
        else
            unset($dataUser['password']);

        if(isset($_FILES['pp']) && !empty($_FILES['pp'])){
            $this->load->helper('file_upload_helper');
            $fname = uploadImage($_FILES['pp'], 'pp', 'profile');

            if(sessiondata('login', 'photo') != 'default.jpg')
                delete_img(sessiondata('login', 'photo'));
            $dataUser['photo'] = $fname;
        }
        if(is_login('member'))
            $this->db->where('id', sessiondata('login', 'memberid'))->update('member', $dataMember);
        $this->db->where('id', sessiondata('login', 'id'))->update('user', $dataUser);

        // Update in localdata
        $tmp = sessiondata();
        if(is_login('member')){
            foreach ($dataMember as $key => $value) {
                if(sessiondata('login', $key) != $value)
                    $tmp[$key] = $value;
            }
        }
        foreach ($dataUser as $key => $value) {
            if($key == 'password') continue;
            if(sessiondata('login', $key) != $value)
                $tmp[$key] = $value;
        }
        $this->session->set_userdata('login', $tmp);
        response("Berhasil update profile");
    }

    function cek_username(){
        if(!httpmethod()) response("Ilegal Akses", 403);
        if(!is_login()) response("Anda belum login", 403);
        if(sessiondata('login', 'username') == $_POST['username']) response(['boleh' => true]);
        if(!isset($_POST['username']) || empty($_POST['username'])) response(['boleh' => false]);
        $usernameBaru = $_POST['username'];
        
        $user = $this->db->select('*')->where('username', $usernameBaru)->get('user')->result();
        if(!empty($user))
            response(['boleh'=> false]);
        else
            response(['boleh' => true]);
    }
    function logout(){
        if (!is_login())
            response(['message' => 'Anda belum login', 'type' => 'error'], 401);

        try {
            $this->session->unset_userdata('login');
            response(['message' => 'Anda berhasil logout', 'type' => 'success'], 200);
        } catch (\Throwable $th) {
            response(['message' => 'Gagal, Terjadi kesalahan', 'type' => 'error', 'err' => $th], 500);
        }
    }
    function get_lapangan(){
        $this->load->model('Lapangan');
        if(isset($_GET['rid']) && $_GET['rid'] == 1){
            if(isset($_GET['l']) && $_GET['l'] == 1){
                response($this->Lapangan->get_lastid());
            }else{
                $lapangan = $this->Lapangan->get_last();
                response($lapangan);
            }
        }
        $data = $this->Lapangan->get_all();
        response($data);
    }

    function add_lapangan(){
        $post = $_POST;
        $this->load->model('Lapangan');
        if($_SERVER['REQUEST_METHOD'] != 'UPDATE'){
            list($_,$res) = $this->Lapangan->create($post);
            response($res, $_ ? 200 : 500);
        }else{
            list($_,$res) = $this->Lapangan->update($post);
            response($res, $_ ? 200 : 500);
        }
            
    }

    function delete_lapangan(){
        $post = $_POST;
        $this->load->model('Lapangan');
        if(isset($post['ids']) && !empty($post['ids'])){
            list($_, $res) = $this->Lapangan->delete($post['ids']);
            response($res, $_ ? 200 : 500);
        }
    }

    function get_jadwal(){
        $this->load->library('Datatables');
        $q = $this->db->from('jadwal')->join('lapangan', 'lapangan.id = jadwal.lapangan');

        $this->datatables->setHeader(array(
            'id' => array('searchable' => false),
            'mulai' => array('searchable' => true),
            'selesai' => array('searchable' => true),
            'lapangan' => array('searchable' => true, 'field' => 'jadwal.lapangan'),
            'tarif' => array('searhcable' => true),
            'jenis' => array('searchable' => true, 'field' => 'jadwal.jenis'),
            'tempat' => array('searchable' => true, 'field' => 'tempat')
        ));
        $this->datatables->addSelect('jadwal.*, lapangan.jenis, lapangan.tempat');
        $this->datatables->setQuery($q);
        $data = $this->datatables->getData();
        response($data);
    }

    function add_jadwal(){
        $post = $_POST;
        $post = $_POST;
        $this->load->model('Jadwal');
        if($_SERVER['REQUEST_METHOD'] != 'UPDATE'){
            list($_,$res) = $this->Jadwal->create($post);
            response($res, $_ ? 200 : 500);
        }else{
            list($_,$res) = $this->Jadwal->update($post);
            response($res, $_ ? 200 : 500);
        }
    }
    function delete_jadwal(){
        $post = $_POST;
        $this->load->model('Lapangan');
        if(isset($post['ids']) && !empty($post['ids'])){
            list($_, $res) = $this->Lapangan->delete($post['ids']);
            response($res, $_ ? 200 : 500);
        }
    }

    function get_member(){
        $this->load->model('Member');
        if(isset($_GET['rid']) && $_GET['rid'] == 1){
           response($this->Member->get_lastid());
        }
        $data = $this->Member->get_all();
        response($data);
    }
    function get_memberby(){
        $this->load->model('Member');
        if(!empty($_GET))
            $where = $_GET;
        else
            $where = null;
        if(!empty($where)){
            $tmp = array();
            foreach($where as $k => $v){
                $tmp[str_replace('-', '.', $k)] = $v;
            }
            $where = $tmp;
        }
        response(array('data' => $this->Member->get_by($where)));
    }
    function add_member(){
        $this->load->model('Member');
        $post = $_POST;
        if($_SERVER['REQUEST_METHOD'] != 'UPDATE'){
           list($_, $res) = $this->Member->create($post);
           response($res, $_ ? 200 : 500);
        }else{
           list($_, $res) = $this->Member->update($post);
           response($res, $_ ? 200 : 500);
        }
    }
    function delete_member(){
        $this->load->model('Member');
        $post = $_POST;
        if(isset($post['ids']) && !empty($post['ids'])){
           list($_, $res) = $this->Member->delete($post['ids']);
           response($res, $_ ? 200 : 500);
        }
    }

    function booking(){
        $this->load->model('Booking');
        $post = $_POST;
        if($_SERVER['REQUEST_METHOD'] == 'UPDATE'){
            if(isset($post['member']) && empty($post['member']))
                unset($post['member']);
            $id = array($post['id']);
            unset($post['id']);
            list($_, $res, $data) = $this->Booking->update($post, $id);
            $dataNotif = [];
            if(is_login('admin') && isset($data['member'])){
                $user = $this->db->select('id')->where('member', $data['member'])->get()->row();
                $dataNotif[] = [
                    'id' => random(8),
                    'dibuat' => waktu(),
                    'pesan' =>  "Bookingan anda dengan id <b>" . $data['id'] . '</b> Telah diupdate oleh Admin',
                    'jenis' =>  'personal',
                    'user' =>  $user->id,
                    'link' => 'pembayaran/' . $data['id']
                ];
            }elseif(is_login('member')){
                $dataNotif[] = [
                    'id' => random(8),
                    'dibuat' => waktu(),
                    'pesan' =>  "Anda telah mengupdate bookingan dengan id <b>" . $data['id'] . '</b>',
                    'jenis' =>  'personal',
                    'user' =>  sessiondata('login', 'id'),
                    'link' => 'pembayaran/' . $data['id']
                ];
                $dataNotif[] = [
                    'id' => random(8),
                    'dibuat' => waktu(),
                    'pesan' =>  "Bokingan dengan id <b>" . $data['id'] . ' telah diupdate</b>',
                    'jenis' =>  'global',
                    'role' => 'admin',
                ];
            }
        }elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
            if(isset($post['member']) && empty($post['member']))
                unset($post['member']);
            if(isset($post['id']) && !empty($post['id']))
                unset($post['id']);

            list($_, $res, $data) = $this->Booking->create($post);
            $batch = false;
            $dataNotif = [
               [
                'id' => random(8),
                'dibuat' => waktu(),
                'pesan' =>  "Bookingan baru telah dibuat dengan id <b>" . $data['id'] . "</b>",
                'jenis' =>  'global',
                'role' =>  'admin',
               ]
            ];
            if(isset($data['member']) && !isset($data['registrar'])){
                $batch = true;
                $dataNotif[] = [
                    'id' => random(8),
                    'dibuat' => waktu(),
                    'pesan' =>  "Anda telah membuat bookingan baru dengan id <b>" . $data['id'] . '</b> Segera lakukan pembayaran',
                    'jenis' =>  'personal',
                    'user' =>  sessiondata('login', 'id'),
                    'link' => 'pembayaran/' . $data['id']
                ];
            }else if(isset($data['member']) && isset($data['registrar'])){
                // TODO: Buat Notifikasi, untuk member yang didaftarkan admin
                $user = $this->db->select('id')->where('member', $data['member'])->get()->row();
                $dataNotif[] = [
                    'id' => random(8),
                    'dibuat' => waktu(),
                    'pesan' =>  "Anda telah membuat bookingan baru melalui admin, booking id <b>" . $data['id'] . '</b> Segera lakukan pembayaran',
                    'jenis' =>  'personal',
                    'user' =>  $user->id,
                    'link' => 'pembayaran/' . $data['id']
                ];
            }
            
            $this->notification->create($dataNotif, $batch);
        }
        response(['message' => $res, 'id' => isset($data['id']) ? $data['id'] : null], $_ ? 200 : 500);
    }
    function update_status_booking($status){
        $this->load->model('Booking');
        $post = $_POST;
        $ids = $post['ids'];
        list($_, $res, $data) = $this->Booking->update(array('status' => $status), $ids);
        $dataNotif = [];
        if(is_login('member')){
            foreach($ids as $id){
                $dataNotif[] = [
                    'id' => random(8),
                    'dibuat' => waktu(),
                    'pesan' =>  "Status Bookingan dengan id #" . $id . " Telah dirubah menjadi " . $status,
                    'jenis' =>  'global',
                    'role' => 'admin',
                    'link' => 'pembayaran/' . $id
                ];
            }
        }else if(is_login('admin')){
            foreach($ids as $id){
                $user = $this->db->select('user.id')
                    ->join('member', 'member.id = booking.member')
                    ->join('user', 'user.member = member.id')
                    ->where('booking.id', $id)
                    ->get('booking')->row();
                if(!empty($user)){
                    $dataNotif[] = [
                        'id' => random(8),
                        'dibuat' => waktu(),
                        'pesan' =>  "Status Bookingan anda dengan id #" . $id . " Telah dirubah menjadi " . $status,
                        'jenis' =>  'personal',
                        'user' => $user->id,
                        'link' => 'pembayaran/' . $id
                    ];
                }
            }
        }
        
        $this->notification->create($dataNotif);
        response(['message' => $res, 'id' => isset($data['id']) ? $data['id'] : null], $_ ? 200 : 500);
    }
    function get_booking($member = null){
        $this->load->model('Booking');
        $data = $this->Booking->get_all($member);
        response($data);
    }

    function upload(){
        $this->load->helper('file_upload_helper');
        $fname = uploadImage($_FILES['file'], 'file', 'booking');
        $newId = random(15);
        $this->db->insert('file_upload', array(
            'id' => $newId,
            'nama' => $fname,
            'uuid' => $_POST['uuid']
        ));
        $dataNotif[] = [
            'id' => random(8),
            'dibuat' => waktu(),
            'pesan' =>  "Bukti pembayaran Bookingan dengan id #" . $_POST['id'] . " Telah diupload, segera konfirmasi!",
            'jenis' =>  'global',
            'role' => 'admin',
        ];
        $this->notification->create($dataNotif);
        $this->db->where('id', $_POST['id'])->update('booking', array('bukti_bayar' => $newId));
    }
    function cancel_upload(){
    }

    function cekjadwal(){
        if(!isset($_GET['j']) || !isset($_GET['t']))
            response("Parameter yang dibutuhkan tidak tersedia", 500);

        $tanggal = $_GET['t'];
        $jadwal = $_GET['j'];
        $boleh = true;
        $data = $this->db->select('*')
            ->where('tanggal', $tanggal)
            ->where('jadwal', $jadwal)
            ->get('booking')->result();
        
        if(!empty($data)){
            foreach($data as $v){
                if($v->status != 'selesai' && $v->status != 'batal'){
                    $boleh =  false;
                    continue;
                }
            }
        }

        response(['kosong' => $boleh]);
    }

    function file(){
        $download = false;
        if(!isset($_GET['l']))
            response("Invalid", 403);
        if(isset($_GET['d']) && $_GET['d'] == 1)
            $download = true;

        $location = $_GET['l'];
        if(!file_exists(get_path(ASSETS_PATH . '/img' . $location)))
            response("Not Found", 404);
        $name = explode('/', $location);
        $name = end($name);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $location = get_path(ASSETS_PATH . '/img' . $location);
        if($download){
            $this->load->helper('download');
            $content = file_get_contents(get_path(ASSETS_PATH . '/img' . $location));
            force_download($name . '.', $content, true);
        }
        else{
            $fp = fopen($location, 'rb');
            // send the right headers
            header("Content-Type: image/" . strtolower($extension));
            header("Content-Length: " . filesize($name));
            // dump the picture and stop the script
            $contents = fread($fp, filesize($location)); 
            fclose($fp); 
            echo $contents;
        }
    }

    function baca_notif($nid){
        $this->notification->baca($nid);
    }

    function get_admin(){
        $this->load->library('Datatables');
        $q = $this->db->from('user')->where('role', 'admin');

        $this->datatables->setHeader(array(
            'username' => array('searchable' => true),
            'id' => array('searchable' => false),
            'nama' => array('searchable' => true),
            'hp' => array('searchable' => true),
            'email' => array('searchable' => true),
            'photo' => array('searhcable' => true),
        ));
        $this->datatables->setQuery($q);
        $data = $this->datatables->getData();
        response($data);
    }

    function add_admin(){
        $post = $this->input->post();
        $id = $post['id'];
        unset($post['id']);
        $post['username'] = str_replace(' ', '_', $post['username']);
        if(httpmethod('update')){
            try {
                if(isset($post['password']) && empty($post['password'])){
                    unset($post['password']);
                }elseif(isset($post['password']) && !empty($post['password'])){
                    $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
                }
                $this->db->where('id', $id)->update('user', $post);
                response("Berhasil Update Admin");
            } catch (\Throwable $th) {
                response("Gagal Update", 500);
            }
        }elseif(httpmethod()){
            try {
                $this->db->insert('user', $post + ['id' => random(8)]);
                response("Berhasil Mendaftarkan Admin");
            } catch (\Throwable $th) {
                response("Gagal Mendaftarkan Admin", 500);
            }
        }
    }
    function delete_admin(){
        $post = $this->input->post();
        if(isset($post['ids']) && !empty($post['ids'])){
           try {
               $ids = $post['ids'];
               $this->db->where_in('id', $ids)->delete('user');
               response("Berhasil menghapus data");
           } catch (\Throwable $th) {
               response("Gagal menghapus data", 500);
           }
        }
    }
}