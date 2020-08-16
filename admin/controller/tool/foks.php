<?php
    
    class ControllerToolFoks extends Controller {
        private $error = array();
        
        public function index() {
    
            $this->document->addScript('admin/view/app/dist/scripts/main.js');
            $this->document->addStyle('admin/view/app/dist/styles/main.css');
            
            $this->document->setTitle( 'Foks import/Export' );
            
            $this->load->model( 'tool/backup' );
            
            if ( ($this->request->server['REQUEST_METHOD'] == 'POST') && $this->user->hasPermission( 'modify', 'tool/foks' ) ) {
                
                
                $data['heading_title'] = 'Foks import/Export';
                
                if ( isset( $this->session->data['error'] ) ) {
                    $data['error_warning'] = $this->session->data['error'];
                    
                    unset( $this->session->data['error'] );
                } elseif ( isset( $this->error['warning'] ) ) {
                    $data['error_warning'] = $this->error['warning'];
                } else {
                    $data['error_warning'] = '';
                }
                
                if ( isset( $this->session->data['success'] ) ) {
                    $data['success'] = $this->session->data['success'];
                    
                    unset( $this->session->data['success'] );
                } else {
                    $data['success'] = '';
                }
                
                $data['breadcrumbs'] = array();
                
                $data['breadcrumbs'][] = array(
                    'text' => 'Home',
                    'href' => $this->url->link( 'common/dashboard', 'token=' . $this->session->data['token'], 'SSL' )
                );
                
                $data['breadcrumbs'][] = array(
                    'text' => 'Foks',
                    'href' => $this->url->link( 'tool/backup', 'token=' . $this->session->data['token'], 'SSL' )
                );
                
                $data['header']      = $this->load->controller( 'common/header' );
                $data['column_left'] = $this->load->controller( 'common/column_left' );
                $data['footer']      = $this->load->controller( 'common/footer' );
                
                $this->response->setOutput( $this->load->view( 'tool/foks.tpl', $data ) );
            }
        }
        
    }
