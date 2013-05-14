RECaptcha-for-Codeigniter
=========================

SETUP: 

Copy Folder config and libraries to ./application

Edit ./application/config/recaptcha.php to update public and private key obtained from google here: 
 *          https://www.google.com/recaptcha/admin/create?domains=&app=Codeigniter
 *          -> Use this URL for addressing the use of CodeIgniter - so that Google may discover it and host it on their site. 

- Setup is done. 

HOW IT WORKS: 
===========================


Load using CI: 
<code>$this->load->library('recaptcha');</code>

Get the HTML for the recaptcha: 
<code> $this->recaptcha->recaptcha_get_html();</code>

ex. 
<pre><code>
$this->load->library('recaptcha');
                    $data['recaptcha_html'] = $this->recaptcha->recaptcha_get_html();
                    $data['page'] = 'login/login';
                    $this->load->view(plane,$data);
</code></pre>

On the website use this inside the form. 

<code> <?php echo $recaptcha_html; ?> </code>

wherever you want the captcha.

Check the REcaptcha with the following function when submitted. 

Run this code: 
<pre><code>
$this->recaptcha->recaptcha_check_answer(
                    $_SERVER['REMOTE_ADDR'],
                    $this->input->post('recaptcha_challenge_field'),
                    $this->input->post('recaptcha_response_field'));
</code></pre>

Check if the captcha was correct with: 

<code>$this->recaptcha->is_valid</code> (BOOLEAN)

If it returns false, check errors with: 

<code>$this->recaptcha->error</code>

EXAMPLE CODE
=============

```php
<?php

class Login extends MY_Controller {

	function  __construct()  {
        parent::__construct();
    }
        //Loads the login form if the user isnt logged in - redirects to root folder elseif. 
	public function index() {
	   
	       //Checks to see if the user is logged in
		if ($this->session->userdata('logged_in') == false)
		{
                    
                    $this->load->library('recaptcha');
                    
                    //Store the captcha HTML for correct MVC pattern use.
                    $data['recaptcha_html'] = $this->recaptcha->recaptcha_get_html();
                    
                    $data['page'] = 'login/login';
                    $this->load->view('login_template',$data); 
                    
                    
                    
                    
            }
        }
	
	//Gets called by the submit-form
	public function submit() {
	
	           //Load REcaptcha again.
                $this->load->library('recaptcha');
                
            //Check if anything is submitted at all
		if ($this->input->post('username') !== FALSE && $this->input->post('password') !== FALSE) 
		{
		          //Call to recaptcha to get the data validation set within the class. 
                    $this->recaptcha->recaptcha_check_answer($_SERVER['REMOTE_ADDR'],$this->input->post('recaptcha_challenge_field'),$this->input->post('recaptcha_response_field'));
                    
                    //Store the username and password for easier access
                    $username = $this->input->post('username');
                    $password = $this->input->post('password');
                    
                    
                    
                    if ($username == "user" && $password == "password" && $this->recaptcha->is_valid) {
                        
                        $this->session->set_userdata('logged_in','yes');
                        $this->session->set_userdata('user_id','test');
                        
                        redirect('');
                    } else {
                        if(!$this->recaptcha->is_valid) {
                            $this->session->set_flashdata('error','incorrect captcha');
                        } else {
                            $this->session->set_flashdata('error','incorrect credentials');
                        }
                        
                        
                        
                        redirect('login');
                    }
                    
		} else { redirect(''); }
	}//.submit()
	

}
```

Add this to the login webpage template. 

```html
    <html><body>
    <form action="login/submit" method="post">
    <span class="loginerror"> <?php if ($this->session->flashdata('error') !== FALSE) { echo $this->session->flashdata('error'); } ?></span>
    <span style="margin-top: 10px;	float: right;">
		            
    </span>
    <input  type="text" name="username" placeholder="username">
		 
    <input type="password" name="password" placeholder="password">
		 
		       
		 
    <input type="submit" value="log in">
    <span style="margin-top: 5px;	float: left;">
    <a href="#test" data-toggle="modal">Forgotten password</a>
    </span>
    <p><a href="<?php echo $this->recaptcha->recaptcha_get_signup_url(); ?>" >Get your API Code HERE</a></p>
    <?php echo $recaptcha_html; ?>
    </form>
    </html></body>
```
