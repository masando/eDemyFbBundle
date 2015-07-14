<?php

namespace eDemy\FbBundle\Controller;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArgvInput;

use Facebook as FB;

use eDemy\MainBundle\Controller\BaseController;
use eDemy\MainBundle\Event\ContentEvent;

class FbController extends BaseController
{
    public static function getSubscribedEvents()
    {
        return self::getSubscriptions('facebook', [], array(
            'edemy_facebook_login'      => array('onFacebookLogin', 0),
            'edemy_facebook_logout'      => array('onFacebookLogout', 0),
            'edemy_meta_module'         => array('onMetaModule', 0),
            'edemy_precontent_module'   => array('onPreContentModule', 0),
            'edemy_facebook_promo'     => array('onPromo', 0),
            'edemy_facebook_privacy'     => array('onFacebookPrivacy', 0),
            'edemy_instagram_frontpage'     => array('onInstagramFrontpage', 0),
        ));
    }

    public function likesAction()
    {
        $this->setContainer($this->get('service_container'));
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'No tienes permisos para acceder a este recurso!');
        //$input = new ArgvInput(array());
        //$output = new BufferedOutput();
        //$command = $this->get("edemy.getlikes");
        //$command->run($input, $output);
        //return $this->newResponse($output->fetch());
        //die(var_dump($output->fetch()));
        $pages = $this->getParam('facebook.page.likes');
        $ref = $this->getParam('facebook.page.ref');

        $list = array();
        if(count($pages)) {
            foreach($pages as $page) {
                $name = $page->getValue();
                $list[$name]['name'] = $name;
                $list[$name]['likes'] = $this->facebook_count($name);
            }
        }

        uasort($list, array( $this, 'cmp' ));
        $ref = $this->facebook_count($ref);

        return $this->newResponse($this->render('templates/facebook/likes', array(
            'pages' => $list,
            'ref' => $ref,
        )));
    }

    public function cmp($a, $b)
    {
       return $b['likes'] > $a['likes'];
    }

    function facebook_count($url){
        $url = 'https://www.facebook.com/' . $url;
        // Query in FQL
        $fql  = "SELECT share_count, like_count, comment_count ";
        $fql .= " FROM link_stat WHERE url = '$url'";
     
        $fqlURL = "https://api.facebook.com/method/fql.query?format=json&query=" . urlencode($fql);
     
        // Facebook Response is in JSON
        $response = file_get_contents($fqlURL);
        $fb_count = json_decode($response);

        return $fb_count[0]->like_count;
    }

    public function onInstagramFrontpage(ContentEvent $event)
    {
        $clientId = $this->getParam('instagram.clientId');
        $userId = $this->getParam('instagram.userId');
        $accessToken = $this->getParam('instagram.accessToken');

        $this->addEventModule($event, 'templates/facebook/instagram', array(
            'clientId' => $clientId,
            'userId' => $userId,
            'accessToken' => $accessToken,
        ));
    }

    public function onFacebookPrivacy(ContentEvent $event)
    {
        $this->addEventModule($event, 'templates/facebook/fbPrivacy');
    }

    public function fbLoginOrUser($redirectUrl)
    {
        $appId = $this->getParam('app.id');
        $appSecret = $this->getParam('app.secret');
//        die(var_dump($appId));
        FB\FacebookSession::setDefaultApplication($appId, $appSecret);
//        $redirectUrl = $this->get('router')->generate('edemy_facebook_login', array(), true);
        $helper = new FB\FacebookRedirectLoginHelper($redirectUrl);
        try {
            if($this->get('session')->get('facebook_token')) {
                $session = new FB\FacebookSession($this->get('session')->get('facebook_token'));
            } else {
                $session = $helper->getSessionFromRedirect();
            }
        } catch(FB\FacebookRequestException $ex) {
        } catch(\Exception $ex) {
        }
        if (isset($session)) {
            $user_profile = (new FB\FacebookRequest($session, 'GET', '/me'))->execute()->getGraphObject(FB\GraphUser::className());
            $id = $user_profile->getProperty('id');
            $name = $user_profile->getProperty('name');
            $first_name = $user_profile->getProperty('first_name');
            $last_name = $user_profile->getProperty('last_name');
            $middle_name = $user_profile->getMiddleName();
            $link = $user_profile->getLink();
            $birthday = $user_profile->getBirthday();
            $location = $user_profile->getLocation();
            $email = $user_profile->getEmail();
            $gender = $user_profile->getGender();

            //checkuser($fuid,$ffname,$femail);
            //header("Location: index.php");

            $response = array(
                'type' => 'user',
                'name' => $first_name
            );

            $message = \Swift_Message::newInstance()
                ->setSubject('fbAccess')
                ->setFrom($email)
//                ->setTo($this->getParam('sendtomail'))
                ->setBcc('manuel@edemy.es')
                ->setBody(
                    "Id: " . $id .
                    "\nNombre: " . $name .
                    "\nFirst Name: " . $first_name .
                    "\nLast Name: " . $last_name .
                    "\nMiddle Name: " . $middle_name .
                    "\nLink: " . $link .
                    "\nBirthday: " . $birthday .
                    "\nLocation: " . $location .
                    "\nEmail: " . $email .
                    "\nGender: " . $gender
                )
            ;
            $this->get('mailer')->send($message);


        } else {

            $response = array(
                'type' => 'loginUrl',
                'loginUrl' => $helper->getLoginUrl(),
            );
        }

        return $response;
    }

    public function onFacebookLogin(ContentEvent $event)
    {
        FB\FacebookSession::setDefaultApplication('', '');
        $redirectUrl = $this->get('router')->generate('edemy_facebook_login', array(), true);
        $helper = new FB\FacebookRedirectLoginHelper($redirectUrl);
        try {
            if($this->get('session')->get('facebook_token')) {
                $session = new FB\FacebookSession($this->get('session')->get('facebook_token'));
            } else {
                $session = $helper->getSessionFromRedirect();
            }
        } catch(FB\FacebookRequestException $ex) {
        } catch(\Exception $ex) {
        }
        if (isset($session)) {
            $this->get('session')->set('facebook_token', $session->getToken());
            // graph api request for user data
            $request = new FB\FacebookRequest( $session, 'GET', '/me' );
            $response = $request->execute();
            // get response
            $graphObject = $response->getGraphObject();
            $fbid = $graphObject->getProperty('id');              // To Get Facebook ID
            $fbfullname = $graphObject->getProperty('name'); // To Get Facebook full name
            $femail = $graphObject->getProperty('email');    // To Get Facebook email ID
            $_SESSION['FBID'] = $fbid;
            $_SESSION['FULLNAME'] = $fbfullname;
            $_SESSION['EMAIL'] =  $femail;
            //checkuser($fuid,$ffname,$femail);
            //header("Location: index.php");
            $this->addEventModule($event, 'templates/facebook/testFb', array(
                'fbfullname' => $fbfullname,
            ));
        } else {
            $event->addModule('<a href="' . $helper->getLoginUrl() . '">Login with Facebook</a>');
        }
    }

    public function onFacebookLogout(ContentEvent $event)
    {
        if($this->get('session')->get('facebook_token')) {
            $this->get('session')->remove('facebook_token');
        }

        return true;
        $appId = $this->getParam('app.id');
        $appSecret = $this->getParam('app.secret');
        FB\FacebookSession::setDefaultApplication($appId, $appSecret);
        try {
            if($this->get('session')->get('facebook_token')) {
                $session = new FB\FacebookSession($this->get('session')->get('facebook_token'));
            }
        } catch(FB\FacebookRequestException $ex) {
        } catch(\Exception $ex) {
        }

        if(isset($session)){
            $session->session_destroy();
        }

        return true;
    }

    public function onPromo(ContentEvent $event)
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $_route = $request->attributes->get('id');

        $required_scope     = 'public_profile, publish_actions, email'; //Permissions required
        $redirectUrl = $this->get('router')->generate('edemy_facebook_promo', array(), true);

        FB\FacebookSession::setDefaultApplication('', '');
        $helper = new FB\FacebookRedirectLoginHelper($redirectUrl);

        try {
            $session = $helper->getSessionFromRedirect();
        } catch(FacebookRequestException $ex) {
            // When Facebook returns an error
        } catch(Exception $ex) {
            // When validation fails or other local issues
        }

        if (isset($session) ) {
            $user_profile = (new FB\FacebookRequest($session, 'GET', '/me'))->execute()->getGraphObject(FB\GraphUser::className());
            //$graphObject->getProperty("email");
            echo print_r($user_profile);

            $this->addEventModule($event, 'templates/facebook/fbPromo', array(
                'id' => $user_profile->getProperty('id'),
                'name' => $user_profile->getProperty('name'),
                'first_name' => $user_profile->getProperty('first_name'),
                'last_name' => $user_profile->getProperty('last_name'),
                'middle_name' => $user_profile->getMiddleName(),
                'link' => $user_profile->getLink(),
                'birthday' => $user_profile->getBirthday(),
                'location' => $user_profile->getLocation(),
                'email' => $user_profile->getEmail(),
                'gender' => $user_profile->getGender(),
//                'verified' => $user_profile->getVerified(),
            ));
        } else {
            $login_url = $helper->getLoginUrl( array( 'scope' => $required_scope ) );
            $this->addEventModule($event, 'templates/facebook/fbPromo', array(
                'login_url' => $login_url,
            ));
        }


//            $request = new FB\FacebookRequest($session,'POST','/me/feed', array ('message' => 'hola', 'scope' => 'publish_actions',));
//            $response = $request->execute();
//            $graphObject = $response->getGraphObject();


//        try {
//            if($this->get('session')->get('facebook_token')) {
//                $session = new FB\FacebookSession($this->get('session')->get('facebook_token'));
//            } else {
//                $session = $helper->getSessionFromRedirect();
//            }
//        } catch(FB\FacebookRequestException $ex) {
//        } catch(\Exception $ex) {
//        }
//        if (isset($session)) {
//            $this->get('session')->set('facebook_token', $session->getToken());
//            // graph api request for user data
//            $request = new FB\FacebookRequest( $session, 'GET', '/me' );
//            $response = $request->execute();
//            // get response
//            $graphObject = $response->getGraphObject();
//            $fbid = $graphObject->getProperty('id');              // To Get Facebook ID
//            $fbfullname = $graphObject->getProperty('name'); // To Get Facebook full name
//            $femail = $graphObject->getProperty('email');    // To Get Facebook email ID
//            $_SESSION['FBID'] = $fbid;
//            $_SESSION['FULLNAME'] = $fbfullname;
//            $_SESSION['EMAIL'] =  $femail;
//            //checkuser($fuid,$ffname,$femail);
//            //header("Location: index.php");
//            $this->addEventModule($event, 'templates/testFb', array(
//                'fbfullname' => $fbfullname,
//            ));
//        } else {
//        }
//        $this->addEventModule($event, 'templates/fbSorteoPuraAlegria');
    }

    public function onFacebookLogin2(ContentEvent $event)
    {
        $this->addEventModule($event, 'templates/facebook/loginFb');

        return true;
    }

    public function onPreContentModule(ContentEvent $event) {
        if($this->getRoute() != 'edemy_main_frontpage') {
            $likeurl = $this->getParam('likeurl');
            if($likeurl != 'likeurl') {
                $this->addEventModule($event, 'templates/facebook/precontentFb', array(
                    'likeurl' => $likeurl,
                ));
            }
        }
        
        return true;
    }

    public function onMetaModule(ContentEvent $event) {
        $pixel_id = $this->getParam('facebook.pixel_id');
        if($pixel_id != 'facebook.pixel_id') {
            $this->addEventModule($event, 'templates/facebook/meta_module', array(
                'pixel_id' => $pixel_id,
            ));
        }

        return true;
    }

    public function likes()
    {
        $input = new ArgvInput(array());
        $output = new BufferedOutput();
        $command = $this->get("edemy.get.likes");
        $command->run($input, $output);
        die(var_dump($output->fetch()));
        return $output->fetch();
    }
}
