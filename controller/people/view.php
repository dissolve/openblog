<?php
class ControllerPeopleView extends Controller {
    public function index()
    {


        $data['auth_page'] = $this->url->link('auth/login');
        $data['auth_endpoint'] = AUTH_ENDPOINT;

        if (isset($this->session->data['user_site'])) {
            $data['user_name'] = $this->session->data['user_site'];
        }
        $data['logout'] = $this->url->link('auth/logout');

        if ($this->session->data['is_owner']){
            $this->response->redirect('error/unauthorized');

        } else {
            $this->document->setTitle('People');
            $this->document->setDescription($this->config->get('config_meta_description'));
            $this->document->setBodyClass('h-feed');

            $this->load->model('blog/person');

            $data['people'] = $this->model_blog_person->getPeople(20, 0);


            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/people/view.tpl')) {
                $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/people/view.tpl', $data));
            } else {
                $this->response->setOutput($this->load->view('default/template/people/view.tpl', $data));
            }
        }
    }

    public function merge()
    {
        $primary = $this->request->post['primary'];
        $list = explode(',', $this->request->post['list']);


        $this->log->write('primary: ' . $primary);
        $this->log->write('list: ' . print_r($list, true));

        $this->load->model('blog/person');
        foreach($list as $secondary){
            //$this->model_blog_person->joinPeople($primary, $secondary);
        }

    }
}
