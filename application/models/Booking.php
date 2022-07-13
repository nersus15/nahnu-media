<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Booking extends CI_Model{
    function get_all(){
        /** @var Datatables */
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
        return $data;
    }
    function my_history($mid){
    
    }
    function get_last(){
        $lapangan = $this->db->select('*')->order_by('id', 'DESC')->get('jadwal')->result();
        return $lapangan;
    }

    function create($data, $member = true){
        try {
            $lastid = $this->db->select('id')->order_by('id', 'DESC')->get('booking')->row();
            if(!empty($lastid)){
                $lastid = substr($lastid, 12, 2);
                $lastid = strlen($lastid) > 1 ? $lastid++ : '0' . $lastid++;
                
            }else{
                $lastid = "01";
            }
            $data['id'] = "BOOK" . str_replace('-', '', waktu(null, MYSQL_DATE_FORMAT)) . $lastid;
            $data['dibuat'] = waktu();
            $data['status'] = "baru";
            $this->db->insert('booking', $data);

            return [true, 'Berhasil Booking'];
        } catch (\Throwable $th) {
            return [false, "Gagal Booking " . print_r($th, true)];
        }
    }

    function update($data){
        try {
            $this->db->where('id', $data['id'])->update('jadwal', array('lapangan' => $data['lapangan'], 'mulai' => $data['mulai'], 'selesai' => $data['selesai'], 'tarif' => $data['tarif']));
            return [true, 'Berhasil update data jadwal #' . $data['id']];
        } catch (\Throwable $th) {
            return [false, "Gagal update data lapangan " . print_r($th, true)];
        }
    }

    function delete($ids){
        try {
            $this->db->where_in('id', $ids)->delete('jadwal');
            return [true, "Berhasil hapus data"];
        } catch (\Throwable $th) {
            return [false, "Gagal Menghapus Data jadwal"];
        }
    }
}