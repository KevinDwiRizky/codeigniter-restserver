<?php

if (!function_exists('convert_to_int')) {
    /**
     * Convert Rupiah to integer number
     * 
     * Fungsi ini akan mengconvert sebuah angka rupiah menjadi bilangan integer,
     *
     * @access public
     * @author kur0nek-o
     * @param string $number String rupiah (required).
     * @return int Mengembalikan integer number.
     */
    function convert_to_int($number)
    {
        $num         = ['Rp', '.'];
        $num_replace = ['', ''];

        $hasil = str_replace($num, $num_replace, $number);

        return $hasil;
    }

    function convert_to_int_update($number)
    {
        $num         = ['Rp', '.'];
        $num_replace = ['', ''];

        // Menghapus simbol 'Rp' dan titik
        $hasil = str_replace($num, $num_replace, $number);

        // Mengonversi hasil ke integer
        return (int) $hasil;
    }

}

if (!function_exists('convert_to_rupiah')) {
    /**
     * Convert integer number to rupiah
     * 
     * Fungsi ini akan mengconvert sebuah bilangan integer menjadi format rupiah,
     *
     * @access public
     * @author kur0nek-o
     * @param int $number Bilangan integer (required).
     * @return string Mengembalikan string rupiah.
     */
    function convert_to_rupiah($number)
    {
        $rupiah = number_format($number, 0, "", ".");

        return 'Rp. ' . $rupiah;
    }
}
