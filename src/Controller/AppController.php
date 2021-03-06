<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Core\Configure;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    public $Session;

    public $controllerLabel;

    private $title_for_layout;

    public $default_template_form;
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('Menu');

        $this->loadComponent(
            'Auth', [
                'authorize'=> 'Controller',
                'authenticate' => [
                    'Form' => [
                        'fields' => [
                            'username' => 'email',
                            'password' => 'password'
                        ]
                    ]
                ],
                'loginAction' => [
                    'controller' => 'Users',
                    'action' => 'login'
                ]
            ]
        );

        $this->Session = $this->request->session();
        $this->loadModel('Profiles');
        $this->loadModel('Users');
        $this->Auth->allow(['add', 'edit', 'index']);

        $this->title_for_layout = 'Cordel';
        $this->controllerLabel = $this->Session->read("Auth.User.Profile.{$this->request->param('controller')}.controller_label");
        $this->default_template_form = 'askdfkasd';

    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return void
     */
    public function beforeRender(Event $event)
    {   
        $this->set('title_for_layout', $this->title_for_layout);
        $this->set('controller_label', $this->controllerLabel);

        $group_menu_id_selected = $this->Session->read("Auth.User.Profile.{$this->request->param('controller')}.group_menu_id");

        $name_group_menu_selected = $this->Session->read("Auth.User.Profile.{$this->request->param('controller')}.name_group_menu");

        $this->Session->write('Menu.selected', $group_menu_id_selected);
        $this->Session->write('Menu.name_group_menu_selected', $name_group_menu_selected);

        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }

    public function beforeFilter(Event $event) 
    {   
        if ($this->Auth->user()) {  
            if (!$this->Session->check("Auth.User.Profile")) {
                $this->Session->write("Auth.User.Profile", $this->Profiles->getAreas($this->Auth->user("profile_id")));
                $this->Users->lastLogin($this->Auth->user("id"));
                $this->Menu->mount();              
            }

            $currentAction = $this->request->param('action');

            if (!$this->Auth->user('pass_switched') && $currentAction != 'changePassword'){
                $this->redirect(['controller' => 'users', 'action' => 'changePassword']);
            }
        }
    }

    public function isAuthorized($user)
    {
        return true;
    }

    protected function checkAccess($controller = null, $action = null) 
    {   

        if ($controller == null || $action == null) {
            $this->Flash->set("Ocorreu um erro de permissão. (erro: falta de parametros)",['params' => ['class' => 'error']]);
            return $this->redirect("/");
        }

        if (!$this->Session->check("Auth.User")) {
            $this->Flash->set('Por favor, efetue login para ter acesso a esta área.',['params' => ['class' => 'error']]);
            return $this->redirect($this->referer());
        }

        if (!$this->Session->check("Auth.User.Profile.{$controller}")) {
            $this->Flash->set("Você não tem acesso a esta área ({$this->name}).",['params' => ['class' => 'error']]);
            return $this->redirect("/");
        }

        if (!$this->Session->check("Auth.User.Profile.{$controller}.action.{$action}")) {
            $this->Flash->set("Você não tem acesso a esta operação ({$this->name}: {$action}).",['params' => ['class' => 'error']]);
            return $this->redirect("/");
        }
    }

    public function isAdmin( $profile_id = null )
    {   
        if( !$profile_id ) {
            $profile_id = $this->Auth->user("profile_id");
        }

        return $profile_id == Configure::read( 'AdminProfileId' );
    }

    public function isAdminUser( $user_id = null )
    {   
        Configure::read( 'AdminUserId' );die;
        if( !$user_id ){
            $user_id = $this->Auth->user("id");
        }

        return $user_id == Configure::read( 'AdminUserId' );
    }

}
