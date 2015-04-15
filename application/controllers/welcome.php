<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

    /**
     * The home page of the site
     * recommend the hot queries
     */
    public function index()
    {
        $this->load->view('inc/header');
        $this->load->view('home');
        $this->load->view('inc/footer');
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
