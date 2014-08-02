<?php

//No direct access allowed.
if(!function_exists('add_action')){
    echo 'Get Well Soon :)';
    exit;
}

class SWPL_Engine{
    
    /**
     * Main Constructor
     */
    public function __construct() {
        
        //Add settings menu
        add_action('admin_menu', array($this, 'constructMenu'));        
        
        //Process Login
        if(1 == get_option('swpl_l')){
            //remove wordpress authentication
            remove_filter('authenticate', 'wp_authenticate_username_password', 20);

            //custom authentication function
            add_filter('authenticate', array($this, 'swplAuthenticate'), 20, 3); 

            //On login form, change "Username" to "Email"
            add_action('login_form_login', array($this,'swplLoginFormLogin'));  
        } 
        
        
        //Process Register
        if(1 == get_option('swpl_r')){                
            //Assign email to username
            add_action('login_form_register', array($this, 'swplLoginFormRegister'));

            //Remove error for username, show error for email only.
            add_filter('registration_errors', array($this, 'swplRegistrationErrors'), 10, 3);

            /**
             * Add own style to registration form.
             * Ref: 
             * http://codex.wordpress.org/Customizing_the_Login_Form
             * http://codex.wordpress.org/Function_Reference/wp_enqueue_script
             * 
             */
            //>3.0.1 only
            //add_action('login_enqueue_scripts', array($this, 'swplLoginEnqueueScripts'));            
            add_action('login_head', array($this, 'swplLoginEnqueueScripts'));            
        }
        
        
        //Process Reset Password
        if(1 == get_option('swpl_rp')){
            add_action('login_form_lostpassword', array($this, 'swplLoginFormLostPassword'));
            add_action('login_form_retrievepassword', array($this, 'swplLoginFormLostPassword'));
        }
    }
    
    /**
     * Adds menu item to WordPress admin menu
     */
    function constructMenu(){
        add_options_page(
                __('Smart WP Login', 'smart-wp-login'), 
                'Smart WP Login', 
                'manage_options', 
                'smart-wp-login',
                array($this, 'smartOptions'));
    }
   
    /**
     * Renders Settings Page
     */
    function smartOptions(){
        if('post' == strtolower($_SERVER['REQUEST_METHOD'])){

            $swpl_options = array('swpl_l', 'swpl_r', 'swpl_rp');

            foreach($swpl_options as $option){
                if(isset($_POST[$option]) && 1 == $_POST[$option]){
                    update_option($option, true);
                }else{
                    update_option($option, false);
                }
            }

            $message = 'Your preferences have been successfully saved.';
        }
    ?>
    <div class="wrap">
        <h2>Smart WP Login Settings</h2>
        <?php
            if(isset($message)){
                echo '<div class="updated below-h2"><p>'.$message.'</p></div>';
            }
        ?>
        <form action="<?php echo admin_url('options-general.php?page=smart-wp-login'); ?>" method="post">        
            <label for="swpl_l">
                <input type="checkbox" id="swpl_l" name="swpl_l" value="1" 
                    <?php echo (1 == get_option('swpl_l'))? 'checked="checked"':''; ?>> Enable in Login
            </label>
            <br>
            <label for="swpl_r">
                <input type="checkbox" id="swpl_r" name="swpl_r" value="1"
                    <?php echo (1 == get_option('swpl_r'))? 'checked="checked"':''; ?>> Enable in Registration
            </label>
            <br>
            <label for="swpl_rp">
                <input type="checkbox" id="swpl_rp" name="swpl_rp" value="1"
                    <?php echo (1 == get_option('swpl_rp'))? 'checked="checked"':''; ?>> Enable in Password Reset
            </label>
            <br>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
    }

    ############################################################################
    #  Login With Email
    ############################################################################
    
    function swplAuthenticate($user, $email, $password){

        //Check for empty fields
        if(empty($email) || empty ($password)){        
            //create new error object and add errors to it.
            $error = new WP_Error();

            if(empty($email)){ //No email
                $error->add('empty_username', __('<strong>ERROR</strong>: Email field is empty.'));
            }
            else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){ //Invalid Email
                $error->add('invalid_username', __('<strong>ERROR</strong>: Email is invalid.'));
            }

            if(empty($password)){ //No password
                $error->add('empty_password', __('<strong>ERROR</strong>: Password field is empty.'));
            }

            return $error;
        }

        //Check if user exists in WordPress database
        $user = get_user_by('email', $email);

        //bad email
        if(!$user){
            $error = new WP_Error();
            $error->add('invalid', __('<strong>ERROR</strong>: Either the email or password you entered is invalid.'));
            return $error;
        }
        else{ //check password
            if(!wp_check_password($password, $user->user_pass, $user->ID)){ //bad password
                $error = new WP_Error();
                $error->add('invalid', __('<strong>ERROR</strong>: Either the email or password you entered is invalid.'));
                return $error;
            }else{
                return $user; //passed
            }
        }
    }
    
    function swplLoginFormLogin(){        
        //Change "Username" text to "Email"
        add_filter('gettext', array($this, 'swplLoginFormGettext'), 20, 3);
    } 
    
    function swplLoginFormGettext($translated_text, $text, $domain ){
        switch($translated_text){
            case 'Username': //For Login
                $translated_text = 'Email';
                break;
        }

        return $translated_text;
    }
    
    

    ############################################################################
    #  Register With Email
    ############################################################################
    
    function swplLoginFormRegister(){
        if(isset($_POST['user_login']) && isset($_POST['user_email']) && !empty($_POST['user_email'])){
            $_POST['user_login'] = $_POST['user_email'];
        }
    }
    
    function swplRegistrationErrors($wp_error, $sanitized_user_login, $user_email){
        if(isset($wp_error->errors['empty_username'])){
            unset($wp_error->errors['empty_username']);
        }

        if(isset($wp_error->errors['username_exists'])){
            unset($wp_error->errors['username_exists']);
        }
        return $wp_error;
    }
    
    function swplLoginEnqueueScripts(){
        //Don't show username field in register form.
        ?>
            <style>
                #registerform > p:first-child{
                    visibility: hidden;
                    display:none;
                }
            </style>

            <script type="text/javascript" src="<?php echo site_url('/wp-includes/js/jquery/jquery.js'); ?>"></script>
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    $('#registerform > p:first-child').css('display', 'none');
                });
            </script>
        <?php
    }
    

    ############################################################################
    #  Reset Password With Email
    ############################################################################
    
    function swplLoginFormLostPassword(){
        if('post' == strtolower($_SERVER['REQUEST_METHOD']) && isset($_POST['user_login'])){
            
            //To skip default wordpress processing.
            $_SERVER['REQUEST_METHOD'] = ':(';  
            global $errors;
            
            if(empty($_POST['user_login'])){
                $errors->errors['empty_username'] = array('<strong>ERROR</strong>: Enter an e-mail address.');
            
                //In case of error, later restore previous REQUEST_METHOD value
                add_action('lost_password', array($this, 'swplLostPassword'));
            }else if(!filter_var($_POST['user_login'], FILTER_VALIDATE_EMAIL)){
                $errors->errors['invalid_combo'] = array('<strong>ERROR</strong>: Invalid e-mail.');
                            
                //In case of error, later restore previous REQUEST_METHOD value
                add_action('lost_password', array($this, 'swplLostPassword'));
            }else{ //Don't skip now
                $_SERVER['REQUEST_METHOD'] = 'POST';
            }
        }

        //Change "Retrieve Password" related text
        add_filter('gettext', array($this, 'swplLostPasswordGettext'), 20, 3); 
    }    
    
    function swplLostPassword(){
        //Restore right value
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }
    
    function swplLostPasswordGettext($translated_text, $text, $domain ){
        switch($translated_text){
            case 'Please enter your username or e-mail address. You will receive a new password via e-mail.':
            case 'Please enter your username or email address. You will receive a link to create a new password via email.':
                $translated_text = 'Please enter your email address. You will receive a link to create a new password via email.';
                break;
            /* No need, we have custom message above this snippet
            case '<strong>ERROR</strong>: Enter a username or e-mail address.';
                $translated_text = '<strong>ERROR</strong>: Enter an e-mail address.';
                break;
            */
            case '<strong>ERROR</strong>: Invalid username or e-mail.':
                $translated_text = '<strong>ERROR</strong>: Invalid e-mail.';
                break;
            case 'Username or E-mail:':
                $translated_text = 'E-mail:';
                break;
        }

        return $translated_text;
    }
}